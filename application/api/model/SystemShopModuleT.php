<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class SystemShopModuleT extends Model
{
    public static function defaultModules()
    {
        $modules = self::where('state', CommonEnum::STATE_IS_OK)
            ->where('default',CommonEnum::STATE_IS_OK)
            ->field('id,type')
            ->select();
        return $modules;

    }

}