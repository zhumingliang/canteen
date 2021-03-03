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

    public static function orderUsers($canteen_id, $dinner_id, $consumption_time, $consumption_type, $key, $page, $size, $department_id)
    {
        $users = self::where(function ($query) use ($dinner_id) {
            if ($dinner_id) {
                $query->where('dinner_id', $dinner_id);
            }
        })->where(function ($query) use ($canteen_id) {
            if ($canteen_id) {
                $query->where('c_id', $canteen_id);
            }
        })
            ->where(function ($query) use ($department_id) {
                if ($department_id) {
                    $query->where('department_id', $department_id);
                }
            })
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
                    $keyRes = (int)$key;
                    if ($keyRes == 0) {
                        $query->where('username|sort_code', 'like', $key);
                    } else {
                        $query->whereOr('parent_id', 'like', $keyRes)
                            ->whereOr('phone', 'like', '%' . $keyRes . '%');

                    }

                }
            })
            ->with([
                'foods' => function ($query) {
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->field('id as detail_id ,o_id,count,name,price');
                }
            ])
            ->field('order_id as id,username,order_num,phone,sum(count) as count,strategy_type as consumption_type,type,dinner_id,booking,used,department')
            ->group('order_id')
            //->fetchSql(true)->select();
            ->paginate($size, false, ['page' => $page])
            ->toArray();
        return $users;
    }

    public static function statisticToOfficial($canteen_id, $consumption_time, $key, $department_id)
    {
        $statistic = self::where('c_id', $canteen_id)
            ->where('pay', PayEnum::PAY_SUCCESS)
            ->where('ordering_date', $consumption_time)
            ->where(function ($query) use ($key) {
                if ($key) {
                    $keyRes = (int)$key;
                    if ($keyRes == 0) {
                        $query->where('username|sort_code', 'like', $key);
                    } else {
                        $query->whereOr('parent_id', 'like', $keyRes)
                            ->whereOr('phone', 'like', '%' . $keyRes . '%');

                    }

                }
            })
            ->where(function ($query) use ($department_id) {
                if ($department_id) {
                    $query->where('department_id', $department_id);
                }
            })
            ->field('dinner_id as d_id,used,booking,sum(count) as count')
            ->group('dinner_id,used,booking')
            ->select()->toArray();
        return $statistic;
    }


}