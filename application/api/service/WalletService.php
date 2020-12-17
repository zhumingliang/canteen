<?php


namespace app\api\service;


use app\api\job\UploadExcel;
use app\api\model\CanteenAccountT;
use app\api\model\CompanyAccountT;
use app\api\model\CompanyStaffT;
use app\api\model\CompanyT;
use app\api\model\DinnerV;
use app\api\model\OrderParentT;
use app\api\model\OrderT;
use app\api\model\PayT;
use app\api\model\RechargeCashT;
use app\api\model\RechargeSupplementT;
use app\api\model\RechargeV;
use app\api\model\UserBalanceV;
use app\api\validate\Company;
use app\lib\enum\CommonEnum;
use app\lib\enum\OrderEnum;
use app\lib\enum\PayEnum;
use app\lib\exception\AuthException;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;
use think\Db;
use think\Model;
use think\Queue;
use think\Request;
use zml\tp_tools\Redis;

class WalletService
{
    public function rechargeCash($params)
    {
        $detail = json_decode($params['detail'], true);
        if (empty($detail)) {
            throw new ParameterException(['msg' => '充值用户信息格式错误']);
        }
        $company_id = Token::getCurrentTokenVar('company_id');
        $admin_id = Token::getCurrentUid();
        $account_id = empty($params['account_id']) ? 0 : $params['account_id'];
        $data = $this->prefixDetail($company_id, $admin_id, $detail, $account_id, $params['money'], $params['remark']);
        $cash = (new RechargeCashT())->saveAll($data);
        if (!$cash) {
            throw new SaveException();
        }
    }

    public function rechargeCashUpload($cash_excel)
    {
        $company_id = Token::getCurrentTokenVar('company_id');
        $admin_id = Token::getCurrentUid();
        $fileName = (new ExcelService())->saveExcelReturnName($cash_excel);
        $fail = $this->checkData($company_id, $fileName);
        if (count($fail)) {
            return [
                'res' => false,
                'fail' => $fail
            ];
        }
        $this->uploadExcelTask($company_id, $admin_id, $fileName, "rechargeCash");
        return [
            'res' => true
        ];
    }

    //分账户批量充值
    public function rechargeCashUploadWithAccount($cash_excel)
    {
        $company_id = Token::getCurrentTokenVar('company_id');
        $admin_id = Token::getCurrentUid();
        $fileName = (new ExcelService())->saveExcelReturnName($cash_excel);
        $fail = $this->checkDataWithAccount($company_id, $fileName);
        if (count($fail)) {
            return [
                'res' => false,
                'fail' => $fail
            ];
        }
        $this->uploadExcelTask($company_id, $admin_id, $fileName, "rechargeCashWithAccount");
        return [
            'res' => true
        ];
    }

    public function checkDataWithAccount($company_id, $fileName)
    {
        $data = (new ExcelService())->importExcel($fileName);
        $staffs = CompanyStaffT::staffs($company_id);
        $accounts = CompanyAccountT::accountsWithoutNonghang($company_id);
        $newStaffs = [];
        $accountsArr = [];
        foreach ($staffs as $k => $v) {
            array_push($newStaffs, $v['username'] . '&' . $v['phone']);
        }
        if (count($accounts)) {
            foreach ($accounts as $k => $v) {
                array_push($accountsArr, $v['name']);
            }
        }

        $fail = [];
        foreach ($data as $k => $v) {
            if ($k < 2) {
                continue;
            }
            if (!strlen($v[0]) || !strlen($v[1]) || !in_array($v[0] . '&' . $v[1], $newStaffs)) {
                array_push($fail, '第' . $k . '行数据有问题');
            }

            if (count($accountsArr) && !in_array($v[2], $accountsArr)) {
                array_push($fail, '第' . $k . '行数据有问题');

            }

            $money = trim($v[3]);
            if ($money == '') {
                array_push($fail, '第' . $k . '行数据有问题');
            }
        }
        return $fail;

    }


