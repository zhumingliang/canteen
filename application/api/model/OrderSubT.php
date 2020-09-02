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

    public static function getOrderMoney($id)
    {
        return self::where('order_id', $id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->field('(money+sub_money) as money')
            ->select()->toArray();
    }

    public
    static function infoForPrinter($id)
    {
        $info = self::where('id', $id)
            ->field('id,order_id,money,sub_money,confirm_time,qrcode_url,count,sort_code')
            ->find()->toArray();
        return $info;
    }

}