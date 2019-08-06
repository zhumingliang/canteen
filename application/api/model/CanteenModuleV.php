<?php


namespace app\api\model;


use think\Model;

class CanteenModuleV extends Model
{
    public static function modules($c_id)
    {
        $modules = self::where('canteen_id', $c_id)
            ->select()->toArray();
        return $modules;
    }
    public static function canteenModules($c_id)
    {
        $modules = self::where('canteen_id', $c_id)
            ->hidden(['canteen_id','company_id'])
            ->select()->toArray();
        return $modules;
    }

}