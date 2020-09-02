<?php


namespace app\api\service;


use app\api\model\CanteenT;
use app\api\model\CompanyDepartmentT;
use app\api\model\CompanyStaffT;
use app\api\model\CompanyStaffV;
use app\api\model\DepartmentV;
use app\api\model\StaffCanteenT;
use app\api\model\StaffQrcodeT;
use app\api\model\StaffV;
use app\lib\enum\CommonEnum;
use app\lib\exception\DeleteException;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;
use app\lib\exception\UpdateException;
use http\Params;
use think\Db;
use think\Exception;
use think\Request;
use function GuzzleHttp\Promise\each_limit;
use function GuzzleHttp\Psr7\str;

class DepartmentService
{
    public function save($params)
    {
        if ($this->checkExit($params['c_id'], $params['name'])) {
            throw new SaveException(['msg' => '部门：' . $params['name'] . '已存在']);
        }
        $params['state'] = CommonEnum::STATE_IS_OK;
        $department = CompanyDepartmentT::create($params);
        if (!$department) {
            throw new SaveException();
        }
        return $department->id;
    }

    private function checkExit($company_id, $name)
    {
        $department = CompanyDepartmentT::where('c_id', $company_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->where('name', $name)
            ->count('id');
        return $department;

    }

    public function deleteDepartment($id)
    {
        if ($this->checkDepartmentCanDelete($id)) {
            throw new DeleteException(['msg' => '删除操作失败，该部门有子部门或者有员工']);
        }
        $res = CompanyDepartmentT::update(['state' => CommonEnum::STATE_IS_FAIL], ['id' => $id]);
        if (!$res) {
            throw new DeleteException();
        }
    }

    private function checkDepartmentCanDelete($id)
    {
        $staff = CompanyStaffT::where('d_id', $id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->count('id');
        if ($staff) {
            return true;
        }
        $son = CompanyDepartmentT::where('parent_id', $id)
            ->where('state',CommonEnum::STATE_IS_OK)
            ->count('id');
        if ($son) {
            return true;
        }
        return false;

    }

    public function departments($c_id)
    {
        if (empty($c_id)) {
            $c_id = Token::getCurrentTokenVar('company_id');
        }
        $departments = DepartmentV::departments($c_id);
        return getTree($departments);
    }

    public function addStaff($params)
    {
        try {
            Db::startTrans();
            $this->checkStaffExits($params['company_id'], $params['phone']);
            $params['state'] = CommonEnum::STATE_IS_OK;
            $staff = CompanyStaffT::create($params);
            if (!$staff) {
                throw new SaveException();
            }
            //保存用户饭堂绑定关系
            $this->saveStaffCanteen($staff->id, $params['canteens']);
            //保存二维码
            $this->saveQrcode($staff->id);
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    private function checkStaffExits($company_id, $phone)
    {
        $staff = CompanyStaffT::where('company_id', $company_id)
            ->where('phone', $phone)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->count('id');
        if ($staff) {
            throw  new SaveException(['msg' => '该用户已存在']);
        }

    }

    public function updateStaff($params)
    {
        try {
            Db::startTrans();
            if (key_exists('expiry_date', $params)) {
                $qrcode = StaffQrcodeT::update(['expiry_date' => $params['expiry_date']], ['s_id' => $params['id']]);
                if (!$qrcode) {
                    throw new UpdateException(['msg' => '更新二维码有效期失败']);
                }
            }
            $staff = CompanyStaffT::update($params);
            if (!$staff) {
                throw new UpdateException();
            }
            //更新用户饭堂绑定关系
            $canteens = empty($params['canteens']) ? [] : json_decode($params['canteens'], true);
            $cancel_canteens = empty($params['cancel_canteens']) ? [] : json_decode($params['cancel_canteens'], true);
            $this->updateStaffCanteen($staff->id, $canteens, $cancel_canteens);
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    private function saveStaffCanteen($staff_id, $canteens)
    {
        $canteens = json_decode($canteens, true);
        if (empty($canteens)) {
            throw new ParameterException(['msg' => '字段饭堂id，参数格式错误']);
        }
        $data_list = [];
        foreach ($canteens as $k => $v) {
            $data_list[] = [
                'staff_id' => $staff_id,
                'canteen_id' => $v['canteen_id'],
                'state' => CommonEnum::STATE_IS_OK
            ];
        }
        $res = (new StaffCanteenT())->saveAll($data_list);
        if (!$res) {
            throw new SaveException(['msg' => '添加饭堂用户关系失败']);
        }

    }

    private function updateStaffCanteen($staff_id, $canteens, $cancel_canteens)
    {
        $data_list = [];
        if (!empty($canteens)) {
            foreach ($canteens as $k => $v) {
                $data_list[] = [
                    'staff_id' => $staff_id,
                    'canteen_id' => $v,
                    'state' => CommonEnum::STATE_IS_OK
                ];
            }
        }
        if (!empty($cancel_canteens)) {
            foreach ($cancel_canteens as $k => $v) {
                $data_list[] = [
                    'id' => $v,
                    'state' => CommonEnum::STATE_IS_FAIL
                ];
            }
        }
        $res = (new StaffCanteenT())->saveAll($data_list);
        if (!$res) {
            throw new SaveException(['msg' => '更新饭堂用户关系失败']);
        }

    }

    public function uploadStaffs($company_id, $staffs_excel)
    {
        $date = (new ExcelService())->saveExcel($staffs_excel);
        $res = $this->prefixStaffs($company_id, $date);
        return $res;
    }

    private function prefixStaffs($company_id, $data)
    {
        $types = (new AdminService())->allTypes();
        $canteens = (new CanteenService())->companyCanteens($company_id);
        $departments = $this->companyDepartments($company_id);
        $phones = $this->getCompanyStaffsPhone($company_id);
        $fail = array();
        $success = array();
        $param_key = array();
        if (count($data) < 2) {
            return [];
        }

        foreach ($data as $k => $v) {
            if ($k == 1) {
                $param_key = $data[$k];
            } else if ($k > 1 && !empty($data[$k])) {
                if (empty($v[0])) {
                    continue;
                }
                //检测手机号是否已经存在
                if (in_array($v[5], $phones)) {
                    $fail[] = "第" . $k . "数据有问题：手机号" . $v[5] . "系统已经存在";
                    break;
                } else if (!$this->isMobile($v[5])) {
                    $fail[] = "第" . $k . "数据有问题：手机号格式错误";
                    break;
                } else {
                    array_push($phones, $v[5]);
                }
                $check = $this->validateParams($company_id, $param_key, $data[$k], $types, $canteens, $departments);
                if (!$check['res']) {
                    $fail[] = "第" . $k . "数据有问题：" . $check['info']['msg'];
                    continue;
                }
                $success[] = $check['info'];
            }

        }

        if (count($success)) {
            $all = (new CompanyStaffT())->saveAll($success);
            if (!$all) {
                throw  new SaveException();
            }

            /*  $info = $this->getUploadStaffQrcodeAndCanteenInfo($all);
              $qrcodeInfo = $info['qrcode'];
              $canteenInfo = $info['canteen'];
              $qrcods = (new StaffQrcodeT())->saveAll($qrcodeInfo);
              if (!$qrcods) {
                  throw  new SaveException();
              }
              $canteens = (new StaffCanteenT())->saveAll($canteenInfo);
              if (!$canteens) {
                  throw  new SaveException();
              }*/

        }
        return [
            'fail' => $fail
        ];


    }

    private function isMobile($value)
    {
        $rule = '^1[0-9][0-9]\d{8}$^';
        $result = preg_match($rule, $value);
        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    private function getCompanyStaffsPhone($company_id)
    {
        $staffs = CompanyStaffT::staffs($company_id);
        $staffsPhone = [];
        foreach ($staffs as $k => $v) {
            array_push($staffsPhone, $v['phone']);
        }
        return $staffsPhone;
    }

    private function validateParams($company_id, $param_key, $data, $types, $canteens, $departments, $len = 7)
    {
        $state = ['启用', '停用'];
        foreach ($data as $k => $v) {
            if ($k >= $len) {
                break;
            }
            if (!strlen($v)) {
                $fail = [
                    'name' => $data[4],
                    'msg' => "参数：$param_key[$k]" . " 为空"
                ];
                return [
                    'res' => false,
                    'info' => $fail
                ];
                break;
            }
        }

        $canteen = trim($data[0]);
        $department = trim($data[1]);
        $staffType = trim($data[2]);
        $code = trim($data[3]);
        $name = trim($data[4]);
        $phone = trim($data[5]);
        $card_num = trim($data[6]);
        $canteen_ids = [];

        if (!in_array($data[7], $state)) {
            $fail = [
                'name' => $name,
                'msg' => '状态错误'
            ];
            return [
                'res' => false,
                'info' => $fail
            ];
        }
        $state = trim($data[7]) == "启用" ? 1 : 2;
        //判断饭堂是否存在
        $canteen_arr = explode('|', $canteen);
        if (empty($canteen_arr)) {
            $fail = [
                'name' => $name,
                'msg' => '饭堂字段为空'
            ];
            return [
                'res' => false,
                'info' => $fail
            ];
        }

        foreach ($canteen_arr as $k => $v) {
            $c_id = $this->checkParamExits($canteens, $v);
            if (!$c_id) {
                $fail = [
                    'name' => $name,
                    'msg' => '企业中不存在该饭堂：' . $v
                ];
                return [
                    'res' => false,
                    'info' => $fail
                ];
                break;
            }
            array_push($canteen_ids, $c_id);
        }

        //判断人员类型是否存在
        $t_id = $this->checkParamExits($types, $staffType);
        if (!$t_id) {
            $fail = [
                'name' => $name,
                'msg' => '系统中不存在该人员类型：' . $staffType
            ];
            return [
                'res' => false,
                'info' => $fail
            ];
        }
        //检测部门是否存在
        $d_id = $this->checkParamExits($departments, $department);
        if (!$d_id) {
            $fail = [
                'name' => $name,
                'msg' => '企业中不存在该部门：' . $department
            ];
            return [
                'res' => false,
                'info' => $fail
            ];
        }

        return [
            'res' => true,
            'info' => [
                'd_id' => $d_id,
                't_id' => $t_id,
                'code' => $code,
                'username' => $name,
                'phone' => $phone,
                'card_num' => $card_num,
                'company_id' => $company_id,
                'canteen_ids' => implode(',', $canteen_ids),
                'state' => $state
            ]
        ];
    }

    private function checkParamExits($list, $current_data)
    {
        if (!count($list)) {
            return 0;
        }
        foreach ($list as $k => $v) {
            if ($v['name'] == $current_data) {
                return $v['id'];
            }
        }
        return 0;

    }

    private function companyDepartments($company_id)
    {
        $departs = CompanyDepartmentT::where('c_id', $company_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->field('id,name')
            ->select()->toArray();
        return $departs;
    }

    private function getUploadStaffQrcodeAndCanteenInfo($staffs)
    {
        $list = array();
        $staff_canteen_list = array();
        foreach ($staffs as $k => $v) {
            $code = getRandChar(12);
            $url = sprintf(config("setting.qrcode_url"), 'canteen', $code);
            $qrcode_url = (new QrcodeService())->qr_code($url);
            $list[] = [
                'code' => $code,
                's_id' => $v->id,
                'expiry_date' => date('Y-m-d H:i:s', strtotime('+' . config("setting.qrcode_expire_in") . 'minute')),
                'url' => $qrcode_url
            ];

            $canteen_ids = $v->canteen_ids;
            $canteen_arr = explode(',', $canteen_ids);
            if (!empty($canteen_arr)) {
                foreach ($canteen_arr as $k2 => $v2) {
                    $staff_canteen_list[] = [
                        'staff_id' => $v->id,
                        'canteen_id' => $v2,
                        'state' => CommonEnum::STATE_IS_OK
                    ];
                }
            }
        }
        return [
            'qrcode' => $list,
            'canteen' => $staff_canteen_list
        ];

    }

    public
    function saveQrcode($s_id)
    {
        $code = QRcodeNUmber();
        $url = sprintf(config("setting.qrcode_url"), 'canteen', $code);
        $qrcode_url = (new QrcodeService())->qr_code($url);
        $expiry_date = date('Y-m-d H:i:s', strtotime("+" . config("setting.qrcode_expire_in") . "minute", time()));
        $data = [
            'code' => $code,
            's_id' => $s_id,
            'minute' => config("setting.qrcode_expire_in"),
            'expiry_date' => $expiry_date,
            'url' => $qrcode_url
        ];
        $qrcode = StaffQrcodeT::create($data);
        if (!$qrcode) {
            throw new SaveException();
        }
        return $qrcode_url;
    }


    public
    function saveQrcode2($s_id)
    {
        $code = QRcodeNUmber();
        $url = sprintf(config("setting.qrcode_url"), 'canteen', $code);
        $qrcode_url = (new QrcodeService())->qr_code($url);
        $expiry_date = date('Y-m-d H:i:s', strtotime("+" . config("setting.qrcode_expire_in") . "minute", time()));
        $data = [
            'code' => $code,
            's_id' => $s_id,
            'minute' => config("setting.qrcode_expire_in"),
            'expiry_date' => $expiry_date,
            'url' => $qrcode_url
        ];
        $qrcode = StaffQrcodeT::create($data);
        if (!$qrcode) {
            throw new SaveException();
        }
        return [
            'url' => $qrcode->url,
            'create_time' => $qrcode->create_time,
            'expiry_date' => $qrcode->expiry_date
        ];
    }


    public
    function updateQrcode2($params)
    {
        $code = QRcodeNUmber();
        $staff_id = $params['id'];
        unset($params['id']);
        $url = sprintf(config("setting.qrcode_url"), 'canteen', $code);
        $qrcode_url = (new QrcodeService())->qr_code($url);
        $params['code'] = $code;
        $params['url'] = $qrcode_url;
        $expiry_date = date('Y-m-d H:i:s', time());
        $params['create_time'] = $expiry_date;
        $params['expiry_date'] = $this->prefixQrcodeExpiryDate($expiry_date, $params);
        $staffQRCode = StaffQrcodeT::where('s_id', $staff_id)->find();
        if ($staffQRCode) {
            $qrcode = StaffQrcodeT::update($params, ['s_id' => $staff_id]);
        } else {
            $params['s_id'] = $staff_id;
            $qrcode = StaffQrcodeT::create($params);
        }
        if (!$qrcode) {
            throw new SaveException();
        }
        $staff = CompanyStaffT::get($staff_id);
        return [
            'usernmae' => $staff->username,
            'url' => $qrcode->url,
            'create_time' => $qrcode->create_time,
            'expiry_date' => $qrcode->expiry_date
        ];
    }


    public
    function updateQrcode($params)
    {
        $code = QRcodeNUmber();
        $url = sprintf(config("setting.qrcode_url"), 'canteen', $code, $params['s_id']);
        $qrcode_url = (new QrcodeService())->qr_code($url);
        $s_id = $params['id'];
        $params['code'] = $code;
        $params['url'] = $qrcode_url;
        $expiry_date = date('Y-m-d H:i:s', time());
        $params['create_time'] = $expiry_date;
        $params['expiry_date'] = $this->prefixQrcodeExpiryDate($expiry_date, $params);
        $qrcode = StaffQrcodeT::update($params);
        if (!$qrcode) {
            throw new SaveException();
        }
        $staff = CompanyStaffT::get($s_id);
        return [
            'usernmae' => $staff->username,
            'url' => $qrcode->url,
            'create_time' => $qrcode->create_time,
            'expiry_date' => $qrcode->expiry_date
        ];
    }

    public
    function updateQrcode3($params)
    {
        $code = QRcodeNUmber();
        $url = sprintf(config("setting.qrcode_url"), 'canteen', $code, $params['s_id']);
        $qrcode_url = (new QrcodeService())->qr_code($url);
        $s_id = $params['staff_id'];
        $params['code'] = $code;
        $params['url'] = $qrcode_url;
        $expiry_date = date('Y-m-d H:i:s', time());
        $params['create_time'] = $expiry_date;
        $params['expiry_date'] = $this->prefixQrcodeExpiryDate($expiry_date, $params);
        $qrcode = StaffQrcodeT::update($params);
        if (!$qrcode) {
            throw new SaveException();
        }
        $staff = CompanyStaffT::get($s_id);
        return [
            'usernmae' => $staff->username,
            'url' => $qrcode->url,
            'create_time' => $qrcode->create_time,
            'expiry_date' => $qrcode->expiry_date
        ];
    }


    public
    function companyStaffs($page, $size, $c_id, $d_id)
    {
        $staffs = CompanyStaffV::companyStaffs($page, $size, $c_id, $d_id);
        return $staffs;
    }

    public function exportStaffs($company_id, $department_id)
    {
        $staffs = CompanyStaffV::exportStaffs($company_id, $department_id);
        $staffs = $this->prefixExportStaff($staffs);
        $header = ['企业', '部门', '人员状态', '人员类型', '员工编号', '姓名', '手机号码', '卡号', '归属饭堂'];
        $file_name = "企业员工导出";
        $url = (new ExcelService())->makeExcel($header, $staffs, $file_name);
        return [
            'url' => config('setting.domain') . $url
        ];
    }

    private function prefixExportStaff($staffs)
    {
        if (!count($staffs)) {
            return $staffs;
        }
        foreach ($staffs as $k => $v) {
            $canteen = [];
            unset($staffs[$k]['id']);
            $canteens = $v['canteens'];
            unset($staffs[$k]['canteens']);
            foreach ($canteens as $k2 => $v2) {
                array_push($canteen, $v2['info']['name']);
            }
            $staffs[$k]['canteen'] = implode('|', $canteen);
            $staffs[$k]['state'] = $v['state'] == 1 ? '启用' : '停用';
        }
        return $staffs;

    }

    private
    function prefixQrcodeExpiryDate($expiry_date, $params)
    {
        $type = ['minute', 'hour', 'day', 'month', 'year'];
        $exit = 0;
        foreach ($type as $k => $v) {
            if (key_exists($v, $params) && !empty($params[$v])) {
                $exit = 1;
                $expiry_date = date('Y-m-d H:i:s', strtotime("+" . $params[$v] . "$v", strtotime($expiry_date)));
            }
        }
        if (!$exit) {
            $expiry_date = date('Y-m-d H:i:s', strtotime("+" . config("setting.qrcode_expire_in") . "minute", strtotime($expiry_date)));

        }
        return $expiry_date;
    }

    public
    function departmentStaffs($d_ids)
    {
        $staffs = CompanyStaffT::departmentStaffs($d_ids);
        return $staffs;
    }

    public
    function getStaffWithPhone($phone, $company_id)
    {
        $staff = CompanyStaffT::getStaffWithPhone($phone, $company_id);
        return $staff;

    }

    public
    function getCompanyStaffCounts($company_id)
    {
        $count = CompanyStaffT::getCompanyStaffCounts($company_id);
        return $count;
    }

    public function adminDepartments()
    {
        if (Token::getCurrentTokenVar('type') == 'official') {
            $company_id = Token::getCurrentTokenVar('current_company_id');

        } else {
            $company_id = Token::getCurrentTokenVar('company_id');

        }
        $departments = CompanyDepartmentT::adminDepartments($company_id);
        return $departments;
    }

    public function departmentsForRecharge()
    {
        $company_id = Token::getCurrentTokenVar('company_id');
        $departments = CompanyDepartmentT::adminDepartments($company_id);
        return $departments;
    }

    public function staffsForRecharge($page, $size, $department_id, $key)
    {
        $company_id = Token::getCurrentTokenVar('company_id');
        $staffs = CompanyStaffV:: staffsForRecharge($page, $size, $department_id, $key, $company_id);
        return $staffs;
    }

    public function searchStaff($page, $size, $company_id, $department_id, $key)
    {
        $staffs = CompanyStaffV::searchStaffs($page, $size, $company_id, $department_id, $key);
        return $staffs;
    }


}