<?php
/**
 * Created by PhpStorm.
 * User: 明良
 * Date: 2019/9/5
 * Time: 23:19
 */

namespace app\api\model;


use app\api\service\LogService;
use app\lib\Date;
use app\lib\enum\CommonEnum;
use app\lib\enum\OrderEnum;
use app\lib\enum\PayEnum;
use think\Db;

use think\Model;

class OrderingV extends Model
{
    public static function getRecordForDayOrdering($u_id, $ordering_date, $dinner)
    {
        /* $record = self::where('u_id', $u_id)
             ->where('ordering_date', $ordering_date)
             ->where('pay', PayEnum::PAY_SUCCESS)
           //  ->where('state', CommonEnum::STATE_IS_OK)
             ->where('dinner', $dinner)
             ->count();
         print_r($record);
         return 1;*/
        $records = Db::table('canteen_order_parent_t')
            ->field('a.id')
            ->alias('a')
            ->leftJoin('canteen_dinner_t b', 'a.dinner_id = b.id')
            ->where('a.u_id', $u_id)
            ->where('a.ordering_date', $ordering_date)
            ->where('b.name', $dinner)
            ->where('a.state', CommonEnum::STATE_IS_OK)
            ->where('a.pay', PayEnum::PAY_SUCCESS)
            ->unionAll(function ($query) use ($u_id, $ordering_date, $dinner) {
                $query->table('canteen_order_t')
                    ->field('a.id')
                    ->alias('a')
                    ->leftJoin('canteen_dinner_t b', 'a.d_id = b.id')
                    ->where('a.u_id', $u_id)
                    ->where('b.name', $dinner)
                    ->where('a.ordering_date', $ordering_date)
                    ->where('a.state', CommonEnum::STATE_IS_OK)
                    ->where('a.pay', PayEnum::PAY_SUCCESS);
            })->select();
        return count($records);
    }