    public function checkData($company_id, $fileName)
    {
        $data = (new ExcelService())->importExcel($fileName);
        $staffs = CompanyStaffT::staffs($company_id);
        $newStaffs = [];
        foreach ($staffs as $k => $v) {
            array_push($newStaffs, $v['username'] . '&' . $v['phone']);
        }

        $fail = [];
        foreach ($data as $k => $v) {
            if ($k < 2) {
                continue;
            }
            if (!strlen($v[0]) || !strlen($v[1]) || !in_array($v[0] . '&' . $v[1], $newStaffs)) {
                array_push($fail, '第' . $k . '行数据有问题');
            }

            $money = trim($v[2]);
            if ($money == '') {
                array_push($fail, '第' . $k . '行数据有问题');
            }
        }
        return $fail;

    }

    public function uploadExcelTask($company_id, $u_id, $fileName, $type)
    {
        //设置限制未上传完成不能继续上传
        /* if (!$this->checkUploading($company_id, $u_id, $type)) {
             throw new SaveException(["msg" => '有文件正在上传，请稍等']);
         }*/
        $jobHandlerClassName = 'app\api\job\UploadExcel';//负责处理队列任务的类
        $jobQueueName = "uploadQueue";//队列名称
        $jobData = [
            'type' => $type,
            'company_id' => $company_id,
            'u_id' => $u_id,
            'fileName' => $fileName,
        ];//当前任务的业务数据
        $isPushed = Queue::push($jobHandlerClassName, $jobData, $jobQueueName);
        //将该任务推送到消息队列
        if ($isPushed == false) {
            (new UploadExcel())->clearUploading($company_id, $u_id, $type);
            throw new SaveException(['msg' => '上传excel失败']);
        }
    }


    private function checkUploading($company_id, $u_id, $type)
    {
        // $set = "uploadExcel";
        $code = "uploadExcel:" . "$company_id:$u_id:$type";
        $check = Redis::instance()->get($code);
        if ($check) {
            return false;
        }
        Redis::instance()->set($code, time(), 5 * 60);
        return true;
    }

    public function prefixUploadData($company_id, $admin_id, $data)
    {
        $dataList = [];
        $staffs = CompanyStaffT::staffs($company_id);
        $newStaffs = [];
        foreach ($staffs as $k => $v) {
            $newStaffs[$v['phone']] = $v['id'];
        }
        foreach ($data as $k => $v) {
            if ($k == 1 || empty($v[0])) {
                continue;
            }
            array_push($dataList, [
                'admin_id' => $admin_id,
                'company_id' => $company_id,
                'staff_id' => $newStaffs[$v[1]],
                'username' => $v[0],
                'phone' => $v[1],
                'money' => $v[2],
                'remark' => $v[3]
            ]);
        }
        return $dataList;

    }

    public function prefixUploadDataWithAccount($company_id, $admin_id, $data)
    {
        $dataList = [];
        $staffs = CompanyStaffT::staffs($company_id);
        $accounts = CompanyAccountT::accountsWithoutNonghang($company_id);
        $newStaffs = [];
        $newAccounts = [];
        foreach ($staffs as $k => $v) {
            $newStaffs[$v['phone']] = $v['id'];
        }
        foreach ($accounts as $k => $v) {
            $newAccounts [$v['name']] = $v['id'];
        }
        foreach ($data as $k => $v) {
            if ($k == 1 || empty($v[0])) {
                continue;
            }
            array_push($dataList, [
                'admin_id' => $admin_id,
                'company_id' => $company_id,
                'staff_id' => $newStaffs[$v[1]],
                'account_id' => count($newAccounts) ? $newAccounts[$v[2]] : 0,
                'username' => $v[0],
                'phone' => $v[1],
                'money' => $v[3],
                'remark' => $v[4]
            ]);
        }
        return $dataList;

    }

    private function prefixDetail($company_id, $admin_id, $detail, $account_id, $money, $remark)
    {
        $dataList = [];
        foreach ($detail as $k => $v) {
            $data = [];
            $data['company_id'] = $company_id;
            $data['account_id'] = $account_id;
            $data['money'] = $money;
            $data['staff_id'] = $v['staff_id'];
            $data['state'] = CommonEnum::STATE_IS_OK;
            $data['admin_id'] = $admin_id;
            $data['remark'] = $remark;
            array_push($dataList, $data);
        }
        return $dataList;
    }

    public function rechargeRecords($time_begin, $time_end,
                                    $page, $size, $type, $admin_id, $username,$department_id)
    {
        $company_id = Token::getCurrentTokenVar('company_id');
        $records = RechargeV::rechargeRecords($time_begin, $time_end,
            $page, $size, $type, $admin_id, $username, $company_id,$department_id);
        return $records;

    }

