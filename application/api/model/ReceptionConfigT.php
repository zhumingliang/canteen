<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class ReceptionConfigT extends Model
{
    public static function config($c_id)
    {
        $config = self::where('canteen_id', $c_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->find();
        return $config;
    }

}