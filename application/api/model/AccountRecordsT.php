<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class AccountRecordsT extends Model
{
    public static function statistic($staff_id)
    {
        $statistic = self::where('staff_id', $staff_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->field('account_id,sum(money) as money')
            ->group('account_id')
            ->select();
        return $statistic;

    }

    public static function companyAccountsBalance($companyId)
    {
        $statistic = self::where('company_id', $companyId)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->field('account_id,sum(money) as money')
            ->group('account_id')
            ->select()->toArray();
        return $statistic;

    }

}