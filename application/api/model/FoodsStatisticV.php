<?php


namespace app\api\model;


use think\Model;

class FoodsStatisticV extends Model
{
    public static function foodUsersStatistic($dinner_id, $food_id, $consumption_time, $page, $size)
    {
        $statistic = self::where('dinner_id', $dinner_id)
            ->where('food_id', $food_id)
            ->whereBetweenTime('ordering_date', $consumption_time)
            ->field('phone,username')
            ->paginate($size, false, ['page' => $page]);
        return $statistic;
    }

}