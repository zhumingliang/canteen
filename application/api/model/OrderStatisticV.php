<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use app\lib\enum\PayEnum;
use think\Db;
use think\Model;

class OrderStatisticV extends Model
{
    public function getTypeAttr($value)
    {
        $status = [1 => '堂食', 2 => '外卖'];
        return $status[$value];
    }
    public static function getSql($time_begin, $time_end, $company_ids, $canteen_id, $dinner_id)
    {
        $sql = Db::table('canteen_order_t')
            ->field("`a`.`id` AS `order_id`,`a`.`count` AS `count`,`a`.`d_id` AS `dinner_id`,`b`.`name` AS `dinner`,`a`.`c_id` AS `canteen_id`,`c`.`name` AS `canteen`,`a`.`company_id` AS `company_id`,`d`.`name` AS `company`,`a`.`ordering_date` AS `ordering_date`,`a`.`u_id` AS `u_id`,`a`.`department_id` AS `department_id`,`e`.`name` AS `department`,`f`.`username` AS `username`,`f`.`phone` AS `phone`,`a`.`type` AS `type`,`a`.`ordering_type` AS `ordering_type`,`a`.`state` AS `state`,`b`.`meal_time_begin` AS `meal_time_begin`,`b`.`meal_time_end` AS `meal_time_end`,'one' AS `consumption_type`,((`a`.`money`+`a`.`sub_money`)+`a`.`delivery_fee`) AS `order_money`,`a`.`used` AS `used`,`a`.`fixed` AS `fixed`,`a`.`delivery_fee` AS `delivery_fee`,`a`.`booking` AS `booking`")
            ->alias('a')
            ->leftJoin('canteen_dinner_t b', "`a`.`d_id` = `b`.`id`")
            ->leftJoin('canteen_canteen_t c', " `a`.`c_id` = `c`.`id`")
            ->leftJoin("canteen_company_t d", "`a`.`company_id` = `d`.`id`")
            ->leftJoin('canteen_company_department_t e', "`a`.`department_id` = `e`.`id`")
            ->leftJoin('canteen_company_staff_t f', "`a`.`staff_id` = `f`.`id`")
            ->where('a.ordering_date', ">=", $time_begin)
            ->where('a.ordering_date', "<=", $time_end)
            ->where(function ($query) use ($company_ids, $canteen_id, $dinner_id) {
                if (!empty($dinner_id)) {
                    $query->where('a.d_id', $dinner_id);
                } else {
                    if (!empty($canteen_id)) {
                        $query->where('a.c_id', $canteen_id);
                    } else {
                        if (strpos($company_ids, ',') !== false) {
                            $query->whereIn('a.company_id', $company_ids);
                        } else {
                            $query->where('a.company_id', $company_ids);
                        }
                    }
                }
            })
            ->where('a.pay', PayEnum::PAY_SUCCESS)
            ->unionAll(function ($query) use ($time_begin, $time_end, $company_ids, $canteen_id, $dinner_id) {
                $query->table('canteen_order_parent_t')->alias('b')
                    ->field("`b`.`id` AS `order_id`,`b`.`count` AS `count`,`b`.`dinner_id` AS `dinner_id`,`c`.`name` AS `dinner`,`b`.`canteen_id` AS `canteen_id`,`d`.`name` AS `canteen`,`b`.`company_id` AS `company_id`,`e`.`name` AS `company`,`b`.`ordering_date` AS `ordering_date`,`b`.`u_id` AS `u_id`,`b`.`department_id` AS `department_id`,`f`.`name` AS `department`,`g`.`username` AS `username`,`g`.`phone` AS `phone`,`b`.`type` AS `type`,`b`.`ordering_type` AS `ordering_type`,`b`.`state` AS `state`,`c`.`meal_time_begin` AS `meal_time_begin`,`c`.`meal_time_end` AS `meal_time_end`,'more' AS `consumption_type`,((`b`.`money`+`b`.`sub_money`)+`b`.`delivery_fee`) AS `order_money`,`b`.`used` AS `used`,`b`.`fixed` AS `fixed`,`b`.`delivery_fee` AS `delivery_fee`,`b`.`booking` AS `booking`")
                    ->leftJoin('canteen_dinner_t c', "`b`.`dinner_id` = `c`.`id`")
                    ->leftJoin('canteen_canteen_t d', " `b`.`canteen_id` = `d`.`id`")
                    ->leftJoin("canteen_company_t e", "`b`.`company_id` = `e`.`id`")
                    ->leftJoin('canteen_company_department_t f', "`b`.`department_id` = `f`.`id`")
                    ->leftJoin('canteen_company_staff_t g', "`b`.`staff_id` = `g`.`id`")
                    ->where('b.ordering_date', ">=", $time_begin)
                    ->where('b.ordering_date', "<=", $time_end)
                    ->where(function ($query) use ($company_ids, $canteen_id, $dinner_id) {
                        if (!empty($dinner_id)) {
                            $query->where('b.dinner_id', $dinner_id);
                        } else {
                            if (!empty($canteen_id)) {
                                $query->where('b.canteen_id', $canteen_id);
                            } else {
                                if (strpos($company_ids, ',') !== false) {
                                    $query->whereIn('b.company_id', $company_ids);
                                } else {
                                    $query->where('b.company_id', $company_ids);
                                }
                            }
                        }
                    })
                    ->where('b.pay', PayEnum::PAY_SUCCESS);
            })->unionAll(function ($query) use ($time_begin, $time_end, $company_ids, $canteen_id, $dinner_id) {
                $query->table('canteen_company_staff_t')
                    ->field("`c`.`id` AS `order_id`,`c`.`count` AS `count`,`c`.`dinner_id` AS `dinner_id`,concat(`e`.`name`,'(接待票)') AS `dinner`,`c`.`canteen_id` AS `canteen_id`,`d`.`name` AS `canteen`,`a`.`company_id` AS `company_id`,`b`.`name` AS `company`,`c`.`ordering_date` AS `ordering_date`,`c`.`user_id` AS `u_id`,`a`.`d_id` AS `department_id`,`g`.`name` AS `department`,`a`.`username` AS `username`,`a`.`phone` AS `phone`,'1' AS `type`,'online' AS `ordering_type`,'1' AS `state`,`e`.`meal_time_begin` AS `meal_time_begin`,`e`.`meal_time_end` AS `meal_time_end`,'one' AS `consumption_type`,`c`.`money` AS `order_money`,`f`.`status` AS `used`,'1' AS `fixed`,'0' AS `delivery_fee`,'1' AS `booking`")
                    ->alias('a')
                    ->leftJoin('canteen_company_t b',"`a`.`company_id`=`b`.id")
                    ->leftJoin('canteen_reception_t c', "`a`.`id` = `c`.`staff_id`")
                    ->leftJoin('canteen_canteen_t d', " `c`.`canteen_id` = `d`.`id`")
                    ->leftJoin('canteen_dinner_t e', "`c`.`dinner_id` = `e`.`id`")
                    ->leftJoin("canteen_reception_qrcode_t f", "`c`.`id` = `f`.`re_id`")
                    ->leftJoin("canteen_company_department_t g", "`a`.`d_id` = `g`.`id`")
                    ->where('c.ordering_date', ">=", $time_begin)
                    ->where('c.ordering_date', "<=", $time_end)
                    ->where('c.status',2)
                    ->where(function ($query) use ($company_ids, $canteen_id, $dinner_id) {
                        if (!empty($dinner_id)) {
                            $query->where('c.dinner_id', $dinner_id);
                        } else {
                            if (!empty($canteen_id)) {
                                $query->where('c.canteen_id', $canteen_id);
                            } else {
                                if (strpos($company_ids, ',') !== false) {
                                    $query->whereIn('b.id', $company_ids);
                                } else {
                                    $query->where('b.id', $company_ids);
                                }
                            }
                        }
                    });
            })->buildSql();
        return $sql;

    }

