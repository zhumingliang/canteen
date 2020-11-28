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

}