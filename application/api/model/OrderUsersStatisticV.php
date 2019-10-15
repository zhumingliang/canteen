<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class OrderUsersStatisticV extends Model
{
    public static function orderUsers($dinner_id, $consumption_time, $consumption_type, $page, $size)
    {
        $users = self::where('dinner_id', $dinner_id)
            ->whereBetweenTime('ordering_date', $consumption_time)
            ->where(function ($query) use ($consumption_type) {
                if ($consumption_type == 'used') {
                    $query->where('used', CommonEnum::STATE_IS_OK);
                } else if ($consumption_type == 'noOrdering') {
                    $query->where('booking', CommonEnum::STATE_IS_FAIL);
                } else if ($consumption_type == 'orderingNoMeal') {
                    $query->where('used', CommonEnum::STATE_IS_FAIL);
                }
            })
            ->field('username,phone')
            ->paginate($size, false, ['page' => $page]);
        return $users;

    }

}