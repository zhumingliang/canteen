<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class OrderParentT extends Model
{
    public static function orderInfo($ordering_date, $canteen_id, $dinner_id, $phone)
    {
        $order = self::where('phone', $phone)
            ->where('ordering_date', $ordering_date)
            ->where('canteen_id', $canteen_id)
            ->where('dinner_id', $dinner_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->find();
        return $order;


    }

}