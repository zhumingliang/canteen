<?php


namespace app\api\model;


use think\Model;

class DinnerV extends Model
{
    public static function companyDinners($company_id)
    {
        return self::where('company_id', $company_id)
            ->select()->toArray();
    }

    public static function companyDinners2($company_id)
    {
        return self::where('company_id', $company_id)
            ->field('dinner_id as id,dinner as name')
            ->select()->toArray();
    }

}