    public static function getRecordForDayOrderingByPhone($ordering_date, $dinner, $phone)
    {
        /*    $record = self::where('phone', "$phone")
                ->where('ordering_date', $ordering_date)
                //->where('state', CommonEnum::STATE_IS_OK)
                ->where('pay', PayEnum::PAY_SUCCESS)
                ->where('dinner', $dinner)
                ->select()->toArray();
            return $record;*/
        $records = Db::field("	`a`.`id` AS `id`,
	`a`.`u_id` AS `u_id`,
	`a`.`ordering_type` AS `ordering_type`,
	`a`.`canteen_id` AS `c_id`,
	`b`.`name` AS `canteen`,
	`a`.`dinner_id` AS `d_id`,
	`c`.`name` AS `dinner`,
	`a`.`ordering_date` AS `ordering_date`,
	date_format( `a`.`ordering_date`, '%Y-%m' ) AS `ordering_month`,
	`a`.`count` AS `count`,
	`a`.`state` AS `state`,
	`a`.`used` AS `used`,
	`a`.`type` AS `type`,
	`a`.`create_time` AS `create_time`,
	(
		( `a`.`money` + `a`.`sub_money` ) + `a`.`delivery_fee` 
	) AS `money`,
	`a`.`phone` AS `phone`,
	`a`.`company_id` AS `company_id`,
	`a`.`pay` AS `pay`,
	`a`.`sub_money` AS `sub_money`,
	`a`.`delivery_fee` AS `delivery_fee`,
	'more' AS `consumption_type`,
	`a`.`fixed` AS `fixed`,
	`a`.`all_used` AS `all_used`,
	`a`.`receive` AS `receive`,
	`a`.`booking` AS `booking` ")
            ->table('canteen_order_parent_t')
            ->alias('a')
            ->leftJoin('canteen_dinner_t b', 'a.dinner_id = b.id')
            ->leftJoin('canteen_canteen_t c', 'a.canteen_id = c.id')
            ->where('a.phone', $phone)
            ->where('a.ordering_date', $ordering_date)
            ->where('b.name', $dinner)
            ->where('a.state', CommonEnum::STATE_IS_OK)
            ->where('a.pay', PayEnum::PAY_SUCCESS)
            ->unionAll(function ($query) use ($phone, $ordering_date, $dinner) {
                $query->field("`a`.`id` AS `id`,
	`a`.`u_id` AS `u_id`,
	`a`.`ordering_type` AS `ordering_type`,
	`a`.`c_id` AS `c_id`,
	`b`.`name` AS `canteen`,
	`a`.`d_id` AS `d_id`,
	`c`.`name` AS `dinner`,
	`a`.`ordering_date` AS `ordering_date`,
	date_format( `a`.`ordering_date`, '%Y-%m' ) AS `ordering_month`,
	`a`.`count` AS `count`,
	`a`.`state` AS `state`,
	`a`.`used` AS `used`,
	`a`.`type` AS `type`,
	`a`.`create_time` AS `create_time`,
	(
		( `a`.`money` + `a`.`sub_money` ) + `a`.`delivery_fee` 
	) AS `money`,
	`a`.`phone` AS `phone`,
	`a`.`company_id` AS `company_id`,
	`a`.`pay` AS `pay`,
	`a`.`sub_money` AS `sub_money`,
	`a`.`delivery_fee` AS `delivery_fee`,
	'one' AS `consumption_type`,
	`a`.`fixed` AS `fixed`,
	`a`.`used` AS `all_used`,
	`a`.`receive` AS `receive`,
	`a`.`booking` AS `booking` ")
                    ->table('canteen_order_t')
                    ->alias('a')
                    ->leftJoin('canteen_dinner_t b', 'a.d_id = b.id')
                    ->leftJoin('canteen_canteen_t c', 'a.c_id = c.id')
                    ->where('a.phone', $phone)
                    ->where('b.name', $dinner)
                    ->where('a.ordering_date', $ordering_date)
                    ->where('a.state', CommonEnum::STATE_IS_OK)
                    ->where('a.pay', PayEnum::PAY_SUCCESS);
            })->select()->toArray();
        return $records;
    }

    public static function getOrderingCountByPhone($ordering_date, $dinner, $phone)
    {
        /*     $record = self::where('phone', $phone)
                 ->where('ordering_date', $ordering_date)
                 ->where('state', CommonEnum::STATE_IS_OK)
                 ->where('pay', PayEnum::PAY_SUCCESS)
                 ->where('dinner', $dinner)
                 ->count();
             return $record;*/
        $records = Db::table('canteen_order_parent_t')
            ->field('a.id')
            ->alias('a')
            ->leftJoin('canteen_dinner_t b', 'a.dinner_id = b.id')
            ->where('a.phone', $phone)
            ->where('a.ordering_date', $ordering_date)
            ->where('b.name', $dinner)
            ->where('a.state', CommonEnum::STATE_IS_OK)
            ->where('a.pay', PayEnum::PAY_SUCCESS)
            ->unionAll(function ($query) use ($phone, $ordering_date, $dinner) {
                $query->table('canteen_order_t')
                    ->field('a.id')
                    ->alias('a')
                    ->leftJoin('canteen_dinner_t b', 'a.d_id = b.id')
                    ->where('a.phone', $phone)
                    ->where('b.name', $dinner)
                    ->where('a.ordering_date', $ordering_date)
                    ->where('a.state', CommonEnum::STATE_IS_OK)
                    ->where('a.pay', PayEnum::PAY_SUCCESS);
            })->select();
        return count($records);
    }

    public static function getOrderingCountByWithDinnerID($orderingDate, $dinnerID, $phone)
    {
        /*   $record = self::where('phone', $phone)
               ->where('ordering_date', $orderingDate)
              // ->where('state', CommonEnum::STATE_IS_OK)
               ->where('pay', PayEnum::PAY_SUCCESS)
               ->where('d_id', $dinnerID)
               ->sum('count');
           return $record;*/

        $records = Db::table('canteen_order_parent_t')
            ->field('a.id,a.count')
            ->alias('a')
            ->leftJoin('canteen_dinner_t b', 'a.dinner_id = b.id')
            ->where('a.phone', $phone)
            ->where('a.ordering_date', $orderingDate)
            ->where('b.id', $dinnerID)
            ->where('a.state', CommonEnum::STATE_IS_OK)
            ->where('a.pay', PayEnum::PAY_SUCCESS)
            ->unionAll(function ($query) use ($phone, $orderingDate, $dinnerID) {
                $query->table('canteen_order_t')
                    ->field('a.id,a.count')
                    ->alias('a')
                    ->leftJoin('canteen_dinner_t b', 'a.d_id = b.id')
                    ->where('a.phone', $phone)
                    ->where('a.ordering_date', $orderingDate)
                    ->where('b.id', $dinnerID)
                    ->where('a.state', CommonEnum::STATE_IS_OK)
                    ->where('a.pay', PayEnum::PAY_SUCCESS);
            })->select()->toArray();
        return array_sum(array_column($records, 'count'));
    }

    public static function getOrderingByWithDinnerID($orderingDate, $dinnerID, $phone, $orderID = 0)
    {
        /*    $record = self::where('phone', $phone)
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
            return $record;*/

        $records = Db::field("	`a`.`id` AS `id`,
	`a`.`u_id` AS `u_id`,
	`a`.`ordering_type` AS `ordering_type`,
	`a`.`canteen_id` AS `c_id`,
	`b`.`name` AS `canteen`,
	`a`.`dinner_id` AS `d_id`,
	`c`.`name` AS `dinner`,
	`a`.`ordering_date` AS `ordering_date`,
	date_format( `a`.`ordering_date`, '%Y-%m' ) AS `ordering_month`,
	`a`.`count` AS `count`,
	`a`.`state` AS `state`,
	`a`.`used` AS `used`,
	`a`.`type` AS `type`,
	`a`.`create_time` AS `create_time`,
	(
		( `a`.`money` + `a`.`sub_money` ) + `a`.`delivery_fee` 
	) AS `money`,
	`a`.`phone` AS `phone`,
	`a`.`company_id` AS `company_id`,
	`a`.`pay` AS `pay`,
	`a`.`sub_money` AS `sub_money`,
	`a`.`delivery_fee` AS `delivery_fee`,
	'more' AS `consumption_type`,
	`a`.`fixed` AS `fixed`,
	`a`.`all_used` AS `all_used`,
	`a`.`receive` AS `receive`,
	`a`.`booking` AS `booking` ")
            ->table('canteen_order_parent_t')
            ->alias('a')
            ->leftJoin('canteen_canteen_t b', 'a.canteen_id = b.id')
            ->leftJoin('canteen_dinner_t c', 'a.dinner_id = c.id')
            ->where(function ($query) use ($orderID) {
                if ($orderID) {
                    $query->where('a.id', '>', $orderID);
                }
            })
            ->where('a.phone', $phone)
            ->where('a.ordering_date', $orderingDate)
            ->where('b.id', $dinnerID)
            ->where('a.state', CommonEnum::STATE_IS_OK)
            ->where('a.pay', PayEnum::PAY_SUCCESS)
            ->order('create_time')
            ->unionAll(function ($query) use ($phone, $orderingDate, $dinnerID, $orderID) {
                $query->field("`a`.`id` AS `id`,
	`a`.`u_id` AS `u_id`,
	`a`.`ordering_type` AS `ordering_type`,
	`a`.`c_id` AS `c_id`,
	`b`.`name` AS `canteen`,
	`a`.`d_id` AS `d_id`,
	`c`.`name` AS `dinner`,
	`a`.`ordering_date` AS `ordering_date`,
	date_format( `a`.`ordering_date`, '%Y-%m' ) AS `ordering_month`,
	`a`.`count` AS `count`,
	`a`.`state` AS `state`,
	`a`.`used` AS `used`,
	`a`.`type` AS `type`,
	`a`.`create_time` AS `create_time`,
	(
		( `a`.`money` + `a`.`sub_money` ) + `a`.`delivery_fee` 
	) AS `money`,
	`a`.`phone` AS `phone`,
	`a`.`company_id` AS `company_id`,
	`a`.`pay` AS `pay`,
	`a`.`sub_money` AS `sub_money`,
	`a`.`delivery_fee` AS `delivery_fee`,
	'one' AS `consumption_type`,
	`a`.`fixed` AS `fixed`,
	`a`.`used` AS `all_used`,
	`a`.`receive` AS `receive`,
	`a`.`booking` AS `booking` ")
                    ->table('canteen_order_t')
                    ->alias('a')
                    ->leftJoin('canteen_canteen_t b', 'a.c_id = b.id')
                    ->leftJoin('canteen_dinner_t c', 'a.d_id = c.id')
                    ->where(function ($query) use ($orderID) {
                        if ($orderID) {
                            $query->where('a.id', '>', $orderID);
                        }
                    })
                    ->where('a.phone', $phone)
                    ->where('a.ordering_date', $orderingDate)
                    ->where('b.id', $dinnerID)
                    ->where('a.state', CommonEnum::STATE_IS_OK)
                    ->where('a.pay', PayEnum::PAY_SUCCESS)
                    ->order('create_time')
                    ->order('create_time');
            })->select();
        return $records;

    }

    public static function userOrdering($phone, $consumption_time)
    {
        $date = Date::mFristAndLast2($consumption_time);
        $timeBegin = $date['fist'];
        $timeEnd = $date['last'];
        /*        $orderings = self::where('phone', $phone)
                    ->where('ordering_date',"<=", $timeEnd)
                    ->where('ordering_date',">=", $timeBegin)
                    ->where('booking', CommonEnum::STATE_IS_OK)
                    ->where('state', CommonEnum::STATE_IS_OK)
                    ->where('pay', PayEnum::PAY_SUCCESS)
                    ->select();
                return $orderings;*/

        $records = Db::field("	`a`.`id` AS `id`,
	`a`.`u_id` AS `u_id`,
	`a`.`ordering_type` AS `ordering_type`,
	`a`.`canteen_id` AS `c_id`,
	`b`.`name` AS `canteen`,
	`a`.`dinner_id` AS `d_id`,
	`c`.`name` AS `dinner`,
	`a`.`ordering_date` AS `ordering_date`,
	date_format( `a`.`ordering_date`, '%Y-%m' ) AS `ordering_month`,
	`a`.`count` AS `count`,
	`a`.`state` AS `state`,
	`a`.`used` AS `used`,
	`a`.`type` AS `type`,
	`a`.`create_time` AS `create_time`,
	(
		( `a`.`money` + `a`.`sub_money` ) + `a`.`delivery_fee` 
	) AS `money`,
	`a`.`phone` AS `phone`,
	`a`.`company_id` AS `company_id`,
	`a`.`pay` AS `pay`,
	`a`.`sub_money` AS `sub_money`,
	`a`.`delivery_fee` AS `delivery_fee`,
	'more' AS `consumption_type`,
	`a`.`fixed` AS `fixed`,
	`a`.`all_used` AS `all_used`,
	`a`.`receive` AS `receive`,
	`a`.`booking` AS `booking` ")
            ->table('canteen_order_parent_t')
            ->alias('a')
            ->leftJoin('canteen_canteen_t b', 'a.canteen_id = b.id')
            ->leftJoin('canteen_dinner_t c', 'a.dinner_id = c.id')
            ->where('a.phone', $phone)
            ->where('a.booking', CommonEnum::STATE_IS_OK)
            ->where('a.ordering_date', "<=", $timeEnd)
            ->where('a.ordering_date', ">=", $timeBegin)
            ->where('a.state', CommonEnum::STATE_IS_OK)
            ->where('a.pay', PayEnum::PAY_SUCCESS)
            ->unionAll(function ($query) use ($phone, $timeBegin, $timeEnd) {
                $query->field("`a`.`id` AS `id`,
	`a`.`u_id` AS `u_id`,
	`a`.`ordering_type` AS `ordering_type`,
	`a`.`c_id` AS `c_id`,
	`b`.`name` AS `canteen`,
	`a`.`d_id` AS `d_id`,
	`c`.`name` AS `dinner`,
	`a`.`ordering_date` AS `ordering_date`,
	date_format( `a`.`ordering_date`, '%Y-%m' ) AS `ordering_month`,
	`a`.`count` AS `count`,
	`a`.`state` AS `state`,
	`a`.`used` AS `used`,
	`a`.`type` AS `type`,
	`a`.`create_time` AS `create_time`,
	(
		( `a`.`money` + `a`.`sub_money` ) + `a`.`delivery_fee` 
	) AS `money`,
	`a`.`phone` AS `phone`,
	`a`.`company_id` AS `company_id`,
	`a`.`pay` AS `pay`,
	`a`.`sub_money` AS `sub_money`,
	`a`.`delivery_fee` AS `delivery_fee`,
	'one' AS `consumption_type`,
	`a`.`fixed` AS `fixed`,
	`a`.`used` AS `all_used`,
	`a`.`receive` AS `receive`,
	`a`.`booking` AS `booking` ")
                    ->table('canteen_order_t')
                    ->alias('a')
                    ->leftJoin('canteen_canteen_t b', 'a.c_id = b.id')
                    ->leftJoin('canteen_dinner_t c', 'a.d_id = c.id')
                    ->where('a.phone', $phone)
                    ->where('a.booking', CommonEnum::STATE_IS_OK)
                    ->where('a.ordering_date', "<=", $timeEnd)
                    ->where('a.ordering_date', ">=", $timeBegin)
                    ->where('a.state', CommonEnum::STATE_IS_OK)
                    ->where('a.pay', PayEnum::PAY_SUCCESS);
            })->select();
        return $records;
    }

    public static function getUserOrdering($u_id)
    {
        /*  $orderings = self::where('u_id', $u_id)
              ->whereTime('ordering_date', '>=', date('Y-m-d'))
              ->where('state', CommonEnum::STATE_IS_OK)
              ->where('pay', PayEnum::PAY_SUCCESS)
              ->where('used', CommonEnum::STATE_IS_FAIL)
              ->select()->toArray();*/

        $records = Db::field("	`a`.`id` AS `id`,
	`a`.`u_id` AS `u_id`,
	`a`.`ordering_type` AS `ordering_type`,
	`a`.`canteen_id` AS `c_id`,
	`b`.`name` AS `canteen`,
	`a`.`dinner_id` AS `d_id`,
	`c`.`name` AS `dinner`,
	`a`.`ordering_date` AS `ordering_date`,
	date_format( `a`.`ordering_date`, '%Y-%m' ) AS `ordering_month`,
	`a`.`count` AS `count`,
	`a`.`state` AS `state`,
	`a`.`used` AS `used`,
	`a`.`type` AS `type`,
	`a`.`create_time` AS `create_time`,
	(
		( `a`.`money` + `a`.`sub_money` ) + `a`.`delivery_fee` 
	) AS `money`,
	`a`.`phone` AS `phone`,
	`a`.`company_id` AS `company_id`,
	`a`.`pay` AS `pay`,
	`a`.`sub_money` AS `sub_money`,
	`a`.`delivery_fee` AS `delivery_fee`,
	'more' AS `consumption_type`,
	`a`.`fixed` AS `fixed`,
	`a`.`all_used` AS `all_used`,
	`a`.`receive` AS `receive`,
	`a`.`booking` AS `booking` ")
            ->table('canteen_order_parent_t')
            ->alias('a')
            ->leftJoin('canteen_canteen_t b', 'a.canteen_id = b.id')
            ->leftJoin('canteen_dinner_t c', 'a.dinner_id = c.id')
            ->where('a.u_id', $u_id)
            ->where('a.ordering_date', '>=', date('Y-m-d'))
            ->where('a.state', CommonEnum::STATE_IS_OK)
            ->where('a.used', CommonEnum::STATE_IS_FAIL)
            ->where('a.pay', PayEnum::PAY_SUCCESS)
            ->unionAll(function ($query) use ($u_id) {
                $query->field("`a`.`id` AS `id`,
	`a`.`u_id` AS `u_id`,
	`a`.`ordering_type` AS `ordering_type`,
	`a`.`c_id` AS `c_id`,
	`b`.`name` AS `canteen`,
	`a`.`d_id` AS `d_id`,
	`c`.`name` AS `dinner`,
	`a`.`ordering_date` AS `ordering_date`,
	date_format( `a`.`ordering_date`, '%Y-%m' ) AS `ordering_month`,
	`a`.`count` AS `count`,
	`a`.`state` AS `state`,
	`a`.`used` AS `used`,
	`a`.`type` AS `type`,
	`a`.`create_time` AS `create_time`,
	(
		( `a`.`money` + `a`.`sub_money` ) + `a`.`delivery_fee` 
	) AS `money`,
	`a`.`phone` AS `phone`,
	`a`.`company_id` AS `company_id`,
	`a`.`pay` AS `pay`,
	`a`.`sub_money` AS `sub_money`,
	`a`.`delivery_fee` AS `delivery_fee`,
	'one' AS `consumption_type`,
	`a`.`fixed` AS `fixed`,
	`a`.`used` AS `all_used`,
	`a`.`receive` AS `receive`,
	`a`.`booking` AS `booking` ")
                    ->table('canteen_order_t')
                    ->alias('a')
                    ->leftJoin('canteen_canteen_t b', 'a.c_id = b.id')
                    ->leftJoin('canteen_dinner_t c', 'a.d_id = c.id')
                    ->where('a.u_id', $u_id)
                    ->where('a.ordering_date', '>=', date('Y-m-d'))
                    ->where('a.state', CommonEnum::STATE_IS_OK)
                    ->where('a.used', CommonEnum::STATE_IS_FAIL)
                    ->where('a.pay', PayEnum::PAY_SUCCESS);
            })->select()->toArray();

        return $records;
    }

    public static function userOrderings($phone, $type, $canteen_id, $page, $size)
    {

        /*      $orderings = self::where('phone', $phone)
                   //->whereTime('ordering_date', '>=', date('Y-m-d'))
                   ->where(function ($query) use ($canteen_id) {
                       if (!empty($canteen_id)) {
                           $query->where('c_id', $canteen_id);
                       }
                   })
                   ->where(function ($query) use ($type) {
                       if ($type == OrderEnum::EAT_CANTEEN) {
                           $query->where('type', $type)
                               ->where('all_used', CommonEnum::STATE_IS_FAIL);
                       } else if ($type == OrderEnum::EAT_OUTSIDER) {
                           $query->where('type', $type)
                               ->where('used', CommonEnum::STATE_IS_FAIL);

                       }
                   })
                   ->where('pay', PayEnum::PAY_SUCCESS)
                   ->where('state', CommonEnum::STATE_IS_OK)
                   ->field('id,canteen as address,if(type=1,"食堂","外卖") as type,create_time,dinner,money,ordering_date,count,c_id as canteen_id,canteen,consumption_type')
                   ->paginate($size, false, ['page' => $page]);
        return $orderings;*/

        $sql = Db::field('a.id,c.name as address,if(a.type=1,"食堂","外卖") as type,a.create_time,b.name as dinner,(a.money+a.sub_money+a.delivery_fee) as money,a.ordering_date,a.count,a.canteen_id as canteen_id,c.name as canteen,"more" as consumption_type')
            ->table('canteen_order_parent_t')
            ->alias('a')
            ->leftJoin('canteen_dinner_t b', 'a.dinner_id = b.id')
            ->leftJoin('canteen_canteen_t c', 'a.canteen_id = c.id')
            ->where('a.phone', $phone)
            ->whereTime('a.ordering_date', '>=', date('Y-m-d'))
            ->where(function ($query) use ($canteen_id) {
                if (!empty($canteen_id)) {
                    $query->where('a.canteen_id', $canteen_id);
                }
            })
            ->where(function ($query) use ($type) {
                if ($type == OrderEnum::EAT_CANTEEN) {
                    $query->where('a.type', $type)
                        ->where('a.all_used', CommonEnum::STATE_IS_FAIL);
                } else if ($type == OrderEnum::EAT_OUTSIDER) {
                    $query->where('a.type', $type)
                        ->where('a.used', CommonEnum::STATE_IS_FAIL);

                }
            })
            ->where('a.state', CommonEnum::STATE_IS_OK)
            ->where('a.pay', PayEnum::PAY_SUCCESS)
            ->unionAll(function ($query) use ($phone, $type, $canteen_id, $page, $size) {
                $query->field('a.id,c.name as address,if(a.type=1,"食堂","外卖") as type,a.create_time,b.name as dinner,(a.money+a.sub_money+a.delivery_fee) as money,a.ordering_date,a.count,a.c_id as canteen_id,c.name as canteen,"one" as consumption_type')
                    ->table('canteen_order_t')
                    ->alias('a')
                    ->leftJoin('canteen_dinner_t b', 'a.d_id = b.id')
                    ->leftJoin('canteen_canteen_t c', 'a.c_id = c.id')
                    ->where('a.phone', $phone)
                    ->whereTime('a.ordering_date', '>=', date('Y-m-d'))
                    ->where(function ($query) use ($canteen_id) {
                        if (!empty($canteen_id)) {
                            $query->where('a.c_id', $canteen_id);
                        }
                    })
                    ->where(function ($query) use ($type) {
                        $query->where('a.type', $type)
                            ->where('a.used', CommonEnum::STATE_IS_FAIL);
                    })
                    ->where('a.state', CommonEnum::STATE_IS_OK)
                    ->where('a.pay', PayEnum::PAY_SUCCESS);
            })->buildSql();
        $records = Db::table($sql . 'a')
            ->paginate($size, false, ['page' => $page]);
        return $records;
    }

}