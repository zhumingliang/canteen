<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class OrderPrepareSubT extends Model
{
    public static function ordersMoney($prepareId)
    {
        $money = self::where('order_id', $prepareId)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->field('sum(money+sub_money) as money')
            ->find();

        return $money->money;
    }

}