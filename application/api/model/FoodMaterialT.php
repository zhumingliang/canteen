<?php


namespace app\api\model;


use think\Model;

class FoodMaterialT extends Model
{
    public static function checkFoodMaterialExits($f_id, $name)
    {
        return self::where('f_id', $f_id)
            ->where('name', $name)
            ->count();

    }

}