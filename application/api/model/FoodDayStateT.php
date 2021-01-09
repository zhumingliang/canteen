<?php
/**
 * Created by PhpStorm.
 * User: 明良
 * Date: 2019/9/3
 * Time: 0:02
 */

namespace app\api\model;


use think\Model;

class FoodDayStateT extends Model
{
    public static function FoodStatus($canteen_id, $dinnerId, $day)
    {
        $list = self::where('canteen_id', $canteen_id)
            ->where('dinner_id', $dinnerId)
            ->where('day', '=', $day)
            ->select()->toArray();
        return $list;

    }

}