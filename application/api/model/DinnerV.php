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

}