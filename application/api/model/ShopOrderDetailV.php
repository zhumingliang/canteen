<?php


namespace app\api\model;


use think\Model;

class ShopOrderDetailV extends Model
{
    public static function detail($order_id)
    {
        return self::where('order_id', $order_id)
            ->select();
    }

}