    public function getStatusAttr($value, $data)
    {
        if ($data['state'] != CommonEnum::STATE_IS_OK) {
            return 2;//已取消
        } else {
            $expiryDate = $data['ordering_date'] . ' ' . $data['meal_time_end'];
            if (time() > strtotime($expiryDate)) {
                return 3;//已结算
            } else {
                if ($data['used'] == CommonEnum::STATE_IS_FAIL) {
                    return 1;//可取消
                } else {
                    return 3;
                }
            }
        }

    }

    public function foods()
    {
        return $this->hasMany('OrderDetailT', 'o_id', 'order_id');
    }

    public static function statistic($time_begin, $time_end, $company_ids, $canteen_id, $page, $size)
    {
        // $time_end = addDay(1, $time_end);
        $sql = self::getSql($time_begin, $time_end, $company_ids, $canteen_id,
            0);
        $list = Db::table($sql . 'a')
            ->where('booking', CommonEnum::STATE_IS_OK)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->field('ordering_date,company,canteen,dinner,sum(count) as count')
            ->order('ordering_date DESC')
            ->group('ordering_date,dinner_id')
            ->paginate($size, false, ['page' => $page]);
        /*        $list = self::where('ordering_date', '>=', $time_begin)
            ->where('ordering_date', '<=', $time_end)
            ->where(function ($query) use ($company_ids, $canteen_id) {
                if (empty($canteen_id)) {
                    if (strpos($company_ids, ',') !== false) {
                        $query->whereIn('company_id', $company_ids);
                    } else {
                        $query->where('company_id', $company_ids);
                    }
                } else {
                    $query->where('canteen_id', $canteen_id);
                }
            })
            ->where('booking', CommonEnum::STATE_IS_OK)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->field('ordering_date,company,canteen,dinner,sum(count) as count')
            ->order('ordering_date DESC')
            ->group('ordering_date,dinner_id')
            ->paginate($size, false, ['page' => $page]);*/
        return $list;

    }

