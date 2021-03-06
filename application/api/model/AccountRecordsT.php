<?php


namespace app\api\model;


use app\lib\Date;
use app\lib\enum\CommonEnum;
use think\Model;
use think\Request;

class AccountRecordsT extends Model
{
    public function account()
    {
        return $this->belongsTo('CompanyAccountT', 'account_id', 'id');
    }

    public static function statistic($staff_id)
    {
        $statistic = self::where('staff_id', $staff_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->field('account_id,sum(money) as money')
            ->group('account_id')
            ->select();
        return $statistic;

    }

    public static function balance($staff_id)
    {
        $statistic = self::where('staff_id', $staff_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->sum('money');
        return $statistic;

    }

    public static function companyAccountsBalance($staffId, $companyId)
    {
        $statistic = self::where('company_id', $companyId)
            ->where('staff_id', $staffId)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->field('account_id,sum(money) as money')
            ->group('account_id')
            ->select()->toArray();
        return $statistic;

    }

    public static function accountBalance($staffId, $accountId)
    {
        $statistic = self::where('staff_id', $staffId)
            ->where('account_id', $accountId)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->with(['account' => function ($query) {
                $query->field('id,name');
            }])
            ->field('account_id,sum(money) as money')
            ->select();
        return $statistic;

    }

    public static function transactionDetails($staffId, $accountId, $page, $size, $type, $consumptionDate)
    {
        $month = Date::mFristAndLast2($consumptionDate);
        $begin = $month['fist'];
        $end = $month['last'];
        return self::where('staff_id', $staffId)
            ->where('consumption_date', '>=', $begin)
            ->where('consumption_date', '<=', $end)
            ->where(function ($query) use ($accountId, $type) {
                if ($accountId) {
                    $query->where('account_id', $accountId);
                }
                if ($type) {
                    if ($type == 1) {
                        $query->where('money', '>', 0);
                    } else
                        $query->where('money', '<', 0);
                }
            })
            ->with(['account' => function ($query) {
                $query->field('id,name');
            }])
            ->where('state', CommonEnum::STATE_IS_OK)
            ->field('id,account_id,order_id,money,type,type_name,create_time')
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page]);

    }

    public static function info($id)
    {

        return self::where('id', $id)->with(['account' => function ($query) {
            $query->field('id,name');
        }])->find();
    }

    public static function billStatistic($staffId, $consumptionDate)
    {
        $month = Date::mFristAndLast2($consumptionDate);
        $begin = $month['fist'];
        $end = $month['last'];
        $statistic = self::field('sum(if(money>0,money,0)) as income,sum(if(money<0,money,0)) as expend')
            ->where('staff_id', $staffId)
            ->where('consumption_date', '>=', $begin)
            ->where('consumption_date', '<=', $end)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->select();
        return $statistic;

    }

    public static function checkAccountBalance($accountId)
    {
        $balance = self::where('account_id', $accountId)
            ->sum('money');
        return $balance;

    }


    public static function staffBalance($accountId)
    {
        $statistic = self::where('account_id', $accountId)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->field('company_id,staff_id,sum(money) as money')
            ->group('staff_id')
            ->select();
        return $statistic;
    }

    public static function orderRecords($type, $orderId, $outsider)
    {
        $records = self::where('order_id', $orderId)
            ->where('type', $type)
            ->where('outsider', $outsider)
            ->select();
        return $records;
    }


}