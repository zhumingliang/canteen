<?php


namespace app\api\model;


use think\Model;

class ShopModuleV extends Model
{
    public static function modules($c_id)
    {
        $modules = self::where('shop_id', $c_id)
            ->select()->toArray();
        return $modules;
    }

}