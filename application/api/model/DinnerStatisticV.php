<?php


namespace app\api\model;


use think\Model;

class DinnerStatisticV extends Model
{
    public static function managerDinnerStatistic($dinner_id, $consumption_time,$page,$size)
    {
        $statistic = self::whereBetweenTime('ordering_date', $consumption_time)
            ->where('dinner_id', $dinner_id)
            ->field('order_id,food_id,name,count(detail_id) as count')
            ->group('food_id')
            ->paginate($size, false, ['page' => $page]);
        return $statistic;
    }
}