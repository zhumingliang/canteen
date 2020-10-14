<?php
/**
 * Created by PhpStorm.
 * User: 明良
 * Date: 2019/9/5
 * Time: 23:19
 */

namespace app\api\model;


use app\api\service\LogService;
use app\lib\enum\CommonEnum;
use app\lib\enum\OrderEnum;
use app\lib\enum\PayEnum;
use think\Model;

class OrderingV extends Model
{
    public static function getRecordForDayOrdering($u_id, $ordering_date, $dinner)
    {
        $record = self::where('u_id', $u_id)
            ->where('ordering_date', $ordering_date)
            ->where('pay', PayEnum::PAY_SUCCESS)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->where('dinner', $dinner)
            ->count();
        return $record;
    }

    public static function getRecordForDayOrderingByPhone($ordering_date, $dinner, $phone)
    {
        $record = self::where('phone', $phone)
            ->where('ordering_date', $ordering_date)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->where('pay', PayEnum::PAY_SUCCESS)
            ->where('dinner', $dinner)
            ->select()->toArray();
        return $record;
    }

    public static function getOrderingCountByPhone($ordering_date, $dinner, $phone)
    {
        $record = self::where('phone', $phone)
            ->where('ordering_date', $ordering_date)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->where('pay', PayEnum::PAY_SUCCESS)
            ->where('dinner', $dinner)
            ->count();
        return $record;
    }

    public static function getOrderingCountByWithDinnerID($orderingDate, $dinnerID, $phone)
    {
        $record = self::where('phone', $phone)
            ->where('ordering_date', $orderingDate)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->where('pay', PayEnum::PAY_SUCCESS)
            ->where('d_id', $dinnerID)
            ->sum('count');
        return $record;
    }

    public static function getOrderingByWithDinnerID($orderingDate, $dinnerID, $phone, $orderID=0)
    {
        $record = self::where('phone', $phone)
            ->where(function ($query) use ($orderID) {
                if ($orderID) {
                    $query->where('id', '>', $orderID);
                }
            })
            ->where('ordering_date', $orderingDate)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->where('pay', PayEnum::PAY_SUCCESS)
            ->where('d_id', $dinnerID)
            ->order('create_time')
            ->select();
        return $record;
    }

    public static function userOrdering($phone, $consumption_time)
    {
        $orderings = self::where('phone', $phone)
            ->where('ordering_month', $consumption_time)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->where('pay', PayEnum::PAY_SUCCESS)
          //  ->fetchSql(true)
            ->select();
        return $orderings;
    }

    public static function getUserOrdering($u_id)
    {
        $orderings = self::where('u_id', $u_id)
            ->whereTime('ordering_date', '>=', date('Y-m-d'))
            ->where('state', CommonEnum::STATE_IS_OK)
            ->where('pay', PayEnum::PAY_SUCCESS)
            ->where('used', CommonEnum::STATE_IS_FAIL)
            ->select()->toArray();
        return $orderings;
    }

    public static function userOrderings($phone, $type, $canteen_id, $page, $size)
    {

        $orderings = self::where('phone', $phone)
            ->whereTime('ordering_date', '>=', date('Y-m-d'))
            ->where(function ($query) use ($canteen_id) {
                if (!empty($canteen_id)) {
                    $query->where('c_id', $canteen_id);
                }
            })
            ->where(function ($query) use ($type) {
                if ($type == OrderEnum::EAT_CANTEEN) {
                    $query->where('type', $type)->where('all_used', CommonEnum::STATE_IS_FAIL);
                } else if ($type == OrderEnum::EAT_OUTSIDER){
                    $query->where('type', $type) ->where('used', CommonEnum::STATE_IS_FAIL);

                }
            })
            ->where('pay', PayEnum::PAY_SUCCESS)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->field('id,canteen as address,if(type=1,"食堂","外卖") as type,create_time,dinner,money,ordering_date,count,c_id as canteen_id,canteen,consumption_type')
            ->paginate($size, false, ['page' => $page]);
        return $orderings;
    }

}