    public function exportRechargeRecords($time_begin, $time_end, $type, $admin_id, $username,$department_id)
    {
        $company_id = Token::getCurrentTokenVar('company_id');
        $records = RechargeV::exportRechargeRecords($time_begin, $time_end, $type, $admin_id, $username, $company_id,$department_id);
        $header = ['创建时间','部门', '姓名', '手机号', '充值金额', '充值途径', '充值人员', '备注'];
        $file_name = $time_begin . "-" . $time_end . "-充值记录明细";
        $url = (new ExcelService())->makeExcel($header, $records, $file_name);
        return [
            'url' => config('setting.domain') . $url
        ];

    }

    public function exportRechargeRecordsWithAccount($time_begin, $time_end, $type, $admin_id, $username,$department_id)
    {
        $company_id = Token::getCurrentTokenVar('company_id');
        $records = RechargeV::exportRechargeRecordsWithAccount($time_begin, $time_end, $type, $admin_id, $username, $company_id,$department_id);
        $header = ['创建时间','部门', '姓名', "手机号", '账户名称', '充值金额', '充值途径', '充值人员', '备注'];
        $file_name = $time_begin . "-" . $time_end . "-充值记录明细";
        $url = (new ExcelService())->makeExcel($header, $records, $file_name);
        return [
            'url' => config('setting.domain') . $url
        ];

    }

    public function usersBalance($page, $size, $department_id, $user, $phone)
    {
        $company_id = Token::getCurrentTokenVar('company_id');
        $checkCard = (new CompanyService())->checkConsumptionContainsCard($company_id);
        $balance = UserBalanceV::usersBalance($page, $size, $department_id, $user, $phone, $company_id, $checkCard);
        return $balance;
    }

    public function usersBalanceWithAccount($page, $size, $department_id, $user, $phone)
    {
        $company_id = Token::getCurrentTokenVar('company_id');
        $checkCard = (new CompanyService())->checkConsumptionContainsCard($company_id);
        $accounts = CompanyAccountT::accountsWithSortsAndDepartmentId($company_id);
        $staffs = CompanyStaffT::staffsForBalanceWithAccount($page, $size, $department_id, $user, $phone, $company_id);
        $staffs['data'] = $this->prefixAccount($staffs['data'], $accounts, $checkCard);
        return $staffs;
    }

    public function prefixAccount($staffs, $accounts, $checkCard)
    {


        if (count($staffs)) {
            foreach ($staffs as $k => $v) {
                if (!$checkCard) {
                    unset($staffs[$k]['card']);
                }
                $countData = [];
                foreach ($accounts as $k4 => $v4) {
                    array_push($countData, [
                        'account_id' => $v4['id'],
                        'name' => $v4['name'],
                        'type' => $v4['type'],
                        'fixed_type' => $v4['fixed_type'],
                        'have' => (new AccountService())->checkStaffHaveAccount($v4['department_all'], $v4['departments'], $v['d_id']),
                        'balance' => 0
                    ]);
                }
                $staffCountData = $countData;
                $account = $v['account'];
                if (count($account)) {
                    foreach ($staffCountData as $k2 => $v2) {
                        foreach ($account as $k3 => $v3) {
                            if ($v2['account_id'] == $v3['account_id']) {
                                $staffCountData[$k2]['balance'] = $v3['money'];
                                break;
                            }

                        }
                    }
                }
                $staffs[$k]['account'] = $staffCountData;
            }
        }
        return $staffs;

    }

    public function exportUsersBalance($department_id, $user, $phone)
    {
        $company_id = Token::getCurrentTokenVar('company_id');
        $checkCard = (new CompanyService())->checkConsumptionContainsCard($company_id);
        $staffs = UserBalanceV::exportUsersBalance($department_id, $user, $phone, $company_id, $checkCard);
        if ($checkCard) {
            $header = ['姓名', '员工编号', '卡号', '手机号码', '部门', '余额'];
        } else {
            $header = ['姓名', '员工编号', '手机号码', '部门', '余额'];
        }
        $file_name = "饭卡余额报表";
        $url = (new ExcelService())->makeExcel($header, $staffs, $file_name);
        return [
            'url' => config('setting.domain') . $url
        ];
    }

