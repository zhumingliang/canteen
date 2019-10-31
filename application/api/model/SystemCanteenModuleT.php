<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class SystemCanteenModuleT extends Model
{
    public static function defaultModules()
    {
        $modules = self::where('state', CommonEnum::STATE_IS_OK)
            ->where('default',CommonEnum::STATE_IS_OK)
            ->field('id,type')
            ->select();
        return $modules;

    }

    public static function getSuperModules()
    {
        $modules = self::where('state', CommonEnum::STATE_IS_OK)
            ->field('id as m_id,type,name,parent_id,url')
            ->select();
        return $modules;

    }


}