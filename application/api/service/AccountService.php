<?php


namespace app\api\service;


use app\api\controller\v1\Account;
use app\api\model\AccountDepartmentT;
use app\api\model\AccountRecordsT;
use app\api\model\AccountRecordsV;
use app\api\model\CompanyAccountT;
use app\api\model\CompanyStaffT;
use app\api\model\ConsumptionRecordsV;
use app\api\model\OrderConsumptionV;
use app\api\model\OrderParentT;
use app\api\model\OrderSubT;
use app\api\model\OrderT;
use app\api\model\PayNonghangConfigT;
use app\api\model\PayT;
use app\api\model\UserBalanceV;
use app\api\validate\Company;
use app\lib\enum\CommonEnum;
use app\lib\enum\PayEnum;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;
use app\lib\exception\UpdateException;
use http\Env\Request;
use MongoDB\BSON\Type;
use think\Db;
use think\Exception;
use think\Queue;
use function EasyWeChat\Kernel\data_to_array;
use function GuzzleHttp\Promise\all;

class AccountService
{

    private $clearNo = 1;
    private $clearCycle = 2;
    private $clearDay = 3;

    public function save($params)
    {
        Db::startTrans();
        try {
            $this->checkExits($params["company_id"], $params["name"]);
            $adminID = Token::getCurrentTokenVar('u_id');
            $params['admin_id'] = $adminID;
            $params['fixed_type'] = 2;
            $dayCount = empty($params['day_count']) ? 0 : $params['day_count'];
            $timeBegin = empty($params['time_begin']) ? 0 : $params['time_begin'];
            $clearType = empty($params['clear_type']) ? 0 : $params['clear_type'];
            $params['next_time'] = $this->getNextClearTime($params['clear'], $clearType,
                $params['first'], $params['end'],
                $dayCount, $timeBegin);
            $account = CompanyAccountT::create($params);

            if (!$account) {
                throw new SaveException();
            }
            if (!empty($params['account_sort'])) {
                $accountSort = $params['account_sort'];
                $update = CompanyAccountT::update($accountSort);
                if (!$update) {
                    throw new UpdateException();
                }
            }
            if (!empty($params['departments'])) {
                $departments = json_decode($params['departments'], true);
                $this->saveDepartments($account->id, $departments);
            }
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    public function checkExits($companyId, $name, $accountId = 0)
    {
        $account = CompanyAccountT::where('company_id', $companyId)
            ->where('name', $name)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->find();
        if ($account && $account->id !== $accountId) {
            throw new UpdateException(['msg' => "账户名已存在"]);
        }


    }


    private function saveDepartments($accountId, $add, $cancel = [])
    {
        $data = [];
        if (!empty($add)) {
            foreach ($add as $k => $v) {
                array_push($data, [
                    'account_id' => $accountId,
                    'department_id' => $v,
                    'state' => CommonEnum::STATE_IS_OK
                ]);
            }
        }

        if (!empty($cancel)) {
            foreach ($cancel as $k => $v) {
                array_push($data, [
                    'id' => $v,
                    'state' => CommonEnum::STATE_IS_FAIL
                ]);
            }
        }
        $accountDepartment = (new AccountDepartmentT())->saveAll($data);
        if (!$accountDepartment) {
            throw new SaveException();
        }
    }

    public function account($id)
    {
        $account = CompanyAccountT::account($id);
        if (!$account) {
            throw new ParameterException(['msg' => '账户不存在']);
        }
        $allAccount = CompanyAccountT::accountsWithSorts($account->company_id);
        $account['allSort'] = $allAccount;
        return $account;

    }

    public function accounts($companyId)
    {
        //检测是否有基本账户：个人账户和农行账户
        //1.查看是否有基本户
        //1.查看是否有农行
        if (empty($companyId)) {
            $companyId = Token::getCurrentTokenVar('company_id');
        }
        if (empty($companyId)) {
            throw new ParameterException(['msg' => "没有归属企业"]);
        }
        $accounts = CompanyAccountT::accounts($companyId);
        if ($accounts->isEmpty()) {
            $this->saveFixedAccount($companyId, 1);
            //检测是否开通农行
            if ($this->checkNongHang($companyId)) {
                $this->saveFixedAccount($companyId, 2);
            }

        } else {
            $fixedPerson = false;
            $fixedNongHang = false;
            foreach ($accounts as $k => $v) {
                if ($v['type'] == 1 && $v['fixed_type'] == 1) {
                    $fixedPerson = true;
                }
                if ($v['type'] == 1 && $v['fixed_type'] == 2) {
                    $fixedNongHang = true;
                }
            }

            if (!$fixedPerson) {
                $this->saveFixedAccount($companyId, 1);
            }
            if (!$fixedNongHang && $this->checkNongHang($companyId)) {
                $this->saveFixedAccount($companyId, 2);
            }
        }

        $accounts = CompanyAccountT::accounts($companyId);
        return $accounts;

    }

    private function checkNongHang($companyId)
    {
        $config = PayNonghangConfigT::config($companyId);
        if ($config) {
            return true;
        }
        return false;

    }

    public function saveFixedAccount($companyId, $fixedType)
    {
        //检测账户是否已经存在
        $account = CompanyAccountT::where('company_id', $companyId)
            ->where('type', 1)
            ->where('fixed_type', $fixedType)
            ->where('state', CommonEnum::STATE_IS_OK)->find();
        if ($account) {
            return true;
        }
        $accountName = [
            1 => '个人账户',
            2 => '农行账户'
        ];
        $data = [
            'company_id' => $companyId,
            'type' => 1,
            'department_all' => 1,
            'name' => $accountName[$fixedType],
            'fixed_type' => $fixedType,
            'clear' => CommonEnum::STATE_IS_FAIL,
            'sort' => $fixedType,
            'state' => CommonEnum::STATE_IS_OK
        ];
        if (!CompanyAccountT::create($data)) {
            throw new SaveException(['msg' => "新增基本账户失败"]);
        }

    }

    public function handle($id, $state)
    {
        $account = CompanyAccountT::get($id);
        if (!$account) {
            throw new ParameterException(['msg' => '账户不存在']);
        }
        if ($account->type == 1) {
            throw new UpdateException(['msg' => '基本账户不能修改']);
        }
        if ($state == CommonEnum::STATE_IS_FAIL) {
            $this->checkAccountBalance($id);
        }
        $account->state = $state;
        $account->update_time = date('Y-m-d H:i:s');
        $res = $account->save();
        if (!$res) {
            throw new UpdateException();
        }
    }

    private function checkAccountBalance($accountId)
    {
        //检测账户余额是否为0
        $balance = AccountRecordsT::checkAccountBalance($accountId);
        if ($balance) {
            throw new ParameterException(['msg' => '该账户余额未全部清零，停用失败！']);
        }

    }

    private function getNextClearTime($clear, $clearType, $first, $end, $dayCount, $time_begin)
    {
        if ($clear == $this->clearNo) {
            return '';
        }
        if ($clearType == "day") {
            return addDay($dayCount, $time_begin) . ' ' . "23:59";
        }
        if ($clearType == "week") {
            if ($first == CommonEnum::STATE_IS_OK) {
                if (date('w') == 1) {

                    return addDay(7, date('Y-m-d')) . ' ' . "00:01";
                } else {
                    return date('Y-m-d', strtotime('+1 week last monday')) . ' ' . "00:01";
                }
            } else if ($end == CommonEnum::STATE_IS_OK) {
                if (date('w') == 0) {
                    return date('Y-m-d') . ' ' . "23:59";
                } else {
                    return date('Y-m-d', strtotime('+1 week last sunday')) . ' ' . "23:59";
                }
            }
        } else if ($clearType == "month") {
            if ($first == CommonEnum::STATE_IS_OK) {
                $nextMonthBegin = date('Y-m-01', strtotime('+1 month'));
                return $nextMonthBegin . ' ' . "00:01";
            } else if ($end == CommonEnum::STATE_IS_OK) {
                $monthBegin = date('Y-m-01');
                return date('Y-m-d', strtotime("$monthBegin +1 month -1 day")) . ' ' . "23:59";
            }

        } else if ($clearType == "quarter") {
            $season = ceil((date('n')) / 3);

            if ($first == CommonEnum::STATE_IS_OK) {
                $nextQuarterBegin = date('Y-m-01', mktime(0, 0, 0, ($season) * 3 + 1, 1, date('Y')));
                return $nextQuarterBegin . ' ' . "00:01";
            } else if ($end == CommonEnum::STATE_IS_OK) {
                return date('Y-m-d', mktime(23, 59, 59, $season * 3,
                    date('t', mktime(0, 0, 0, $season * 3, 1,
                        date("Y"))), date('Y')));
            }

        } else if ($clearType == "year") {
            $nextYearBegin = date('Y-01-01', strtotime('+1 year'));

            if ($first == CommonEnum::STATE_IS_OK) {
                return $nextYearBegin . ' ' . "00:01";
            } else if ($end == CommonEnum::STATE_IS_OK) {
                return reduceDay(1, $nextYearBegin) . ' ' . "23:59";
            }
        }
    }

    public function update($params)
    {
        Db::startTrans();
        try {
            $params['next_time'] = $this->getNextClearTime($params['clear'], $params['clear_type'],
                $params['first'], $params['end'],
                $params['day_count'], $params['time_begin']);
            $account = CompanyAccountT::update($params);
            if (!$account) {
                throw new UpdateException();
            }
            if (!empty($params['departments'])) {
                $departments = json_decode($params['departments'], true);
                $add = [];
                $cancel = [];
                if (!empty($departments['add'])) {
                    $add = $departments['add'];
                }
                if (!empty($departments['cancel'])) {
                    $cancel = $departments['cancel'];
                }
                $this->saveDepartments($params['id'], $add, $cancel);
            }

            if (!empty($params['account_sort'])) {
                $accountSort = $params['account_sort'];
                $accountSortArr = json_decode($accountSort, true);
                $dataList = [];
                foreach ($accountSortArr as $k => $v) {
                    array_push($dataList, [
                        'id' => $v['id'],
                        'sort' => $v['sort']
                    ]);

                }
                $update = (new CompanyAccountT())->saveAll($dataList);
                if (!$update) {
                    throw new UpdateException();
                }
            }

            Db::commit();

        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    public function accountsForSearch($companyId)
    {
        $accounts = CompanyAccountT::accountForSearch($companyId);
        return $accounts;
    }

    public function accountBalance($company_id, $staff_id)
    {
        //消费统计
        $statistic = AccountRecordsT::statistic($staff_id);
        $accounts = CompanyAccountT::accountsWithSorts($company_id);
        foreach ($accounts as $k => $v) {
            $money = 0;
            foreach ($statistic as $k2 => $v2) {
                if ($v2['account_id'] == $v['id']) {
                    $money = $v2['money'];
                    break;
                }
            }
            $accounts[$k]['balance'] = $money;
        }
        return $accounts;

    }

    public function saveAccountRecords($consumptionDate, $canteenId, $money, $type, $orderId, $companyId, $staffId, $typeName, $outsider = 2)
    {
        $staff = CompanyStaffT::where('id', $staffId)->find();
        $accounts = $this->getAccountBalance($companyId, $staffId, $staff->d_id);
        $data = [];
        foreach ($accounts as $k => $v) {
            if ($v['balance'] >= $money) {
                array_push($data, [
                    'account_id' => $v['id'],
                    'company_id' => $companyId,
                    'consumption_date' => $consumptionDate,
                    'location_id' => $canteenId,
                    'used' => CommonEnum::STATE_IS_OK,
                    'status' => CommonEnum::STATE_IS_OK,
                    'staff_id' => $staffId,
                    'type' => $type,
                    'order_id' => $orderId,
                    'money' => 0 - $money,
                    'outsider' => $outsider,
                    'type_name' => $typeName
                ]);
                break;
            } else {
                if ($v['balance'] > 0) {
                    array_push($data, [
                        'account_id' => $v['id'],
                        'company_id' => $companyId,
                        'consumption_date' => $consumptionDate,
                        'location_id' => $canteenId,
                        'used' => CommonEnum::STATE_IS_OK,
                        'status' => CommonEnum::STATE_IS_OK,
                        'staff_id' => $staffId,
                        'type' => $type,
                        'order_id' => $orderId,
                        'money' => 0 - $v['balance'],
                        'outsider' => $outsider,
                        'type_name' => $typeName
                    ]);
                    $money -= $v['balance'];
                }
            }
        }
        $res = (new AccountRecordsT())->saveAll($data);
        if (!$res) {
            throw new SaveException(['msg' => '账户明细失败']);
        }

    }

    public function getAccountBalance($companyId, $staffID, $departmentId)
    {
        //获取企业所有账户
        $accounts = CompanyAccountT::accountsWithSortsAndDepartment($companyId);
        //获取用户账户余额
        $accountBalance = AccountRecordsT::statistic($staffID);
        foreach ($accounts as $k => $v) {
            $balance = 0;
            $allow = false;
            if ($v['department_all'] == CommonEnum::STATE_IS_OK) {
                $allow = true;
            } else {
                $departments = $v['departments'];
                foreach ($departments as $k2 => $v2) {
                    if ($departmentId == $v2['department_id']) {
                        $allow = true;
                        break;
                    }
                }
            }

            if ($allow) {
                foreach ($accountBalance as $k2 => $v2) {
                    if ($v['id'] == $v2['account_id']) {
                        $balance += $v2['money'];
                    }

                }
                $accounts[$k]['balance'] = $balance;
            } else {
                unset($accounts[$k]);
            }

        }
        return $accounts;

    }

    public function staffAccountBalance()
    {
        $companyId = Token::getCurrentTokenVar('current_company_id');
        $phone = Token::getCurrentPhone();
        $staff = CompanyStaffT::staffName($phone, $companyId);
        $staffId = $staff->id;
        $departmentId = $staff->d_id;
        $accounts = $this->getAccountBalance($companyId, $staffId, $departmentId);

        $fixedBalance = UserBalanceV::userFixedBalance($staffId);
        $accountBalance = array_sum(array_column($accounts, 'balance'));

        return [
            'balance' => $accountBalance,
            'useBalance' => $accountBalance - $fixedBalance,
            'accounts' => $accounts
        ];


    }

    public function fixedBalance($page, $size)
    {
        $phone = Token::getCurrentPhone();
        $companyId = Token::getCurrentTokenVar('current_company_id');
        $staff = CompanyStaffT::staffName($phone, $companyId);
        $staffId = $staff->id;
        $info = ConsumptionRecordsV::fixedRecords($phone, $companyId, $page, $size);
        $balance = UserBalanceV::userFixedBalance($staffId);
        return [
            'records' => $info,
            'balance' => $balance
        ];

    }

    public function transactionDetails($page, $size, $accountId, $type, $consumptionDate)
    {
        $staffId = Token::getCurrentTokenVar('staff_id');
        $records = $this->records($page, $size, $staffId, $consumptionDate, $accountId, $type);
        $accountInfo = AccountRecordsT::accountBalance($staffId, $accountId);
        return [
            'account' => $accountInfo,
            'records' => $records
        ];
    }

    public function bill($page, $size, $consumptionDate)
    {
        $staffId = Token::getCurrentTokenVar('staff_id');
        $records = $this->records($page, $size, $staffId, $consumptionDate, $accountId = 0, $type = 0);
        $statistic = AccountRecordsT::billStatistic($staffId, $consumptionDate);
        return [
            'statistic' => $statistic,
            'records' => $records
        ];
    }

    public function records($page, $size, $staffId, $consumptionDate, $accountId = 0, $type = 0)
    {
        $records = AccountRecordsT::transactionDetails($staffId, $accountId, $page, $size, $type, $consumptionDate);
        return $records;
    }


    public function detail($id)
    {
        $accountRecord = AccountRecordsT::info($id);
        if (!$accountRecord) {
            throw  new ParameterException(['msg' => '明细不存在']);
        }
        $type = $accountRecord->type;
        $outsider = $accountRecord->outsider;
        $returnData = [
            'name' => $accountRecord->type_name,
            'type' => $accountRecord->money > 0 ? "收入" : "支出",
            'create_time' => $accountRecord->create_time,
            'sub_money' => 0,
            'money' => $accountRecord->money,
            'delivery_fee' => 0,
            'consumption_sort' => 0
        ];
        $orderId = $accountRecord->order_id;
        if ($type == "one") {
            $info = OrderT::get($orderId);
            $returnData['count'] = $info->count;
            $returnData['money'] = $info->money;
            $returnData['sub_money'] = $info->sub_money;
            $returnData['delivery_fee'] = $info->delivery_fee;
            $returnData['unused_handel'] = $info->unused_handel;
        } else if ($type == "more") {
            if ($outsider == CommonEnum::STATE_IS_OK) {
                $info = OrderParentT::get($orderId);
                $returnData['count'] = $info->count;
                $returnData['money'] = $info->money;
                $returnData['delivery_fee'] = $info->delivery_fee;
                $returnData['unused_handel'] = $info->unused_handel;
                $returnData['consumption_sort'] = 1;

            } else {
                $info = OrderSubT::infoWithParent($orderId);
                $returnData['count'] = 1;
                $returnData['money'] = $info->money;
                $returnData['sub_money'] = $info->sub_money;
                $returnData['consumption_sort'] = $info->consumption_sort;
            }
        }

        return $returnData;
    }

    public function staffAccount()
    {
        $staffId = Token::getCurrentTokenVar('staff_id');
        $companyId = Token::getCurrentTokenVar('current_company_id');
        $staff = CompanyStaffT::get($staffId);
        $departmentId = $staff->d_id;
        $accounts = CompanyAccountT::accountsWithSortsAndDepartment($companyId);
        $useAccounts = [];
        if ($accounts) {
            foreach ($accounts as $k => $v) {
                if ($v['department_all'] == CommonEnum::STATE_IS_OK) {
                    array_push($useAccounts, [
                        'sort' => $v['sort'],
                        'name' => $v['name']
                    ]);
                    continue;
                }
                $departments = $v['departments'];
                foreach ($departments as $k2 => $v2) {
                    if ($departmentId == $v2['department_id']) {
                        array_push($useAccounts, [
                            'sort' => $v['sort'],
                            'name' => $v['name']
                        ]);
                        break;
                    }
                }
            }
        }
        return $useAccounts;
    }

    public function staffsAccount($companyId, $departmentId, $username, $page, $size)
    {
        $accounts = CompanyAccountT::accountsWithSortsAndDepartmentId($companyId);
        $staffs = CompanyStaffT::staffsForAccount($companyId, $departmentId, $username, $page, $size);
        $data = $staffs['data'];
        if (count($data)) {
            foreach ($data as $k => $v) {
                $staffDepartmentId = $v['d_id'];
                $data[$k]['account'] = $this->getStaffAccount($staffDepartmentId, $accounts);
            }
        }
        $staffs['data'] = $data;
        return [
            'staffs' => $staffs,
            'accounts' => $accounts
        ];
    }

    private function getStaffAccount($departmentId, &$accounts)
    {
        $staffAccount = [];
        if (!count($accounts)) {
            return $staffAccount;
        }
        foreach ($accounts as $k => $v) {

            if ($v['department_all'] == CommonEnum::STATE_IS_OK) {
                array_push($staffAccount, [
                    'account_id' => $v['id'],
                    'account_name' => $v['name'],
                ]);
                continue;
            }
            $allowDepartments = $v['departments'];
            if (count($allowDepartments)) {
                foreach ($allowDepartments as $k2 => $v2) {
                    if ($departmentId == $v2['department_id']) {
                        array_push($staffAccount, [
                            'account_id' => $v['id'],
                            'account_name' => $v['name'],
                        ]);
                        break;
                    }
                }
            }
        }
        return $staffAccount;
    }

    public function checkStaffAccount($accounts, $departmentId)
    {
        $useAccounts = [];
        if ($accounts) {
            foreach ($accounts as $k => $v) {
                if ($v['department_all'] == CommonEnum::STATE_IS_OK) {
                    array_push($useAccounts, [
                        'account_id' => $v['id'],
                        'sort' => $v['sort'],
                        'name' => $v['name']
                    ]);
                    continue;
                }
                $departments = $v['departments'];
                foreach ($departments as $k2 => $v2) {
                    if ($departmentId == $v2['department_id']) {
                        array_push($useAccounts, [
                            'account_id' => $v['id'],
                            'sort' => $v['sort'],
                            'name' => $v['name']
                        ]);
                        break;
                    }
                }
            }
        }
        return $useAccounts;
    }


    public function checkStaffHaveAccount($department_all, $accountDepartments, $staffDepartmentId)
    {
        $have = CommonEnum::STATE_IS_FAIL;
        if ($department_all == CommonEnum::STATE_IS_OK) {
            $have = CommonEnum::STATE_IS_OK;
        }
        foreach ($accountDepartments as $k => $v) {
            if ($staffDepartmentId == $v['department_id']) {
                $have = CommonEnum::STATE_IS_OK;
                break;
            }
        }
        return $have;

    }


    public function initAccount()
    {
        $companyId = 0;//95;
        //获取企业个人账户
        (new  AccountService())->accounts($companyId);
        $account = CompanyAccountT::where('company_id', $companyId)
            ->where('type', 1)
            ->where('fixed_type', 1)
            ->where('state', 1)
            ->find()->toArray();
        if (!$account) {
            throw new ParameterException(['msg' => "账户为空"]);
        }
        //获取企业所有用户余额
        $balance = UserBalanceV::balanceForAccountInit($companyId);
        $data = [];
        foreach ($balance as $k => $v) {
            if ($v['staff_id'] > 0 && $v['balance'] != 0) {
                array_push($data, [
                    'account_id' => $account['id'],
                    'company_id' => $companyId,
                    'consumption_date' => \date('Y-m-d'),
                    'location_id' => 0,
                    'used' => CommonEnum::STATE_IS_OK,
                    'status' => CommonEnum::STATE_IS_OK,
                    'staff_id' => $v['staff_id'],
                    'type' => 'init',
                    'order_id' => 0,
                    'money' => $v['balance'],
                    'outsider' => 2,
                    'type_name' => "系统初始化"
                ]);
            }

        }
        (new AccountRecordsT())->saveAll($data);
    }

    public function sendTemplate($type, $accountId)
    {

        $jobHandlerClassName = 'app\api\job\SendTemplate';//负责处理队列任务的类
        $jobQueueName = "sendTemplateQueue";//队列名称
        $jobData = [
            'type' => $type,
            'id' => $accountId
        ];//当前任务的业务数据
        $isPushed = Queue::push($jobHandlerClassName, $jobData, $jobQueueName);
        //将该任务推送到消息队列
      /*  if ($isPushed == false) {
            throw new SaveException(['msg' => '发送模板消息失败']);
        }*/
    }


}