    public function exportUsersBalanceWithAccount($department_id, $user, $phone)
    {
        $company_id = Token::getCurrentTokenVar('company_id');
        $accounts = CompanyAccountT::accountsWithSorts($company_id);
        $checkCard = (new CompanyService())->checkConsumptionContainsCard($company_id);
        $staffs = CompanyStaffT::staffsForExportsBalance($department_id, $user, $phone, $company_id);
        if ($checkCard) {
            $header = ['姓名', '员工编号', '卡号', '手机号码', '部门'];
        } else {
            $header = ['姓名', '员工编号', '手机号码', '部门'];
        }

        $header = $this->prefixHeader($accounts, $header);
        $staffs = $this->prefixExportBalanceWithAccount($staffs, $accounts, $checkCard);
        $file_name = "饭卡余额报表";
        $url = (new ExcelService())->makeExcel($header, $staffs, $file_name);
        return [
            'url' => config('setting.domain') . $url
        ];
    }

    private function prefixHeader($accounts, $header)
    {
        foreach ($accounts as $k => $v) {
            array_push($header, $v['name']);
        }
        array_push($header, '总余额（元）');
        return $header;

    }


    private function prefixExportBalanceWithAccount($staffs, $accounts, $checkCard)
    {
        $dataList = [];
        if (count($staffs)) {
            foreach ($staffs as $k => $v) {
                if ($checkCard) {
                    $data = [
                        'username' => $v['username'],
                        'code' => $v['code'],
                        'card_num' => empty($v['card']['card_code']) ? '' : $v['card']['card_code'],
                        'phone' => $v['phone'],
                        'department' => $v['department']['name']
                    ];
                } else {
                    $data = [
                        'username' => $v['username'],
                        'code' => $v['code'],
                        'phone' => $v['phone'],
                        'department' => $v['department']['name']
                    ];
                }

                $account = $v['account'];
                $allBalance = 0;
                foreach ($accounts as $k2 => $v2) {
                    $accountBalance = 0;
                    if (count($account)) {
                        foreach ($account as $k3 => $v3) {
                            if ($v2['id'] == $v3['account_id']) {
                                $allBalance += $v3['money'];
                                $accountBalance = $v3['money'];
                                break;
                            }
                        }

                    }
                    $data[$v2['name']] = $accountBalance;
                }
                $data['总余额'] = $allBalance;
                array_push($dataList, $data);
            }
        }
        return $dataList;

    }

    public function getUserBalance($company_id, $phone, $staff_id = 0)
    {
        if (!$staff_id) {
            $staff = CompanyStaffT::staffName($phone, $company_id);
            $staff_id = $staff->id;
        }
        $balance = UserBalanceV::userBalance2($staff_id);
        return $balance;

    }


    public function getUserBalanceWithStaffId($staff_id)
    {
        $balance = UserBalanceV::userBalance2($staff_id);
        return $balance;

    }

    public function clearBalance()
    {
        $grade = Token::getCurrentTokenVar('grade');
        if ($grade != 2) {
            throw new AuthException();
        }
        $company_id = Token::getCurrentTokenVar('company_id');
        if (empty($company_id)) {
            throw  new AuthException(['msg' => '账户异常']);
        }
        $admin_id = Token::getCurrentUid();
        //调用存储过程，将账户清0
        /* $resultSet = Db::query('call clear_money(:in_companyId,:in_adminID)', [
             'in_companyId' => $company_id,
             'in_adminID' => $admin_id
         ]);*/
    }

    public function rechargeSupplement($params)
    {
        $admin_id = Token::getCurrentUid();
        $company_id = Token::getCurrentTokenVar('company_id');
        $staffs = explode(',', $params['staff_ids']);
        $dataList = array();
        foreach ($staffs as $k => $v) {
            //检测余额是否充足
            if ($params['type'] == 2) {
                $this->checkSupplementBalance($v, $params['canteen_id'], $params['money']);
            }

            $data = [
                'source' => 'save',
                'admin_id' => $admin_id,
                'company_id' => $company_id,
                'canteen_id' => $params['canteen_id'],
                'money' => $params['type'] == 1 ? $params['money'] : 0 - $params['money'],
                'type' => $params['type'],
                'staff_id' => $v,
                'consumption_date' => $params['consumption_date'],
                'remark' => empty($params['remark']) ? '' : $params['remark'],
                'dinner_id' => $params['dinner_id'],
                'account_id' => empty($params['account_id']) ? 0 : $params['account_id']
            ];
            array_push($dataList, $data);
        }
        $supplement = (new RechargeSupplementT())->saveAll($dataList);
        if (!$supplement) {
            throw new SaveException();
        }
    }

