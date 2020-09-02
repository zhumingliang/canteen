<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use app\lib\enum\PayEnum;
use think\Model;

class OrderUsersStatisticV extends Model
{


    public function foods()
    {
        return $this->hasMany('OrderDetailT', 'o_id', 'id');
    }

    public static function orderUsers($dinner_id, $consumption_time, $consumption_type, $key, $page, $size)
    {
        $users = self::where('dinner_id', $dinner_id)
            ->where('ordering_date', $consumption_time)
            ->where(function ($query) use ($consumption_type) {
                if ($consumption_type == 'used') {
                    $query->where('booking', CommonEnum::STATE_IS_OK)
                        ->where('used', CommonEnum::STATE_IS_OK);
                } else if ($consumption_type == 'noOrdering') {
                    $query->where('booking', CommonEnum::STATE_IS_FAIL);
                } else if ($consumption_type == 'orderingNoMeal') {
                    $query->where('used', CommonEnum::STATE_IS_FAIL);
                }
            })
            ->where(function ($query) use ($key) {
                if ($key) {
                    $query->where('username|order_num|phone|sort_code', 'like', "%$key%");
                }
            })
            ->with([
                'foods' => function ($query) {
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->field('id as detail_id ,o_id,count,name,price');
                }
            ])
            ->field('order_id as id,username,order_num,phone,sum(count) as count,strategy_type as consumption_type')
            ->group('order_id')
            ->paginate($size, false, ['page' => $page]);
        return $users;
    }

    public static function statisticToOfficial($canteen_id, $consumption_time, $key)
    {
        $statistic = self::where('c_id', $canteen_id)
            ->where('pay', PayEnum::PAY_SUCCESS)
            ->where('ordering_date', $consumption_time)
            ->where(function ($query) use ($key) {
                if ($key) {
                    $query->where('username|order_num|phone|sort_code', 'like', "%$key%");
                }
            })
            ->field('dinner_id as d_id,used,booking,sum(count) as count')
            ->group('dinner_id,used,booking')
            ->select()->toArray();
        return $statistic;
    }


}