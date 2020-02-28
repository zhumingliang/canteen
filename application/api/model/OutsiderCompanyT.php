<?php


namespace app\api\model;


use think\Model;

class OutsiderCompanyT extends Model
{
    public static function companies($user_id)
    {
        $companies = self::where('user_id', $user_id)
            ->field('group_concat(company_id, ",") as ids')
            ->group('user_id')
           ->find();
        return $companies;

    }

}