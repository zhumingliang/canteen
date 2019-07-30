<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class CanteenAccountT extends Model
{
    public  static function account($c_id)
    {
        $info = self::where('c_id', $c_id)
            ->hidden(['update_time'])
            ->find();
        return $info;
    }

}