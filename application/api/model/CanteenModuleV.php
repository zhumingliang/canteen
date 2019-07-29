<?php


namespace app\api\model;


use think\Model;

class CanteenModuleV extends Model
{
    public static function modules($c_id)
    {
        $modules = self::where('company_id', $c_id)
            ->select()->toArray();
        return $modules;
    }

}