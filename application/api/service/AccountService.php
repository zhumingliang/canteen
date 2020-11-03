<?php


namespace app\api\service;


use app\api\controller\v1\Account;
use app\api\model\AccountDepartmentT;
use app\api\model\AccountRecordsT;
use app\api\model\CompanyAccountT;
use app\api\model\PayNonghangConfigT;
use app\api\model\PayT;
use app\api\validate\Company;
use app\lib\enum\CommonEnum;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;
use app\lib\exception\UpdateException;
use MongoDB\BSON\Type;
use think\Db;
use think\Exception;

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
            if ($this->checkNongHang($companyId) && !$fixedNongHang) {
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
            $account = CompanyAccountT::update($params);
            if (!$account) {
                throw new UpdateException();
            }
            if (empty($params['departments'])) {
                $departments = json_encode($params['departments'], true);
                $add = [];
                $cancel = [];
                if (!empty($departments['add'])) {
                    $add = json_decode($departments['add'], true);
                }
                if (!empty($departments['cancel'])) {
                    $cancel = json_decode($departments['cancel'], true);
                }
                $this->saveDepartments($params['id'], $add, $cancel);
            }

            if (!empty($params['account_sort'])) {
                $accountSort = $params['account_sort'];
                $update = CompanyAccountT::update($accountSort);
                if (!$update) {
                    throw new UpdateException();
                }
            }

        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }
        Db::commit();
    }

    public function accountsForSearch($companyId)
    {
        //检测是否有基本账户：个人账户
        //1.查看是否有基本户
        if (empty($companyId)) {
            $companyId = Token::getCurrentTokenVar('company_id');
        }
        $accounts = CompanyAccountT::accountForSearch($companyId);
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
                if ($v['fixed_type'] == 1) {
                    $fixedPerson = true;
                }

                if ($v['fixed_type'] == 2) {
                    $fixedNongHang = true;
                }
            }

            if (!$fixedPerson) {
                $this->saveFixedAccount($companyId, 1);
            }
            if ($this->checkNongHang($companyId) && !$fixedNongHang) {
                $this->saveFixedAccount($companyId, 2);
            }
        }

        $accounts = CompanyAccountT::accountForSearch($companyId);
        return $accounts;
    }

    public function accountBalance($company_id, $staff_id)
    {
        //获取充值信息（微信/农行）1｜ 微信；2｜农行
        $payBalance = PayT::statistic($staff_id);
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
            if ($v['type'] == 1) {
                //基本账户
                foreach ($payBalance as $k3 => $v3) {
                    if ($v['fixed_type'] == $v3['method_id']) {
                        $money += $v3['money'];
                    }
                }

            }
            $accounts[$k]['balance'] = $money;
        }
        return $accounts;

    }

    public function saveAccountRecords($accounts, $money, $type, $orderId, $companyId, $staffId)
    {
        $data = [];
        foreach ($accounts as $k => $v) {
            if ($v['balance'] >= $money) {
                array_push($data, [
                    'account_id' => $v['id'],
                    'company_id' => $companyId,
                    'staff_id' => $staffId,
                    'type' => $type,
                    'order_id' => $orderId,
                    'money' => $money
                ]);
            } else {
                $money -= $v['balance'];
            }
        }
        $res = (new AccountRecordsT())->saveAll($data);
        if (!$res) {
            throw new SaveException(['msg' => '账户明细失败']);
        }

    }

}