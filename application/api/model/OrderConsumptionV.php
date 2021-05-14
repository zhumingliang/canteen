<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use app\lib\enum\PayEnum;
use think\Db;
use think\Model;

class OrderUsersStatisticV extends Model
{


    public function foods()
    {
        return $this->hasMany('OrderDetailT', 'o_id', 'id');
    }

    public static function getBuildSql($canteen_id, $consumption_time, $department_id)
    {
        $subQuery = Db::table('canteen_order_t')
            ->alias('a')
            ->field("	`a`.`id` AS `id`,
	`a`.`id` AS `parent_id`,
	`a`.`order_num` AS `order_num`,
	`a`.`c_id` AS `c_id`,
	`a`.`id` AS `order_id`,
	`a`.`d_id` AS `dinner_id`,
	`a`.`u_id` AS `u_id`,
	`a`.`ordering_date` AS `ordering_date`,
	`a`.`used` AS `used`,
	`a`.`booking` AS `booking`,
	`a`.`phone` AS `phone`,
	`b`.`username` AS `username`,
	`a`.`count` AS `count`,
	`a`.`money` AS `money`,
	`a`.`sub_money` AS `sub_money`,
	`a`.`delivery_fee` AS `delivery_fee`,
	`a`.`sort_code` AS `sort_code`,
	`a`.`outsider` AS `outsider`,
	`a`.`pay` AS `pay`,
	`a`.`consumption_type` AS `consumption_type`,
	'one' AS `strategy_type`,
	`c`.`meal_time_end` AS `meal_time_end`,
	1 AS `order_sort`,
	`a`.`meal_money` AS `meal_money`,
	`a`.`meal_sub_money` AS `meal_sub_money`,
	( `a`.`money` + `a`.`sub_money` ) AS `parent_money`,
	`a`.`type` AS `type`,
	`a`.`ordering_type` AS `ordering_type`,
	`a`.`pay_way` AS `pay_way`,
	`a`.`staff_id` AS `staff_id`,
	`a`.`d_id` AS `d_id`,
	`a`.`state` AS `state`,
	`a`.`wx_confirm` AS `wx_confirm`,
	`a`.`take` AS `take`,
	`a`.`order_num` AS `parent_order_num`,
	`a`.`department_id` AS `department_id`,
	`d`.`name` AS `department`,
	`a`.`fixed` AS `fixed` ")
            ->leftJoin('canteen_company_staff_t b', "a.staff_id = b.id")
            ->leftJoin('canteen_dinner_t c', "a.d_id = c.id")
            ->leftJoin('canteen_company_department_t d', "a.department_id = d.id")
            ->where('a.c_id', $canteen_id)
            ->where('a.pay', PayEnum::PAY_SUCCESS)
            ->where('a.ordering_date', $consumption_time)
            ->where('a.state',CommonEnum::STATE_IS_OK)
            ->where(function ($query) use ($department_id) {
                if ($department_id) {
                    $query->where('a.department_id', $department_id);
                }
            })
            ->unionAll(
                function ($query) use ($canteen_id, $consumption_time, $department_id) {
                    $query->table("canteen_order_sub_t")
                        ->alias('a')
                        ->field("	`a`.`id` AS `id`,
	`a`.`order_id` AS `parent_id`,
	`a`.`order_num` AS `order_num`,
	`b`.`canteen_id` AS `c_id`,
	`a`.`order_id` AS `order_id`,
	`b`.`dinner_id` AS `dinner_id`,
	`b`.`u_id` AS `u_id`,
	`b`.`ordering_date` AS `ordering_date`,
	`a`.`used` AS `used`,
	`b`.`booking` AS `booking`,
	`b`.`phone` AS `phone`,
	`c`.`username` AS `username`,
	`a`.`count` AS `count`,
	`a`.`money` AS `money`,
	`a`.`sub_money` AS `sub_money`,
	`b`.`delivery_fee` AS `delivery_fee`,
	`a`.`sort_code` AS `sort_code`,
	`b`.`outsider` AS `outsider`,
	`b`.`pay` AS `pay`,
	`a`.`consumption_type` AS `consumption_type`,
	'more' AS `strategy_type`,
	`d`.`meal_time_end` AS `meal_time_end`,
	`a`.`order_sort` AS `order_sort`,
	`a`.`meal_money` AS `meal_money`,
	`a`.`meal_sub_money` AS `meal_sub_money`,
	( `b`.`money` + `b`.`sub_money` ) AS `parent_money`,
	`b`.`type` AS `type`,
	`b`.`ordering_type` AS `ordering_type`,
	`a`.`pay_way` AS `pay_way`,
	`b`.`staff_id` AS `staff_id`,
	`b`.`dinner_id` AS `d_id`,
	`a`.`state` AS `state`,
	`a`.`wx_confirm` AS `wx_confirm`,
	`a`.`take` AS `take`,
	`b`.`order_num` AS `parent_order_num`,
	`b`.`department_id` AS `department_id`,
	`e`.`name` AS `department`,
	`b`.`fixed` AS `fixed`")
                        ->leftJoin('canteen_order_parent_t b', "a.order_id = b.id")
                        ->leftJoin('canteen_company_staff_t c', "b.staff_id = c.id")
                        ->leftJoin('canteen_dinner_t d', "b.dinner_id = d.id")
                        ->leftJoin('canteen_company_department_t e', "b.department_id = e.id")
                        ->where('b.canteen_id', $canteen_id)
                        ->where('b.pay', PayEnum::PAY_SUCCESS)
                        ->where('b.ordering_date', $consumption_time)
                        ->where('a.state',CommonEnum::STATE_IS_OK)
                        ->where(function ($query2) use ($department_id) {
                            if ($department_id) {
                                $query2->where('b.department_id', $department_id);
                            }
                        });
                })
            ->unionAll(
                function ($query) use ($canteen_id, $consumption_time, $department_id) {
                    $query->table("canteen_company_staff_t")
                        ->alias('a')
                        ->field("	`c`.`id` AS `id`,
	`c`.`id` AS `parent_id`,
	`f`.`code` AS `order_num`,
	`c`.`canteen_id` AS `c_id`,
	`c`.`id` AS `order_id`,
	`c`.`dinner_id` AS `dinner_id`,
	`c`.`user_id` AS `u_id`,
	`c`.`ordering_date` AS `ordering_date`,
	`f`.`status` AS `used`,
	'1' AS `booking`,
	`a`.`phone` AS `phone`,
	`a`.`username` AS `username`,
	`c`.`count` AS `count`,
	`c`.`money` AS `money`,
	'0' AS `sub_money`,
	'0' AS `delivery_fee`,
	'1' AS `sort_code`,
	'1' AS `outsider`,
	'paid' AS `pay`,
	'ordering_meals' AS `consumption_type`,
	'more' AS `strategy_type`,
	`e`.`meal_time_end` AS `meal_time_end`,
	'1' AS `order_sort`,
	'0' AS `meal_money`,
	'0' AS `meal_sub_money`,
	`c`.`money` AS `parent_money`,
	'1' AS `type`,
	'online' AS `ordering_type`,
	'4' AS `pay_way`,
	`c`.`staff_id` AS `staff_id`,
	`c`.`dinner_id` AS `d_id`,
	'1' AS `state`,
	'1' AS `wx_confirm`,
	'1' AS `take`,
	`c`.`code_number` AS `parent_order_num`,
	`a`.`d_id` AS `department_id`,
	`g`.`name` AS `department`,
	'1' AS `fixed`")
                        ->leftJoin('canteen_company_t b',"`a`.`company_id`=`b`.id")
                        ->leftJoin('canteen_reception_t c', "`a`.`id` = `c`.`staff_id`")
                        ->leftJoin('canteen_canteen_t d', " `c`.`canteen_id` = `d`.`id`")
                        ->leftJoin('canteen_dinner_t e', "`c`.`dinner_id` = `e`.`id`")
                        ->leftJoin("canteen_reception_qrcode_t f", "`c`.`id` = `f`.`re_id`")
                        ->leftJoin("canteen_company_department_t g", "`a`.`d_id` = `g`.`id`")
                        ->where('c.canteen_id', $canteen_id)
                        ->where('c.ordering_date', $consumption_time)
                        ->where('f.status',CommonEnum::STATE_IS_OK)
                        ->where(function ($query2) use ($department_id) {
                            if ($department_id) {
                                $query2->where('a.d_id', $department_id);
                            }
                        });
                }
            )
            ->buildSql();
        return $subQuery;
    }


    public static function getBuildSql2($canteen_id, $dinner_id, $consumption_time, $consumption_type, $department_id)
    {
        $subQuery = Db::table('canteen_order_t')
            ->alias('a')
            ->field("	`a`.`id` AS `id`,
	`a`.`id` AS `parent_id`,
	`a`.`order_num` AS `order_num`,
	`a`.`c_id` AS `c_id`,
	`a`.`id` AS `order_id`,
	`a`.`d_id` AS `dinner_id`,
	`a`.`u_id` AS `u_id`,
	`a`.`ordering_date` AS `ordering_date`,
	`a`.`used` AS `used`,
	`a`.`booking` AS `booking`,
	`a`.`phone` AS `phone`,
	`b`.`username` AS `username`,
	`a`.`count` AS `count`,
	`a`.`money` AS `money`,
	`a`.`sub_money` AS `sub_money`,
	`a`.`delivery_fee` AS `delivery_fee`,
	`a`.`sort_code` AS `sort_code`,
	`a`.`outsider` AS `outsider`,
	`a`.`pay` AS `pay`,
	`a`.`consumption_type` AS `consumption_type`,
	'one' AS `strategy_type`,
	`c`.`meal_time_end` AS `meal_time_end`,
	1 AS `order_sort`,
	`a`.`meal_money` AS `meal_money`,
	`a`.`meal_sub_money` AS `meal_sub_money`,
	( `a`.`money` + `a`.`sub_money` ) AS `parent_money`,
	`a`.`type` AS `type`,
	`a`.`ordering_type` AS `ordering_type`,
	`a`.`pay_way` AS `pay_way`,
	`a`.`staff_id` AS `staff_id`,
	`a`.`d_id` AS `d_id`,
	`a`.`state` AS `state`,
	`a`.`wx_confirm` AS `wx_confirm`,
	`a`.`take` AS `take`,
	`a`.`order_num` AS `parent_order_num`,
	`a`.`department_id` AS `department_id`,
	`d`.`name` AS `department`,
	`a`.`fixed` AS `fixed` ")
            ->leftJoin('canteen_company_staff_t b', "a.staff_id = b.id")
            ->leftJoin('canteen_dinner_t c', "a.d_id = c.id")
            ->leftJoin('canteen_company_department_t d', "a.department_id = d.id")
            ->where(function ($query) use ($dinner_id) {
                if ($dinner_id) {
                    $query->where('a.d_id', $dinner_id);
                }
            })
            //   ->where('a.c_id', $canteen_id)
            ->where('a.pay', PayEnum::PAY_SUCCESS)
            ->where('a.ordering_date', $consumption_time)
            ->where('a.state',CommonEnum::STATE_IS_OK)
            ->where(function ($query) use ($consumption_type) {
                if ($consumption_type == 'used') {
                    $query->where('a.booking', CommonEnum::STATE_IS_OK)
                        ->where('a.used', CommonEnum::STATE_IS_OK);
                } else if ($consumption_type == 'noOrdering') {
                    $query->where('a.booking', CommonEnum::STATE_IS_FAIL);
                } else if ($consumption_type == 'orderingNoMeal') {
                    $query->where('a.used', CommonEnum::STATE_IS_FAIL);
                }
            })
            ->where(function ($query) use ($department_id) {
                if ($department_id) {
                    $query->where('a.department_id', $department_id);
                }
            })
            ->unionAll(
                function ($query) use ($canteen_id, $consumption_time, $department_id,$dinner_id,$consumption_type) {
                    $query->table("canteen_order_sub_t")
                        ->alias('a')
                        ->field("	`a`.`id` AS `id`,
	`a`.`order_id` AS `parent_id`,
	`a`.`order_num` AS `order_num`,
	`b`.`canteen_id` AS `c_id`,
	`a`.`order_id` AS `order_id`,
	`b`.`dinner_id` AS `dinner_id`,
	`b`.`u_id` AS `u_id`,
	`b`.`ordering_date` AS `ordering_date`,
	`a`.`used` AS `used`,
	`b`.`booking` AS `booking`,
	`b`.`phone` AS `phone`,
	`c`.`username` AS `username`,
	`a`.`count` AS `count`,
	`a`.`money` AS `money`,
	`a`.`sub_money` AS `sub_money`,
	`b`.`delivery_fee` AS `delivery_fee`,
	`a`.`sort_code` AS `sort_code`,
	`b`.`outsider` AS `outsider`,
	`b`.`pay` AS `pay`,
	`a`.`consumption_type` AS `consumption_type`,
	'more' AS `strategy_type`,
	`d`.`meal_time_end` AS `meal_time_end`,
	`a`.`order_sort` AS `order_sort`,
	`a`.`meal_money` AS `meal_money`,
	`a`.`meal_sub_money` AS `meal_sub_money`,
	( `b`.`money` + `b`.`sub_money` ) AS `parent_money`,
	`b`.`type` AS `type`,
	`b`.`ordering_type` AS `ordering_type`,
	`a`.`pay_way` AS `pay_way`,
	`b`.`staff_id` AS `staff_id`,
	`b`.`dinner_id` AS `d_id`,
	`a`.`state` AS `state`,
	`a`.`wx_confirm` AS `wx_confirm`,
	`a`.`take` AS `take`,
	`b`.`order_num` AS `parent_order_num`,
	`b`.`department_id` AS `department_id`,
	`e`.`name` AS `department`,
	`b`.`fixed` AS `fixed`")
                        ->leftJoin('canteen_order_parent_t b', "a.order_id = b.id")
                        ->leftJoin('canteen_company_staff_t c', "b.staff_id = c.id")
                        ->leftJoin('canteen_dinner_t d', "b.dinner_id = d.id")
                        ->leftJoin('canteen_company_department_t e', "b.department_id = e.id")
                        ->where(function ($query) use ($dinner_id) {
                            if ($dinner_id) {
                                $query->where('b.dinner_id', $dinner_id);
                            }
                        })
                        //  ->where('b.canteen_id', $canteen_id)
                        ->where('b.pay', PayEnum::PAY_SUCCESS)
                        ->where('b.ordering_date', $consumption_time)
                        ->where('a.state',CommonEnum::STATE_IS_OK)
                        ->where(function ($query2) use ($consumption_type) {
                            if ($consumption_type == 'used') {
                                $query2->where('b.booking', CommonEnum::STATE_IS_OK)
                                    ->where('a.used', CommonEnum::STATE_IS_OK);
                            } else if ($consumption_type == 'noOrdering') {
                                $query2->where('b.booking', CommonEnum::STATE_IS_FAIL);
                            } else if ($consumption_type == 'orderingNoMeal') {
                                $query2->where('a.used', CommonEnum::STATE_IS_FAIL);
                            }
                        })
                        ->where(function ($query2) use ($department_id) {
                            if ($department_id) {
                                $query2->where('b.department_id', $department_id);
                            }
                        });
                }
            )
            ->buildSql();
        return $subQuery;
    }

    public static function orderUsers($canteen_id, $dinner_id, $consumption_time, $consumption_type, $key, $page, $size, $department_id)
    {

        $sql = self::getBuildSql2($canteen_id, $dinner_id, $consumption_time, $consumption_type, $department_id);
        $statistic = Db::table($sql . ' a')
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
            ->field('order_id as id,username,order_num,phone,sum(count) as count,strategy_type as consumption_type,type,dinner_id,booking,used,department,fixed')
            ->group('order_id')
            ->paginate($size, false, ['page' => $page])
            ->toArray();
        return $statistic;

        /*     $users = self::where(function ($query) use ($dinner_id) {
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
                 ->field('order_id as id,username,order_num,phone,sum(count) as count,strategy_type as consumption_type,type,dinner_id,booking,used,department,fixed')
                 ->group('order_id')
                 ->paginate($size, false, ['page' => $page])
                 ->toArray();
             return $users;*/
    }

    public static function statisticToOfficial($canteen_id, $consumption_time, $key, $department_id)
    {
        $sql = self::getBuildSql($canteen_id, $consumption_time, $department_id);
        $statistic = Db::table($sql . ' a')
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
            ->field('dinner_id as d_id,used,booking,sum(count) as count')
            ->group('dinner_id,used,booking')
            ->select()->toArray();
        return $statistic;
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