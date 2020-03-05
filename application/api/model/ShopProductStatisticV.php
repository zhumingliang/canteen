<?php


namespace app\api\model;


use think\Model;

class ShopProductStatisticV extends Model
{
    public static function saleMoney($supplier_id, $time_begin, $time_end)
    {
        $time_end=addDay(1,$time_end);
        $money = self::where('supplier_id', $supplier_id)
            ->whereBetweenTime('create_time', $time_begin, $time_end)
            ->sum('sell_money');
        return $money;
    }

}