    private function checkSupplementBalance($staffId, $canteenId, $money)
    {
        $balance = (new WalletService())->getUserBalanceWithStaffId($staffId);
        if ($money > $balance) {
            //获取账户设置，检测是否可预支消费
            $canteenAccount = CanteenAccountT::where('c_id', $canteenId)->find();
            if (!$canteenAccount) {
                throw new ParameterException(['msg' => "账户余额不足，不能补扣"]);
            }

            if ($canteenAccount->type == OrderEnum::OVERDRAFT_NO) {
                throw new ParameterException(['msg' => "账户余额不足，不能补扣"]);
            }
            if ($canteenAccount->limit_money < ($money - $balance)) {
                throw new ParameterException(['msg' => "账户余额不足，不能补扣"]);
            }
        }

    }

    public function rechargeSupplementWithAccount($params)
    {
        $admin_id = Token::getCurrentUid();
        $company_id = Token::getCurrentTokenVar('company_id');
        $staffs = explode(',', $params['staff_ids']);
        $dataList = array();
        foreach ($staffs as $k => $v) {
            $data = [
                'source' => 'save',
                'admin_id' => $admin_id,
                'company_id' => $company_id,
                'canteen_id' => $params['canteen_id'],
                'money' => $params['type'] == 1 ? $params['money'] : 0 - $params['money'],
                'type' => $params['type'],
                'staff_id' => $v,
                'consumption_date' => $params['consumption_date'],
                'remark' => empty($params['remark']) ? '' : $params['remark'],
                'dinner_id' => $params['dinner_id'],
                'account_id' => empty($params['account_id']) ? 0 : $params['account_id']
            ];
            array_push($dataList, $data);
        }
        $supplement = (new RechargeSupplementT())->saveAll($dataList);
        if (!$supplement) {
            throw new SaveException();
        }
    }


    public function rechargeSupplementUploadWithAccount($supplement_excel)
    {
        $company_id = Token::getCurrentTokenVar('company_id');
        $admin_id = Token::getCurrentUid();
        $fileName = (new ExcelService())->saveExcelReturnName($supplement_excel);
        //$fileName = dirname($_SERVER['SCRIPT_FILENAME']) . '/static/excel/upload/test.xlsx';
        $fail = $this->checkSupplementDataWithAccount($company_id, $fileName);
        if (count($fail)) {
            return [
                'res' => false,
                'fail' => $fail
            ];
        }
        $this->uploadExcelTask($company_id, $admin_id, $fileName, "supplementWithAccount");
        return [
            'res' => true
        ];
    }

    public function rechargeSupplementUpload($supplement_excel)
    {
        $company_id = Token::getCurrentTokenVar('company_id');
        $admin_id = Token::getCurrentUid();
        $fileName = (new ExcelService())->saveExcelReturnName($supplement_excel);
        //$fileName = dirname($_SERVER['SCRIPT_FILENAME']) . '/static/excel/upload/test.xlsx';
        $fail = $this->checkSupplementData($company_id, $fileName);
        if (count($fail)) {
            return [
                'res' => false,
                'fail' => $fail
            ];
        }
        $this->uploadExcelTask($company_id, $admin_id, $fileName, "supplement");
        return [
            'res' => true
        ];
    }


