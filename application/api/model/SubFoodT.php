<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class SubFoodT extends Model
{
    public static function detail($order_id)
    {
        $detail = self::where('o_id', $order_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->select()->toArray();
        return $detail;
    }

}