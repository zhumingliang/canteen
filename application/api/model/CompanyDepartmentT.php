<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class CompanyDepartmentT extends Model
{
    public static function adminDepartments($company_id)
    {
        $departments = self::where('c_id', $company_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->field('id,name')
            ->select();
        return $departments;
    }


}