    private function checkSupplementDataWithAccount($company_id, $fileName)
    {
        $newCanteen = [];
        $canteens = (new CanteenService())->companyCanteens($company_id);
        $dinners = DinnerV::companyDinners($company_id);
        $staffs = CompanyStaffT::staffs($company_id);
        $accounts = CompanyAccountT::accountsWithoutNonghang($company_id);
        $accountsArr = [];
        if (count($accounts)) {
            foreach ($accounts as $k => $v) {
                array_push($accountsArr, $v['name']);
            }
        }

        foreach ($canteens as $k => $v) {
            array_push($newCanteen, $v['name']);
        }
        if (!count($newCanteen) || !count($dinners)) {
            throw  new  SaveException(['msg' => '企业饭堂或者餐次设置异常']);
        }
        $newStaffs = [];
        $staffCanteens = [];

        foreach ($staffs as $k => $v) {
            array_push($newStaffs, $v['username'] . '&' . $v['phone']);
            $canteens = $v['canteens'];
            $staffCanteen = [];
            foreach ($canteens as $k2 => $v2) {
                if (!empty($v2['info']['name']) && strlen($v2['info']['name'])) {
                    array_push($staffCanteen, $v2['info']['name']);
                }
            }

            $staffCanteens[$v['username'] . '&' . $v['phone']] = $staffCanteen;

        }
        $fail = [];
        $data = (new ExcelService())->importExcel($fileName);
        foreach ($data as $k => $v) {
            if ($k < 2) {
                continue;
            }
            $checkData = $v[0] . '&' . $v[1];
            if (!in_array($checkData, $newStaffs) ||
                !in_array($v[2], $newCanteen) || !$this->checkDinnerInCanteen($v[2], $v[4], $dinners)) {
                array_push($fail, '第' . $k . '行数据有问题');
                break;
            }
            //检测饭堂是否合法
            $checkCanteens = $staffCanteens[$checkData];
            if (!in_array($v[2], $checkCanteens)) {
                array_push($fail, '第' . $k . '行数据有问题');
                break;
            }

            if (count($accountsArr) && !in_array($v[6], $accountsArr)) {
                array_push($fail, '第' . $k . '行数据有问题');

            }
            if (strtotime($v[3]) > strtotime(\date('Y-m-d')) || $v[7] < 0) {
                array_push($fail, '第' . $k . '行数据有问题');
                break;
            }

        }
        return $fail;
    }


    private function checkSupplementData($company_id, $fileName)
    {
        $newCanteen = [];
        $canteens = (new CanteenService())->companyCanteens($company_id);
        $dinners = DinnerV::companyDinners($company_id);
        $staffs = CompanyStaffT::staffs($company_id);

        foreach ($canteens as $k => $v) {
            array_push($newCanteen, $v['name']);
        }
        if (!count($newCanteen) || !count($dinners)) {
            throw  new  SaveException(['msg' => '企业饭堂或者餐次设置异常']);
        }
        $newStaffs = [];
        $staffCanteens = [];
        foreach ($staffs as $k => $v) {
            array_push($newStaffs, $v['username'] . '&' . $v['phone']);
            $canteens = $v['canteens'];
            $staffCanteen = [];
            foreach ($canteens as $k2 => $v2) {
                if (!empty($v2['info']['name']) && strlen($v2['info']['name'])) {
                    array_push($staffCanteen, $v2['info']['name']);
                }
            }
            array_push($staffCanteens, [
                $v['username'] . '&' . $v['phone'] => $staffCanteen
            ]);
        }
        $fail = [];
        $data = (new ExcelService())->importExcel($fileName);
        foreach ($data as $k => $v) {
            if ($k < 2) {
                continue;
            }
            $checkData = $v[0] . '&' . $v[1];
            if (!in_array($checkData, $newStaffs) ||
                !in_array($v[2], $newCanteen) || !$this->checkDinnerInCanteen($v[2], $v[4], $dinners)) {
                array_push($fail, '第' . $k . '行数据有问题');
                break;
            }

            //检测饭堂是否合法
            $checkCanteens = $staffCanteens[$checkData];
            if (!in_array($v[2], $checkCanteens)) {
                array_push($fail, '第' . $k . '行数据有问题');
                break;
            }

            if (strtotime($v[3]) > strtotime(\date('Y-m-d')) || $v[6] < 0) {
                array_push($fail, '第' . $k . '行数据有问题');
                break;
            }

        }
        return $fail;
    }

    private function checkDinnerInCanteen($canteen, $dinner, $dinners)
    {
        foreach ($dinners as $k => $v) {
            if ($v['dinner'] == $dinner && $v['canteen'] == $canteen) {
                return true;
                break;
            }
        }
        return false;

    }

