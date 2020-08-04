<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class RechargeSupplementT extends Model
{

    public function getConsumptionTypeAttr($value, $data)
    {
        if ($data['type'] == CommonEnum::STATE_IS_FAIL) {
            return "系统补扣";
        } else {
            return "系统补充值";
        }
    }

    public static function orderDetail($order_id)
    {
        $info = self::where('id', $order_id)
            ->field('id,ordering_date,1 as count,
           1 as consumption_type,money,0 as sub_money 0 as delivery_fee')
            ->find();
        return $info;
    }

}