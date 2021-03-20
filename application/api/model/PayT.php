<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use app\lib\enum\PayEnum;
use think\Model;

class PayT extends Model
{
    public static function statistic($staff_id)
    {
        $statistic = self::where('staff_id', $staff_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->where('status', PayEnum::PAY_SUCCESS)
            ->where('refund', CommonEnum::STATE_IS_FAIL)
            ->field('method_id,sum(money) as money')
            ->group('method_id')
            ->select();
        return $statistic;

    }

    public static function getPreOrder($order_id)
    {
        return self::where('prepare_id', $order_id)
            ->find();
    }

}