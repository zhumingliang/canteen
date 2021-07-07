<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Db;
use think\Model;

class AdminCanteenT extends Model
{

    public static function canteens($adminId)
    {
        $canteens = Db::table('canteen_admin_canteen_t')->alias('a')
            ->leftJoin('canteen_canteen_t b', 'a.c_id=b.id')
            ->field('a.c_id as id ,b.name,"canteen" as type')
            ->where('a.admin_id', $adminId)
            ->where('a.state', CommonEnum::STATE_IS_OK)
            ->select()->toArray();
        return $canteens;

    }

}