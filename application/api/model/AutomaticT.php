<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class AutomaticT extends Model
{
    public static function checkExits($dinnerId, $repeatWeek)
    {
        $auto = self::where('dinner_id', $dinnerId)
            ->where('repeat_week', $repeatWeek)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->find();
        return $auto;

    }

}