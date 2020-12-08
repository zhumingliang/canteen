<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class OrderSubT extends Model
{
    public function parent()
    {
        return $this->belongsTo('OrderParentT', 'order_id', 'id');
    }

    public static function usedOrders($orderID)
    {
        return self::where('order_id', $orderID)
            ->where('state', CommonEnum::STATE_IS_OK)
            // ->where('used', CommonEnum::STATE_IS_OK)
            // ->where('wx_confirm', CommonEnum::STATE_IS_OK)
            ->whereRaw('`wx_confirm` = 1  OR `used` = 1')
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
            ->find();
        return $info;
    }

    public
    static function info($id)
    {
        $info = self::where('id', $id)
            ->field('id,order_id,money,sub_money,used,booking,ordering_date,consumption_sort,sort_code')
            ->find();
        return $info;
    }

    public
    static function infoWithParent($id)
    {
        $info = self::where('id', $id)
            ->field('id,order_id,money,sub_money,used,booking,ordering_date,consumption_sort,sort_code')
            ->with('parent')
            ->find();
        return $info;
    }

}