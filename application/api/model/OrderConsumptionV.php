<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use app\lib\enum\OrderEnum;
use app\lib\enum\PayEnum;
use think\Db;
use think\Model;

class OrderConsumptionV extends Model
{

    public function getStatusAttr($value)
    {
        $status = ['1' => '订餐就餐', 2 => '订餐未就餐', 3 => '未订餐就餐', 4 => '系统补充', 5 => '系统补扣', 6 => "小卖部消费", 7 => "小卖部退款"];
        return $status[$value];
    }

    public function getOrderMoneyAttr($value)
    {
        return round($value, 2);

    }

    public static function getBuildSql()
    {
        $sql = Db::field("	`a`.`id` AS `order_id`,
	`a`.`type` AS `type`,
	`a`.`d_id` AS `dinner_id`,
	`b`.`name` AS `dinner`,
	`a`.`c_id` AS `canteen_id`,
	`c`.`name` AS `canteen`,
	`a`.`company_id` AS `company_id`,
	`d`.`name` AS `company`,
	`a`.`ordering_date` AS `consumption_date`,
	`a`.`department_id` AS `department_id`,
	`e`.`name` AS `department`,
	`f`.`username` AS `username`,
	`f`.`phone` AS `phone`,
IF
	(
		( `a`.`booking` = 2 ),
		3,
	IF
		(
			( `a`.`used` = 2 ),
			2,
			1 
		) 
	) AS `status`,
	(
		( `a`.`money` + `a`.`sub_money` ) + `a`.`delivery_fee` 
	) AS `order_money`,
	`f`.`t_id` AS `staff_type_id`,
	`g`.`name` AS `staff_type`,
	`a`.`count` AS `order_count`,
	`a`.`staff_id` AS `staff_id`,
	'canteen' AS `location` ")
            ->table('canteen_order_t')->alias('a')
            ->leftJoin('canteen_dinner_t b', '`a`.`d_id` = `b`.`id`')
            ->leftJoin('canteen_canteen_t c', '`a`.`c_id` = `c`.`id` ')
            ->leftJoin('canteen_company_t d', '`a`.`company_id` = `d`.`id`')
            ->leftJoin('canteen_company_department_t e', '`a`.`department_id` = `e`.`id`')
            ->leftJoin('canteen_company_staff_t f', '`a`.`staff_id` = `f`.`id` ')
            ->leftJoin('canteen_staff_type_t g', '`f`.`t_id` = `g`.`id`')
            ->where('a.state', CommonEnum::STATE_IS_OK)
            ->where('a.pay', PayEnum::PAY_SUCCESS)
            ->unionAll(function ($query) {
                $query->field("`a`.`id` AS `order_id`,1 AS `type`,`a`.`dinner_id` AS `dinner_id`,`f`.`name` AS `dinner`,`a`.`canteen_id` AS `canteen_id`,`g`.`name` AS `canteen`,`a`.`company_id` AS `company_id`,`b`.`name` AS `company`,`a`.`consumption_date` AS `consumption_date`,`c`.`d_id` AS `department_id`,`d`.`name` AS `department`,`c`.`username` AS `username`,`c`.`phone` AS `phone`,IF ((`a`.`type`=1),4,5) AS `status`,(0-`a`.`money`) AS `order_money`,`c`.`t_id` AS `staff_type_id`,`e`.`name` AS `staff_type`,1 AS `order_count`,`a`.`staff_id` AS `staff_id`,'canteen' AS `location`")
                    ->table('canteen_recharge_supplement_t')->alias('a')
                    ->leftJoin('canteen_company_t b', "`a`.`company_id` = `b`.`id`")
                    ->leftJoin('canteen_company_staff_t c', "`a`.`staff_id` = `c`.`id`")
                    ->leftJoin('canteen_company_department_t d', "`c`.`d_id` = `d`.`id`")
                    ->leftJoin('canteen_staff_type_t e', " `c`.`t_id` = `e`.`id`")
                    ->leftJoin('canteen_dinner_t f', '`a`.`dinner_id` = `f`.`id`')
                    ->leftJoin('canteen_canteen_t g', '`a`.`canteen_id` = `g`.`id` ');

            })->unionAll(function ($query) {
                $query->field("`h`.`id` AS `order_id`,`a`.`type` AS `type`,`a`.`dinner_id` AS `dinner_id`,`b`.`name` AS `dinner`,`a`.`canteen_id` AS `canteen_id`,`c`.`name` AS `canteen`,`a`.`company_id` AS `company_id`,`d`.`name` AS `company`,`a`.`ordering_date` AS `consumption_date`,`a`.`department_id` AS `department_id`,`e`.`name` AS `department`,`f`.`username` AS `username`,`f`.`phone` AS `phone`,IF ((`a`.`booking`=2),3,IF ((`h`.`used`=2),2,1)) AS `status`,((`h`.`money`+`h`.`sub_money`)+(`a`.`delivery_fee`/`a`.`count`)) AS `order_money`,`f`.`t_id` AS `staff_type_id`,`g`.`name` AS `staff_type`,`h`.`count` AS `order_count`,`a`.`staff_id` AS `staff_id`,'canteen' AS `location`")
                    ->table('canteen_order_sub_t')->alias('h')
                    ->leftJoin('canteen_order_parent_t a', "`h`.`order_id` = `a`.`id`")
                    ->leftJoin('canteen_dinner_t b', '`a`.`dinner_id` = `b`.`id`')
                    ->leftJoin('canteen_canteen_t c', '`a`.`canteen_id` = `c`.`id` ')
                    ->leftJoin('canteen_company_t d', '`a`.`company_id` = `d`.`id`')
                    ->leftJoin('canteen_company_department_t e', '`a`.`department_id` = `e`.`id`')
                    ->leftJoin('canteen_company_staff_t f', '`a`.`staff_id` = `f`.`id` ')
                    ->leftJoin('canteen_staff_type_t g', '`f`.`t_id` = `g`.`id`')
                    ->where('h.state', CommonEnum::STATE_IS_OK)
                    ->where('a.pay', PayEnum::PAY_SUCCESS);

            })->unionAll(function ($query) {
                $query->field("`a`.`id` AS `order_id`,1 AS `type`,0 AS `dinner_id`,IF ((`a`.`money`> 0),'小卖部消费','小卖部退款') AS `dinner`,`a`.`shop_id` AS `canteen_id`,`d`.`name` AS `canteen`,`a`.`company_id` AS `company_id`,`e`.`name` AS `company`,date_format(`a`.`create_time`,'%Y%-%m%-%d') AS `consumption_date`,`b`.`d_id` AS `department_id`,`c`.`name` AS `department`,`b`.`username` AS `username`,`a`.`phone` AS `phone`,IF ((`a`.`money`> 0),6,7) AS `status`,`a`.`money` AS `order_money`,`b`.`t_id` AS `staff_type_id`,`f`.`name` AS `staff_type`,1 AS `order_count`,`a`.`staff_id` AS `staff_id`,'shop' AS `location`")
                    ->table('canteen_shop_order_t')->alias('a')
                    ->leftJoin('canteen_company_staff_t b', "`a`.`staff_id` = `b`.`id`")
                    ->leftJoin('canteen_company_department_t c', '`b`.`d_id` = `c`.`id`')
                    ->leftJoin('canteen_shop_t d', '`a`.`shop_id` = `d`.`id`')
                    ->leftJoin('canteen_company_t e', ' `a`.`company_id` = `e`.`id`')
                    ->leftJoin('canteen_staff_type_t f', '`b`.`t_id` = `f`.`id`')
                    ->where('a.state', CommonEnum::STATE_IS_OK);

            })->buildSql();
        return $sql;
    }

    public static function consumptionStatisticByDepartment($canteen_id, $status, $department_id,
                                                            $username, $staff_type_id, $time_begin,
                                                            $time_end, $company_id, $phone, $order_type)
    {
        $statistic = self::where(function ($query) use ($company_id, $canteen_id) {
            if (!empty($canteen_id)) {
                $query->where('canteen_id', $canteen_id);
            } else {
                if (strpos($company_id, ',') !== false) {
                    $query->whereIn('company_id', $company_id);
                } else {
                    $query->where('company_id', $company_id);
                }
            }
        })
            ->where(function ($query) use ($order_type) {
                if ($order_type !== 'all') {
                    $query->where('location', $order_type);
                }
            })
            ->where('consumption_date', '>=', $time_begin)
            ->where('consumption_date', '<=', $time_end)
            ->where(function ($query) use (
                $status, $department_id,
                $username, $staff_type_id, $phone
            ) {
                if (!empty($status)) {
                    $query->where('status', $status);
                }
                if (!empty($department_id)) {
                    $query->where('department_id', $department_id);
                }
                if (!empty($phone)) {
                    $query->where('phone', $phone);
                }
                if (!empty($username)) {
                    $query->where('username', 'like', '%' . $username . '%');
                }
                if (!empty($staff_type_id)) {
                    $query->where('staff_type_id', $staff_type_id);
                }

            })
            ->field('department_id as statistic_id,department,dinner_id,dinner,sum(order_count) as order_count,sum(order_money) as order_money')
            ->group('department_id,dinner')
            ->select()
            ->toArray();
        return $statistic;
        $sql = self::getBuildSql();
        $statistic = Db::table($sql . 'a')
            ->where(function ($query) use ($company_id, $canteen_id) {
                if (!empty($canteen_id)) {
                    $query->where('a.canteen_id', $canteen_id);
                } else {
                    if (strpos($company_id, ',') !== false) {
                        $query->whereIn('a.company_id', $company_id);
                    } else {
                        $query->where('a.company_id', $company_id);
                    }
                }
            })
            ->where(function ($query) use ($order_type) {
                if ($order_type !== 'all') {
                    $query->where('a.location', $order_type);
                }
            })
            ->where('a.consumption_date', '>=', $time_begin)
            ->where('a.consumption_date', '<=', $time_end)
            ->where(function ($query) use (
                $status, $department_id,
                $username, $staff_type_id, $phone
            ) {
                if (!empty($status)) {
                    $query->where('a.status', $status);
                }
                if (!empty($department_id)) {
                    $query->where('a.department_id', $department_id);
                }
                if (!empty($phone)) {
                    $query->where('a.phone', $phone);
                }
                if (!empty($username)) {
                    $query->where('a.username', 'like', '%' . $username . '%');
                }
                if (!empty($staff_type_id)) {
                    $query->where('a.staff_type_id', $staff_type_id);
                }

            })
            ->field('department_id,department,dinner_id,dinner,sum(order_count) as order_count,sum(order_money) as order_money')
            ->group('a.department_id,a.dinner')
            ->select()
            ->toArray();
        return $statistic;
    }

    public static function consumptionStatisticByUsername($canteen_id, $status, $department_id,
                                                          $username, $staff_type_id, $time_begin,
                                                          $time_end, $company_id)
    {
        //$time_end = addDay(1, $time_end);
        $statistic = self::where(function ($query) use ($company_id, $canteen_id) {
            if (!empty($canteen_id)) {
                $query->where('canteen_id', $canteen_id);
            } else {
                if (strpos($company_id, ',') !== false) {
                    $query->whereIn('company_id', $company_id);
                } else {
                    $query->where('company_id', $company_id);
                }
            }
        })
            ->where('consumption_date', '>=', $time_begin)
            ->where('consumption_date', '<=', $time_end)
            ->where(function ($query) use (
                $status, $department_id,
                $username, $staff_type_id
            ) {
                if (!empty($status)) {
                    $query->where('status', $status);
                }
                if (!empty($department_id)) {
                    $query->where('department_id', $department_id);
                }
                if (!empty($username)) {
                    $query->where('username', 'like', '%' . $username . '%');
                }
                if (!empty($staff_type_id)) {
                    $query->where('staff_type_id', $staff_type_id);
                }

            })
            ->field('sum(order_count) as order_count,sum(order_money) as order_money')
            ->find();
        return $statistic;
    }


    public static function consumptionStatisticByUser($canteen_id, $status, $department_id,
                                                      $username, $staff_type_id, $time_begin,
                                                      $time_end, $company_id)
    {
        //$time_end = addDay(1, $time_end);
        $statistic = self::where(function ($query) use ($company_id, $canteen_id) {
            if (!empty($canteen_id)) {
                $query->where('canteen_id', $canteen_id);
            } else {
                if (strpos($company_id, ',') !== false) {
                    $query->whereIn('company_id', $company_id);
                } else {
                    $query->where('company_id', $company_id);
                }
            }
        })
            ->where('consumption_date', '>=', $time_begin)
            ->where('consumption_date', '<=', $time_end)
            ->where(function ($query) use (
                $status, $department_id,
                $username, $staff_type_id
            ) {
                if (!empty($status)) {
                    $query->where('status', $status);
                }
                if (!empty($department_id)) {
                    $query->where('department_id', $department_id);
                }
                if (!empty($username)) {
                    $query->where('username', 'like', '%' . $username . '%');
                }
                if (!empty($staff_type_id)) {
                    $query->where('staff_type_id', $staff_type_id);
                }

            })
            ->field('sum(order_count) as order_count,sum(order_money) as order_money')
            ->find();
        return $statistic;
    }


    public static function consumptionStatisticByStatus($canteen_id, $status, $department_id,
                                                        $username, $staff_type_id, $time_begin,
                                                        $time_end, $company_id, $phone, $order_type)
    {
        $statistic = self::where(function ($query) use ($company_id, $canteen_id) {
            if (!empty($canteen_id)) {
                $query->where('canteen_id', $canteen_id);
            } else {
                if (strpos($company_id, ',') !== false) {
                    $query->whereIn('company_id', $company_id);
                } else {
                    $query->where('company_id', $company_id);
                }
            }
        })
            ->where(function ($query) use ($order_type) {
                if ($order_type !== 'all') {
                    $query->where('location', $order_type);
                }
            })
            ->where('consumption_date', '>=', $time_begin)
            ->where('consumption_date', '<=', $time_end)
            ->where(function ($query) use (
                $status, $department_id,
                $username, $staff_type_id, $phone
            ) {
                if (!empty($phone)) {
                    $query->where('phone', $phone);
                }
                if (!empty($status)) {
                    $query->where('status', $status);
                }
                if (!empty($department_id)) {
                    $query->where('department_id', $department_id);
                }
                if (!empty($username)) {
                    $query->where('username', 'like', '%' . $username . '%');
                }
                if (!empty($staff_type_id)) {
                    $query->where('staff_type_id', $staff_type_id);
                }

            })
            ->field('status as statistic_id,status,department,dinner_id,dinner,sum(order_count) as order_count,sum(order_money) as order_money')
            ->group('status,dinner')
            ->select()
            ->toArray();
        return $statistic;
    }

    public static function consumptionStatisticByCanteen($canteen_id, $status, $department_id,
                                                         $username, $staff_type_id, $time_begin,
                                                         $time_end, $company_id, $phone, $order_type)
    {
        // $time_end = addDay(1, $time_end);
        $statistic = self::where(function ($query) use ($company_id, $canteen_id) {
            if (!empty($canteen_id)) {
                $query->where('canteen_id', $canteen_id);
            } else {
                if (strpos($company_id, ',') !== false) {
                    $query->whereIn('company_id', $company_id);
                } else {
                    $query->where('company_id', $company_id);
                }
            }
        })
            ->where(function ($query) use ($order_type) {
                if ($order_type !== 'all') {
                    $query->where('location', $order_type);
                }
            })
            ->where('consumption_date', '>=', $time_begin)
            ->where('consumption_date', '<=', $time_end)
            ->where(function ($query) use (
                $status, $department_id,
                $username, $staff_type_id, $phone
            ) {
                if (!empty($status)) {
                    $query->where('status', $status);
                }
                if (!empty($phone)) {
                    $query->where('phone', $phone);
                }
                if (!empty($department_id)) {
                    $query->where('department_id', $department_id);
                }
                if (!empty($username)) {
                    $query->where('username', 'like', '%' . $username . '%');
                }
                if (!empty($staff_type_id)) {
                    $query->where('staff_type_id', $staff_type_id);
                }

            })
            ->field('canteen_id as statistic_id,canteen,dinner_id,dinner,sum(order_count) as order_count,sum(order_money) as order_money')
            ->group('canteen_id,dinner')
            ->select()
            ->toArray();
        return $statistic;
    }

    public static function consumptionStatisticByStaff($canteen_id, $status, $department_id,
                                                       $username, $staff_type_id, $time_begin,
                                                       $time_end, $company_id, $phone, $order_type)
    {
        //$time_end = addDay(1, $time_end);
        $statistic = self::where(function ($query) use ($company_id, $canteen_id) {
            if (!empty($canteen_id)) {
                $query->where('canteen_id', $canteen_id);
            } else {
                if (strpos($company_id, ',') !== false) {
                    $query->whereIn('company_id', $company_id);
                } else {
                    $query->where('company_id', $company_id);
                }
            }
        })
            ->where(function ($query) use ($order_type) {
                if ($order_type !== 'all') {
                    $query->where('location', $order_type);
                }
            })
            ->where('consumption_date', '>=', $time_begin)
            ->where('consumption_date', '<=', $time_end)
            ->where(function ($query) use (
                $status, $department_id,
                $username, $staff_type_id, $phone
            ) {
                if (!empty($status)) {
                    $query->where('status', $status);
                }
                if (!empty($department_id)) {
                    $query->where('department_id', $department_id);
                }
                if (!empty($phone)) {
                    $query->where('phone', $phone);
                }
                if (!empty($username)) {
                    $query->where('username', 'like', '%' . $username . '%');
                }
                if (!empty($staff_type_id)) {
                    $query->where('staff_type_id', $staff_type_id);
                }

            })
            ->field('staff_type_id as statistic_id,staff_type,dinner_id,dinner,sum(order_count) as order_count,sum(order_money) as order_money')
            ->group('staff_type_id,dinner')
            ->select()
            ->toArray();
        return $statistic;
    }

    public static function userDinnerStatistic($canteen_id, $status, $department_id,
                                               $username, $staff_type_id, $time_begin,
                                               $time_end, $company_id, $phone, $order_type, $page, $size)
    {
        return self::where(function ($query) use ($company_id, $canteen_id) {
            if (!empty($canteen_id)) {
                $query->where('canteen_id', $canteen_id);
            } else {
                if (strpos($company_id, ',') !== false) {
                    $query->whereIn('company_id', $company_id);
                } else {
                    $query->where('company_id', $company_id);
                }
            }
        })->where(function ($query) use ($order_type) {
            if ($order_type !== 'all') {
                $query->where('location', $order_type);
            }
        })
            ->where(function ($query) use (
                $department_id,
                $username, $staff_type_id, $phone
            ) {
                if (!empty($department_id)) {
                    $query->where('department_id', $department_id);
                }
                if (!empty($phone)) {
                    $query->where('phone', $phone);
                }
                if (!empty($username)) {
                    $query->where('username', 'like', '%' . $username . '%');
                }
                if (!empty($status)) {
                    $query->where('staff_type_id', $staff_type_id);
                }

            })
            ->where('consumption_date', '>=', $time_begin)
            ->where('consumption_date', '<=', $time_end)
            ->where(function ($query2) use (
                $status
            ) {
                if (!empty($status)) {
                    $query2->where('status', $status);

                }
            })
            ->field('staff_id as statistic_id,staff_id,username,department,dinner_id,dinner,sum(order_count) as order_count,sum(order_money) as order_money')
            ->group('staff_id,dinner')
            ->select()->toArray();
    }

    public static function userStatistic($canteen_id, $status, $department_id,
                                         $username, $staff_type_id, $time_begin,
                                         $time_end, $company_id, $phone, $order_type, $page, $size)
    {
        return self::where(function ($query) use ($company_id, $canteen_id) {
            if (!empty($canteen_id)) {
                $query->where('canteen_id', $canteen_id);
            } else {
                if (strpos($company_id, ',') !== false) {
                    $query->whereIn('company_id', $company_id);
                } else {
                    $query->where('company_id', $company_id);
                }
            }
        })->where(function ($query) use ($order_type) {
            if ($order_type !== 'all') {
                $query->where('location', $order_type);
            }
        })
            ->where(function ($query) use (
                $department_id,
                $username, $staff_type_id, $phone
            ) {
                if (!empty($department_id)) {
                    $query->where('department_id', $department_id);
                }
                if (!empty($phone)) {
                    $query->where('phone', $phone);
                }
                if (!empty($username)) {
                    $query->where('username', 'like', '%' . $username . '%');
                }
                if (!empty($staff_type_id)) {
                    $query->where('staff_type_id', $staff_type_id);
                }

            })
            ->where('consumption_date', '>=', $time_begin)
            ->where('consumption_date', '<=', $time_end)
            ->where(function ($query2) use (
                $status
            ) {
                if (!empty($status)) {
                    $query2->where('status', $status);

                }
            })
            ->field('staff_id as statistic_id,staff_id,username, username as statistic,department')
            ->group('staff_id')
            ->paginate($size, false, ['page' => $page])->toArray();

    }


    /*  public static function userDinnerStatistic($staff_id, $status,
                                                 $time_begin, $time_end)
      {
          return self::where('staff_id', $staff_id)
              ->where('consumption_date', '>=', $time_begin)
              ->where('consumption_date', '<=', $time_end)
              ->where(function ($query2) use (
                  $status
              ) {
                  if (!empty($status)) {
                      $query2->where('status', $status);
                  }
              })
              ->field('staff_id,dinner_id,dinner,sum(order_count) as order_count,sum(order_money) as order_money')
              ->group('dinner')->select();
      }*/
    public function getOrderConsumption($c_id, $consumption_date)
    {
        $dateArr = explode('-',$consumption_date);
        $list = self::where('company_id', $c_id)
            ->where('year(consumption_date) ='. $dateArr[0])
            ->where('month(consumption_date) ='.$dateArr[1])
            ->select()
            ->toArray();
        return $list;
    }

}