    public function prefixSupplementUploadData($company_id, $admin_id, $data)
    {
        $dataList = [];
        $canteens = (new CanteenService())->companyCanteens($company_id);
        $dinners = DinnerV::companyDinners($company_id);
        $staffs = CompanyStaffT::staffs($company_id);
        $newStaffs = [];
        $newCanteen = [];

        foreach ($staffs as $k => $v) {
            $newStaffs[$v['phone']] = $v['id'];
        }
        foreach ($canteens as $k => $v) {
            $newCanteen[$v['name']] = $v['id'];
        }
        foreach ($data as $k => $v) {
            if ($k == 1) {
                continue;
            }
            array_push($dataList, [
                'admin_id' => $admin_id,
                'company_id' => $company_id,
                'staff_id' => $newStaffs[$v[1]],
                'source' => 'upload',
                'code' => '',
                'username' => $v[0],
                'card_num' => '',
                'phone' => $v[1],
                'canteen' => $v[2],
                'canteen_id' => $newCanteen[$v[2]],
                'consumption_date' => $this->getConsumptionDate($v[3]),
                'dinner_id' => $this->getDinnerID($dinners, $newCanteen[$v[2]], $v[4]),
                'dinner' => $v[4],
                'type' => $v[5] == "补扣" ? 2 : 1,
                'money' => $v[5] == "补扣" ? 0 - $v[6] : $v[6]
            ]);
        }

        return $dataList;
    }

    public function prefixSupplementUploadDataWithAccount($company_id, $admin_id, $data)
    {
        $dataList = [];
        $canteens = (new CanteenService())->companyCanteens($company_id);
        $dinners = DinnerV::companyDinners($company_id);
        $staffs = CompanyStaffT::staffs($company_id);
        $accounts = CompanyAccountT::accountsWithoutNonghang($company_id);

        $newStaffs = [];
        $newCanteen = [];
        $newAccounts = [];

        foreach ($accounts as $k => $v) {
            $newAccounts [$v['name']] = $v['id'];
        }

        foreach ($staffs as $k => $v) {
            $newStaffs[$v['phone']] = $v['id'];
        }
        foreach ($canteens as $k => $v) {
            $newCanteen[$v['name']] = $v['id'];
        }
        foreach ($data as $k => $v) {
            if ($k == 1) {
                continue;
            }
            array_push($dataList, [
                'admin_id' => $admin_id,
                'company_id' => $company_id,
                'staff_id' => $newStaffs[$v[1]],
                'account_id' => count($newAccounts) ? $newAccounts[$v[6]] : 0,
                'source' => 'upload',
                'code' => '',
                'username' => $v[0],
                'card_num' => '',
                'phone' => $v[1],
                'canteen' => $v[2],
                'canteen_id' => $newCanteen[$v[2]],
                'consumption_date' => $this->getConsumptionDate($v[3]),
                'dinner_id' => $this->getDinnerID($dinners, $newCanteen[$v[2]], $v[4]),
                'dinner' => $v[4],
                'type' => $v[5] == "补扣" ? 2 : 1,
                'money' => $v[5] == "补扣" ? 0 - $v[7] : $v[7]
            ]);
        }

        return $dataList;
    }

    private function getConsumptionDate($value)
    {
        return gmdate("Y-m-d", ($value - 25569) * 86400);

    }

    private function getCanteenID($canteens, $canteen)
    {
        $canteenId = '';
        if (!count($canteens)) {
            return $canteenId;
        }
        foreach ($canteens as $k => $v) {
            if ($v['name'] == $canteen) {
                $canteenId = $v['id'];
                break;
            }

        }
        return $canteenId;
    }

    private function getDinnerID($dinners, $canteen_id, $dinner)
    {
        $dinnerID = '';
        if (!count($dinners)) {
            return $dinnerID;
        }
        foreach ($dinners as $k => $v) {
            if ($v['canteen_id'] == $canteen_id && $v['dinner'] == $dinner) {
                $dinnerID = $v['dinner_id'];
                break;
            }
        }
        return $dinnerID;

    }

    public function saveOrder($params)
    {
        $company_id = Token::getCurrentTokenVar('current_company_id');
        $openid = Token::getCurrentOpenid();
        $u_id = Token::getCurrentUid();
        $phone = Token::getCurrentTokenVar('phone');
        $staff = (new UserService())->getUserCompanyInfo($phone, $company_id);
        $data = [
            'openid' => $openid,
            'company_id' => $company_id,
            'u_id' => $u_id,
            'order_num' => time(),
            'money' => $params['money'],
            'method_id' => $params['method_id'],
            'staff_id' => $staff->id,
            'type' => 'recharge',
            'username' => $staff->username,
            'phone' => $phone
        ];
        $order = PayT::create($data);
        if (!$order) {
            throw new SaveException();
        }
        return [
            'id' => $order->id
        ];
    }

