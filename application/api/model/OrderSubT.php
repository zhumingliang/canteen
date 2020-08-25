<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class OrderSubT extends Model
{
    public static function usedOrders($orderID)
    {
        return self::where('order_id', $orderID)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->whereOr('used', CommonEnum::STATE_IS_OK)
            ->whereOr('wx_confirm', CommonEnum::STATE_IS_OK)
            ->count('id');
    }

}