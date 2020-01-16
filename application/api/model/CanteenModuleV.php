<?php


namespace app\api\model;


use app\lib\enum\ModuleEnum;
use think\Model;

class CanteenModuleV extends Model
{
    public static function modules($c_id)
    {

        $modules = self::where('company_id', $c_id)
            ->select()->toArray();
        return $modules;
    }

    public static function canteenModules($company_id)
    {
        $modules = self::where('company_id', $company_id)
            ->hidden(['canteen_id', 'company_id'])
            ->select()->toArray();
        return $modules;
    }

    public static function mobileModulesWithID($ids)
    {
        $modules = self::whereIn('c_m_id', $ids)
            ->where('type', ModuleEnum::MOBILE)
            ->where('parent_id', '>', 0)
            ->field('category,name,url,icon')
            ->select()->toArray();
        return $modules;
    }

    public static function adminModulesWithID($ids)
    {
        $modules = self::whereIn('c_m_id', $ids)
            ->field('id,m_id,parent_id,type,name,url,icon')
            ->select()->toArray();
        return $modules;
    }

    public static function companyNormalMobileModules($company_id)
    {

        $modules = self::where('company_id', $company_id)
            ->where('type', ModuleEnum::MOBILE)
            ->where('category', ModuleEnum::NORMAL)
            ->field('category,name,url,icon')
            ->group('c_m_id')
            ->select()->toArray();
        return $modules;
    }

}