    public static function exportStatistic($time_begin, $time_end, $company_ids, $canteen_id)
    {
        //$time_end = addDay(1, $time_end);
        $list = self::whereBetweenTime('ordering_date', $time_begin, $time_end)
            ->where(function ($query) use ($company_ids, $canteen_id) {
                if (empty($canteen_id)) {
                    if (strpos($company_ids, ',') !== false) {
                        $query->whereIn('company_id', $company_ids);
                    } else {
                        $query->where('company_id', $company_ids);
                    }
                } else {
                    //  $query->where('canteen_id', $canteen_id);
                    if (strpos($company_ids, ',') !== false) {
                        $query->whereIn('canteen_id', $canteen_id);
                    } else {
                        $query->where('canteen_id', $canteen_id);
                    }
                }
            })
            ->where('booking', CommonEnum::STATE_IS_OK)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->field('ordering_date,company,canteen,dinner,sum(count) as count')
            ->order('ordering_date DESC')
            ->group('ordering_date,dinner_id')
            ->select()->toArray();
        return $list;

    }

    public static function detail($company_ids, $time_begin,
                                  $time_end, $page, $size, $name,
                                  $phone, $canteen_id, $department_id,
                                  $dinner_id, $type)
    {
        /*  $list = self::where('ordering_date', ">=", $time_begin)
              ->where('ordering_date', "<=", $time_end)
              ->where(function ($query) use ($name, $phone, $department_id) {
                  if (strlen($name)) {
                      $query->where('username', 'like', '%' . $name . '%');
                  }
                  if (strlen($phone)) {
                      $query->where('phone', 'like', '%' . $phone . '%');
                  }
                  if (!empty($department_id)) {
                      $query->where('department_id', $department_id);
                  }
              })
              ->where(function ($query) use ($company_ids, $canteen_id, $dinner_id) {
                  if (!empty($dinner_id)) {
                      $query->where('dinner_id', $dinner_id);
                  } else {
                      if (!empty($canteen_id)) {
                          $query->where('canteen_id', $canteen_id);
                      } else {
                          if (strpos($company_ids, ',') !== false) {
                              $query->whereIn('company_id', $company_ids);
                          } else {
                              $query->where('company_id', $company_ids);
                          }
                      }
                  }
              })
              ->where(function ($query) use ($type) {
                  if ($type < 3) {
                      $query->where('type', $type);
                  }
              })
              ->where('booking', CommonEnum::STATE_IS_OK)
              ->field('order_id,consumption_type,ordering_date,username,canteen,department,phone,count,dinner,type,ordering_type,order_money,1 as status,state,meal_time_end,used,fixed')
              ->order('ordering_date DESC')
              ->paginate($size, false, ['page' => $page]);*/
        // return $list;

        $sql = self::getSql($time_begin, $time_end, $company_ids, $canteen_id, $dinner_id);
        $list = Db::table($sql . 'a')->where(function ($query) use ($type) {
            if ($type < 3) {
                $query->where('type', $type);
            }
        })
            ->field('order_id,consumption_type,ordering_date,username,canteen,department,phone,count,dinner,type,ordering_type,order_money,1 as status,state,meal_time_end,used,fixed')
            ->where('booking', CommonEnum::STATE_IS_OK)
            ->where(function ($query) use ($name, $phone, $department_id) {
                if (strlen($name)) {
                    $query->where('username', 'like', '%' . $name . '%');
                }
                if (strlen($phone)) {
                    $query->where('phone', 'like', '%' . $phone . '%');
                }
                if (!empty($department_id)) {
                    $query->where('department_id', $department_id);
                }
            })
            ->order('ordering_date DESC')
            ->group('ordering_date,dinner_id')
            ->paginate($size, false, ['page' => $page])->toArray();
        return $list;

    }

