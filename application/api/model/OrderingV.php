<?php
/**
 * Created by PhpStorm.
 * User: 明良
 * Date: 2019/9/5
 * Time: 23:19
 */

namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class OrderingV extends Model
{
    public static function getRecordForDayOrdering($u_id, $ordering_date, $dinner)
    {
        $record = self::where('u_id', $u_id)
            ->where('ordering_date', $ordering_date)
            ->where('dinner', $dinner)
            ->count();
        return $record;
    }

    public static function userOrdering($u_id, $consumption_time)
    {
        $orderings = self::where('u_id', $u_id)
            ->whereBetweenTime('ordering_month', $consumption_time)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->select();
        return $orderings;
    }

    public static function getUserOrdering($u_id)
    {
        $orderings = self::where('u_id', $u_id)
            ->whereTime('ordering_date', '>=', date('Y-m-d'))
            ->where('state', CommonEnum::STATE_IS_OK)
            ->where('used', CommonEnum::STATE_IS_FAIL)
            ->select()->toArray();
        return $orderings;
    }

    public static function userOrderings($u_id, $type, $canteen_id, $page, $size)
    {
        $orderings = self::where('u_id', $u_id)
            ->whereTime('ordering_date', '>=', date('Y-m-d'))
            ->where('type', $type)
            ->where(function ($query) use ($canteen_id) {
                if (!empty($canteen_id)) {
                    $query->where('c_id', $canteen_id);
                }
            })
            ->where('used', CommonEnum::STATE_IS_FAIL)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->field('id,canteen as address,if(type=1,"食堂","外卖") as type,create_time,dinner,money')
            ->paginate($size, false, ['page' => $page]);
        return $orderings;
    }

}