    public function getPreOrder($order_id)
    {
        //$openid = "oSi030qTHU0p3vD4um68F4z2rdHU";//Token::getCurrentOpenid();
        $openid = Token::getCurrentOpenid();
        $status = $this->checkOrderValid($order_id, $openid);
        $method_id = $status['methodID'];
        $company_id = $status['companyID'];
        switch ($method_id) {
            case PayEnum::PAY_METHOD_WX:
                return $this->getPreOrderForWX($status['orderNumber'], $status['orderPrice'], $openid, $company_id);
                break;
            default:
                throw new ParameterException();
        }
    }

    public function getPreOrderForOrder($order_id)
    {
        // $openid = "oSi030oELLvP4suMSvOxTAF8HrLE";//Token::getCurrentOpenid();
        $openid = Token::getCurrentOpenid();
        $status = $this->checkOrderValid($order_id, $openid);
        $method_id = $status['method_id'];
        $company_id = $status['companyID'];
        switch ($method_id) {
            case PayEnum::PAY_METHOD_WX:
                return $this->getPreOrderForWX($status['orderNumber'], $status['orderPrice'], $openid, $company_id);
                break;
            default:
                throw new ParameterException();
        }
    }


    private function getPreOrderForWX($orderNumber, $orderPrice, $openid, $company_id)
    {

        $data = [
            'company_id' => $company_id,
            'openid' => $openid,
            'total_fee' => $orderPrice * 100,//转换单位为分
            'body' => '云饭堂充值中心-点餐充值',
            'out_trade_no' => $orderNumber
        ];
        $wxOrder = (new WeiXinPayService())->getPayInfo($data);
        if (empty($wxOrder['result_code']) || $wxOrder['result_code'] != 'SUCCESS' || $wxOrder['return_code'] != 'SUCCESS') {
            LogService::save(json_encode($wxOrder));
            throw new ParameterException(['msg' => '获取微信支付信息失败']);
        }
        return $wxOrder;


    }

    private
    function checkOrderValid($order_id, $openid)
    {
        $order = PayT::get($order_id);

        if (!$order) {
            throw new ParameterException(['msg' => '订单不存在']);
        }
        if ($order->state == CommonEnum::STATE_IS_FAIL) {
            throw new ParameterException(['msg' => '订单已经取消，不能支付']);
        }
        if (!empty($order->pay_id)) {
            throw new ParameterException(['msg' => '订单已经支付，不能重复支付']);
        }
        if ($openid != $order->openid) {
            throw new ParameterException(['msg' => '用户与订单不匹配']);
        }
        $status = [
            'methodID' => $order->method_id,
            'orderNumber' => $order->order_num,
            'orderPrice' => $order->money,
            'companyID' => $order->company_id
        ];

        return $status;
    }

    private
    function checkOrderValidToOutsider($order_id, $openid)
    {
        $order = PayT::get($order_id);

        if (!$order) {
            throw new ParameterException(['msg' => '订单不存在']);
        }
        if ($order->state == CommonEnum::STATE_IS_FAIL) {
            throw new ParameterException(['msg' => '订单已经取消，不能支付']);
        }
        if (!empty($order->pay_id)) {
            throw new ParameterException(['msg' => '订单已经支付，不能重复支付']);
        }
        if ($openid != $order->openid) {
            throw new ParameterException(['msg' => '用户与订单不匹配']);
        }
        $status = [
            'method_id' => $order->method_id,
            'orderNumber' => $order->order_num,
            'orderPrice' => $order->money,
            'companyID' => $order->company_id
        ];

        return $status;
    }

    public function paySuccess($order_id, $order_type, $times)
    {
        if ($times == 'one') {
            if ($order_type == "canteen") {
                OrderT::update([
                    'pay' => 'paid'
                ], ['id' => $order_id]);
            }
        } else if ($times == 'more') {
            OrderParentT::update([
                'pay' => 'paid'
            ], ['id' => $order_id]);
        }

    }
}