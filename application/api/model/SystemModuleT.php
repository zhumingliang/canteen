<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class SystemModuleT extends Model
{
    public function getSuperModules()
    {
        $modules = self::where('state', CommonEnum::STATE_IS_OK)
            ->field('id,name,url,parent_id')
            ->select();
        return $modules;
    }

}