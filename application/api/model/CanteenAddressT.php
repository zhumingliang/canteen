<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class CanteenAddressT extends Model
{
    public static function address($canteen_id)
    {
        return self::where('canteen_id', $canteen_id)
            ->where('state',CommonEnum::STATE_IS_OK)
            ->hidden(['create_time','update_time','canteen_id'])
            ->order('create_time','DESC')
            ->select();
    }

}