<?php


namespace app\api\model;


use think\Model;

class StaffV extends Model
{
    public static function get($phone)
    {
        $info = self::where('phone', $phone)
            ->field('id,company_id,company')
            ->group('phone,company_id')
            ->select()->toArray();
        return $info;

    }


    public static function getStaffCanteens($phone)
    {
        $info = self::where('phone', $phone)
            ->field('id,company_parent_id,company_id,company,canteen_id,canteen')
            ->select()
            ->toArray();
        return $info;

    }

}