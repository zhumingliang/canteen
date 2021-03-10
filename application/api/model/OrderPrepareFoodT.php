<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class OrderPrepareFoodT extends Model
{
    public static function orderMoney($prepareOrderId)
    {
        $money = self::where('prepare_order_id', $prepareOrderId)
            ->field('sum(price*count) as money')
            ->find();
        return $money->money;

    }

}