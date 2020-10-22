<?php


namespace app\api\service;


use app\api\model\AccountDepartmentT;
use app\api\model\CompanyAccountT;
use app\lib\enum\CommonEnum;
use app\lib\exception\SaveException;
use app\lib\exception\UpdateException;
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
            $adminID = Token::getCurrentTokenVar('u_id');
            $params['admin_id'] = $adminID;
            $params['next_time'] = $this->getNextClearTime($params['clear'], $params['clear_type'], $params['first'], $params['end'],
                $params['dat_count'], $params['time_begin']);
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
                $departments = $params['departments'];
                $data = [];
                foreach ($departments as $k => $v) {
                    array_push($data, [
                        'account_id' => $account->id,
                        'department_id' => $v,
                        'state' => CommonEnum::STATE_IS_OK
                    ]);
                }
                $accountDepartment = (new AccountDepartmentT())->saveAll($data);
                if (!$accountDepartment) {
                    throw new SaveException();
                }
            }
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw new SaveException();
        }


    }

    public function account($id)
    {

    }

    public function accounts()
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

}