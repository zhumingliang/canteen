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
    public static function FoodStatus($canteen_id, $day)
    {
        $list = self::where('canteen_id', $canteen_id)
            ->where('day', '=',$day)
            ->select();
        return $list;

    }

}