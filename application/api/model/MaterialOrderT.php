<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class MaterialOrderT extends Model
{
    public static function checkExits($canteenId, $day, $material)
    {
        return self::where('canteen_id', $canteenId)
            ->where('day', $day)
            ->where('material', $material)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->count();
    }



}