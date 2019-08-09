<?php


namespace app\api\model;


use think\Model;

class AdminCanteenV extends Model
{
    public static function companyRoleCanteens($admin_id)
    {
        $canteens = self::where('admin_id', $admin_id)
            ->field('id,company_name as name,GROUP_CONCAT(canteen_id) as  canteen_ids,GROUP_CONCAT(canteen_name) as  canteen_names')
            ->group('id')
            ->select()->toArray();
        return $canteens;

    }

}