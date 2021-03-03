<?php


namespace app\api\model;


use think\Model;

class FoodsStatisticV extends Model
{
    public static function foodUsersStatistic($dinner_id, $food_id, $consumption_time, $page, $size, $department_id)
    {
        $statistic = self::where('dinner_id', $dinner_id)
            ->where('food_id', $food_id)
            ->where('ordering_date', $consumption_time)
            ->where(function ($query) use ($department_id) {
                if ($department_id) {
                    $query->where('department_id', $department_id);
                }
            })
            ->field('phone,username')
            ->paginate($size, false, ['page' => $page]);
        return $statistic;
    }

}