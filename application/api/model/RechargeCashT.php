<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use app\lib\enum\PayEnum;
use think\Model;

class RechargeCashT extends Model
{
    //充值总金额
    public static function monthRechargeMoney($timeBegin, $timeEnd, $staffId)
    {
        $timeEnd = addDay(1, $timeEnd);
        $statistic = self::field("money")
            ->where('staff_id', $staffId)
            ->where('create_time', '>=', $timeBegin)
            ->where('create_time', '<=', $timeEnd)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->unionAll(function ($query) use ($timeBegin, $timeEnd, $staffId) {
                $query->table('canteen_pay_t')
                    ->field("money")
                    ->where('staff_id', $staffId)
                    ->where('create_time', '>=', $timeBegin)
                    ->where('create_time', '<=', $timeEnd)
                    ->where('state', CommonEnum::STATE_IS_OK)
                    ->where('refund', CommonEnum::STATE_IS_FAIL)
                    ->where('status', PayEnum::PAY_SUCCESS);
            })->select()->toArray();
        $monthRechargeMoney = array_sum(array_column($statistic, 'money'));
        return $monthRechargeMoney;
    }


    public
    static function outsiderMonthRechargeMoney($timeBegin, $timeEnd, $companyId, $phone)
    {
        $timeEnd = addDay(1, $timeEnd);
        $statistic = self::field("money")
            ->where('company_id', $companyId)
            ->where('phone', $phone)
            ->where('create_time', '>=', $timeBegin)
            ->where('create_time', '<=', $timeEnd)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->unionAll(function ($query) use ($timeBegin, $timeEnd, $companyId, $phone) {
                $query->table('canteen_pay_t')
                    ->field("money")
                    ->where('company_id', $companyId)
                    ->where('phone', $phone)
                    ->where('create_time', '>=', $timeBegin)
                    ->where('create_time', '<=', $timeEnd)
                    ->where('state', CommonEnum::STATE_IS_OK)
                    ->where('refund', CommonEnum::STATE_IS_FAIL)
                    ->where('status', PayEnum::PAY_SUCCESS);
            })->select()->toArray();
        $monthRechargeMoney = array_sum(array_column($statistic, 'money'));
        return $monthRechargeMoney;
    }

}