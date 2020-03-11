<?php


namespace app\api\model;


use think\Model;

class ShopT extends Model
{
    public static function shop($shop_id)
    {
        $shop = self::where('id', $shop_id)
            ->find();
        return $shop;
    }
}