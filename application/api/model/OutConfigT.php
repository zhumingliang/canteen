<?php


namespace app\api\model;


use think\Model;

class OutConfigT extends Model
{
    public static function config($canteen_id)
    {
        return self::where('canteen_id', $canteen_id)
            ->hidden(['create_time','update_time','canteen_id'])
            ->order('create_time','DESC')
            ->select();

    }

}