    public static function exportDetail($company_ids, $time_begin,
                                        $time_end, $name,
                                        $phone, $canteen_id, $department_id,
                                        $dinner_id, $type)
    {

        //$time_end = addDay(1, $time_end);
        /*        $list = self::where('ordering_date', ">=", $time_begin)
                    ->where('ordering_date', "<=", $time_end)
                    ->where(function ($query) use ($name, $phone, $department_id) {
                        if (strlen($name)) {
                            $query->where('username', 'like', '%' . $name . '%');
                        }
                        if (strlen($phone)) {
                            $query->where('phone', 'like', '%' . $phone . '%');
                        }
                        if (!empty($department_id)) {
                            $query->where('department_id', $department_id);
                        }
                    })
                    ->where(function ($query) use ($company_ids, $canteen_id, $dinner_id) {
                        if (!empty($dinner_id)) {
                            $query->where('dinner_id', $dinner_id);
                        } else {
                            if (!empty($canteen_id)) {
                                $query->where('canteen_id', $canteen_id);
                            } else {
                                if (strpos($company_ids, ',') !== false) {
                                    $query->whereIn('company_id', $company_ids);
                                } else {
                                    $query->where('company_id', $company_ids);
                                }
                            }
                        }
                    })
                    ->where(function ($query) use ($type) {
                        if ($type < 3) {
                            $query->where('type', $type);
                        }
                    })
                    ->where('booking', CommonEnum::STATE_IS_OK)
                    ->field('order_id,ordering_date,username,canteen,department,dinner,type,ordering_type,state,meal_time_end,used,phone,count,order_money,consumption_type,delivery_fee')
                    ->order('ordering_date DESC')
                    ->select()->toArray();*/


        $sql = self::getSql($time_begin, $time_end, $company_ids, $canteen_id, $dinner_id);
        $list = Db::table($sql . 'a')
            ->where(function ($query) use ($type) {
                if ($type < 3) {
                    $query->where('type', $type);
                }
            })
            ->field('order_id,ordering_date,username,canteen,department,dinner,type,ordering_type,state,meal_time_end,used,phone,count,order_money,consumption_type,delivery_fee')
            ->where('booking', CommonEnum::STATE_IS_OK)
            ->where(function ($query) use ($name, $phone, $department_id) {
                if (strlen($name)) {
                    $query->where('username', 'like', '%' . $name . '%');
                }
                if (strlen($phone)) {
                    $query->where('phone', 'like', '%' . $phone . '%');
                }
                if (!empty($department_id)) {
                    $query->where('department_id', $department_id);
                }
            })
            ->order('ordering_date DESC')
            ->select()->toArray();
        return $list;

    }
}