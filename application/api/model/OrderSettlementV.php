<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use app\lib\enum\OrderEnum;
use app\lib\enum\PayEnum;
use think\Db;
use think\Model;

class OrderSettlementV extends Model
{

    public static function getBuildSqlWithAccount($time_begin, $time_end, $company_ids, $canteen_id, $dinner_id)
    {
        $end = addDay(1, $time_end);
        $sql = Db::field("`a`.`id` AS `order_id`,`a`.`d_id` AS `dinner_id`,`b`.`name` AS `dinner`,`a`.`c_id` AS `canteen_id`,`c`.`name` AS `canteen`,`a`.`company_id` AS `company_id`,`a`.`ordering_date` AS `ordering_date`,`a`.`u_id` AS `u_id`,`a`.`department_id` AS `department_id`,`e`.`name` AS `department`,`f`.`username` AS `username`,`a`.`phone` AS `phone`,`a`.`booking` AS `booking`,`a`.`used` AS `used`,`a`.`used_time` AS `used_time`,'canteen' AS `type`,((`a`.`money`+`a`.`sub_money`)+`a`.`delivery_fee`) AS `money`,'' AS `remark`,`a`.`type` AS `consumption_type`, GROUP_CONCAT(h.name) as account,f.state as staff_state")
            //$sql = Db::field("`a`.`id` AS `order_id`,`a`.`d_id` AS `dinner_id`,`b`.`name` AS `dinner`,`a`.`c_id` AS `canteen_id`,`c`.`name` AS `canteen`,`a`.`company_id` AS `company_id`,`a`.`ordering_date` AS `ordering_date`,`a`.`u_id` AS `u_id`,`a`.`department_id` AS `department_id`,`e`.`name` AS `department`,`f`.`username` AS `username`,`a`.`phone` AS `phone`,`a`.`booking` AS `booking`,`a`.`used` AS `used`,`a`.`used_time` AS `used_time`,'canteen' AS `type`,((`a`.`money`+`a`.`sub_money`)+`a`.`delivery_fee`) AS `money`,'' AS `remark`,`a`.`type` AS `consumption_type`")
            ->table('canteen_order_t')->alias('a')
            ->leftJoin('canteen_dinner_t b', 'a.d_id=b.id')
            ->leftJoin('canteen_canteen_t c', 'a.c_id=c.id')
            ->leftJoin('canteen_company_department_t e', 'a.department_id=e.id')
            ->leftJoin('canteen_company_staff_t f', 'a.staff_id=f.id')
            ->leftJoin('canteen_account_records_t g', 'a.id=g.order_id and g.type= "one"')
            ->leftJoin('canteen_company_account_t h', 'g.account_id=h.id')
            ->where('a.ordering_date', '>=', $time_begin)
            ->where('a.ordering_date', '<=', $time_end)
            ->where('a.state', CommonEnum::STATE_IS_OK)
            ->where('a.pay', PayEnum::PAY_SUCCESS)
            ->where(function ($query) {
                $query->where('a.used', CommonEnum::STATE_IS_OK)->whereOr('a.unused_handel', CommonEnum::STATE_IS_OK);
            })
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
            ->group('a.id')
            ->unionAll(function ($query) use ($time_begin, $time_end, $company_ids, $canteen_id, $dinner_id) {
                $query->field("`a`.`id` AS `order_id`,`a`.`dinner_id` AS `dinner_id`,`b`.`name` AS `dinner`,`a`.`canteen_id` AS `canteen_id`,`c`.`name` AS `canteen`,`a`.`company_id` AS `company_id`,`a`.`consumption_date` AS `ordering_date`,'' AS `u_id`,`e`.`d_id` AS `department_id`,`f`.`name` AS `department`,`e`.`username` AS `username`,`e`.`phone` AS `phone`,0 AS `booking`,0 AS `used`,`a`.`create_time` AS `used_time`,IF ((`a`.`type`=1),'recharge','deduction') AS `type`,IF ((`a`.`type`=1),`a`.`money`,(0-`a`.`money`)) AS `money`,`a`.`remark` AS `remark`,1 AS `consumption_type`,g.name as account,e.state as staff_state")
                    // $query->field("`a`.`id` AS `order_id`,`a`.`dinner_id` AS `dinner_id`,`b`.`name` AS `dinner`,`a`.`canteen_id` AS `canteen_id`,`c`.`name` AS `canteen`,`a`.`company_id` AS `company_id`,`a`.`consumption_date` AS `ordering_date`,'' AS `u_id`,`e`.`d_id` AS `department_id`,`f`.`name` AS `department`,`e`.`username` AS `username`,`e`.`phone` AS `phone`,0 AS `booking`,0 AS `used`,`a`.`create_time` AS `used_time`,IF ((`a`.`type`=1),'recharge','deduction') AS `type`,IF ((`a`.`type`=1),`a`.`money`,(0-`a`.`money`)) AS `money`,`a`.`remark` AS `remark`,1 AS `consumption_type`")
                    ->table('canteen_recharge_supplement_t')->alias('a')
                    ->leftJoin('canteen_dinner_t b', "`a`.`dinner_id` = `b`.`id`")
                    ->leftJoin('canteen_canteen_t c', '`a`.`canteen_id` = `c`.`id`')
                    ->leftJoin('canteen_company_staff_t e', "`a`.`staff_id` = `e`.`id`")
                    ->leftJoin('canteen_company_department_t f', "`e`.`d_id` = `f`.`id`")
                    ->leftJoin('canteen_company_account_t g', '`a`.`account_id` = `g`.`id`')
                    ->where('a.consumption_date', '>=', $time_begin)
                    ->where('a.consumption_date', '<=', $time_end)
                    ->where(function ($query) use ($company_ids, $canteen_id, $dinner_id) {
                        if (!empty($dinner_id)) {
                            $query->where('a.dinner_id', $dinner_id);
                        } else {
                            if (!empty($canteen_id)) {
                                $query->where('a.canteen_id', $canteen_id);
                            } else {
                                if (strpos($company_ids, ',') !== false) {
                                    $query->whereIn('a.company_id', $company_ids);
                                } else {
                                    $query->where('a.company_id', $company_ids);
                                }
                            }
                        }

                    });


            })->unionAll(function ($query) use ($time_begin, $time_end, $company_ids, $canteen_id, $dinner_id) {
                $query->field("`g`.`id` AS `order_id`,`a`.`dinner_id` AS `dinner_id`,`b`.`name` AS `dinner`,`a`.`canteen_id` AS `canteen_id`,`c`.`name` AS `canteen`,`a`.`company_id` AS `company_id`,`a`.`ordering_date` AS `ordering_date`,`a`.`u_id` AS `u_id`,`a`.`department_id` AS `department_id`,`e`.`name` AS `department`,`f`.`username` AS `username`,`a`.`phone` AS `phone`,`a`.`booking` AS `booking`,`g`.`used` AS `used`,`g`.`used_time` AS `used_time`,'canteen' AS `type`,(`g`.`money`+`g`.`sub_money`) AS `money`,'' AS `remark`,`a`.`type` AS `consumption_type`, GROUP_CONCAT(i.name) as account,f.state as staff_state")
                    // $query->field("`g`.`id` AS `order_id`,`a`.`dinner_id` AS `dinner_id`,`b`.`name` AS `dinner`,`a`.`canteen_id` AS `canteen_id`,`c`.`name` AS `canteen`,`a`.`company_id` AS `company_id`,`a`.`ordering_date` AS `ordering_date`,`a`.`u_id` AS `u_id`,`a`.`department_id` AS `department_id`,`e`.`name` AS `department`,`f`.`username` AS `username`,`a`.`phone` AS `phone`,`a`.`booking` AS `booking`,`g`.`used` AS `used`,`g`.`used_time` AS `used_time`,'canteen' AS `type`,(`g`.`money`+`g`.`sub_money`) AS `money`,'' AS `remark`,`a`.`type` AS `consumption_type`")
                    ->table('canteen_order_sub_t')->alias('g')
                    ->leftJoin('canteen_order_parent_t a', "`g`.`order_id` = `a`.`id`")
                    ->leftJoin('canteen_dinner_t b', '`a`.`dinner_id` = `b`.`id`')
                    ->leftJoin('canteen_canteen_t c', '`a`.`canteen_id` = `c`.`id` ')
                    ->leftJoin('canteen_company_department_t e', '`a`.`department_id` = `e`.`id`')
                    ->leftJoin('canteen_company_staff_t f', '`a`.`staff_id` = `f`.`id` ')
                    ->leftJoin('canteen_account_records_t h', 'g.id=h.order_id and h.type= "more" and h.outsider = 2')
                    ->leftJoin('canteen_company_account_t i', 'h.account_id=i.id')
                    ->where('a.ordering_date', '>=', $time_begin)
                    ->where('a.ordering_date', '<=', $time_end)
                    ->where(function ($query) use ($company_ids, $canteen_id, $dinner_id) {
                        if (!empty($dinner_id)) {
                            $query->where('a.dinner_id', $dinner_id);
                        } else {
                            if (!empty($canteen_id)) {
                                $query->where('a.canteen_id', $canteen_id);
                            } else {
                                if (strpos($company_ids, ',') !== false) {
                                    $query->whereIn('a.company_id', $company_ids);
                                } else {
                                    $query->where('a.company_id', $company_ids);
                                }
                            }
                        }

                    })
                    ->where('a.type', OrderEnum::EAT_CANTEEN)
                    ->where('g.state', CommonEnum::STATE_IS_OK)
                    ->where('a.pay', PayEnum::PAY_SUCCESS)
                    ->where(function ($query) {
                        $query->where('g.used', CommonEnum::STATE_IS_OK)->whereOr('g.unused_handel', CommonEnum::STATE_IS_OK);
                    })
                    ->group('g.id');
            })->unionAll(function ($query) use ($time_begin, $time_end, $company_ids, $canteen_id, $dinner_id) {
                $query->field("`a`.`id` AS `order_id`,`a`.`dinner_id` AS `dinner_id`,`b`.`name` AS `dinner`,`a`.`canteen_id` AS `canteen_id`,`c`.`name` AS `canteen`,`a`.`company_id` AS `company_id`,`a`.`ordering_date` AS `ordering_date`,`a`.`u_id` AS `u_id`,`a`.`department_id` AS `department_id`,`e`.`name` AS `department`,`f`.`username` AS `username`,`a`.`phone` AS `phone`,`a`.`booking` AS `booking`,`a`.`used` AS `used`,`a`.`used_time` AS `used_time`,'canteen' AS `type`,((`a`.`money`+`a`.`sub_money`)+`a`.`delivery_fee`) AS `money`,'' AS `remark`,`a`.`type` AS `consumption_type`,GROUP_CONCAT(i.name) as account,f.state as staff_state")
                    // $query->field("`a`.`id` AS `order_id`,`a`.`dinner_id` AS `dinner_id`,`b`.`name` AS `dinner`,`a`.`canteen_id` AS `canteen_id`,`c`.`name` AS `canteen`,`a`.`company_id` AS `company_id`,`a`.`ordering_date` AS `ordering_date`,`a`.`u_id` AS `u_id`,`a`.`department_id` AS `department_id`,`e`.`name` AS `department`,`f`.`username` AS `username`,`a`.`phone` AS `phone`,`a`.`booking` AS `booking`,`a`.`used` AS `used`,`a`.`used_time` AS `used_time`,'canteen' AS `type`,((`a`.`money`+`a`.`sub_money`)+`a`.`delivery_fee`) AS `money`,'' AS `remark`,`a`.`type` AS `consumption_type`")
                    ->table('canteen_order_parent_t')->alias('a')
                    ->leftJoin('canteen_dinner_t b', "`a`.`dinner_id` = `b`.`id`")
                    ->leftJoin('canteen_canteen_t c', '`a`.`canteen_id` = `c`.`id` ')
                    ->leftJoin('canteen_company_department_t e', '`a`.`department_id` = `e`.`id`')
                    ->leftJoin('canteen_company_staff_t f', '`a`.`staff_id` = `f`.`id`')
                    ->leftJoin('canteen_account_records_t h', 'a.id=h.order_id and h.type= "more" and h.outsider = 1')
                    ->leftJoin('canteen_company_account_t i', 'h.account_id=i.id')
                    ->where('a.ordering_date', '>=', $time_begin)
                    ->where('a.ordering_date', '<=', $time_end)
                    ->where('a.type', OrderEnum::EAT_OUTSIDER)
                    ->where('a.state', CommonEnum::STATE_IS_OK)
                    ->where('a.pay', PayEnum::PAY_SUCCESS)
                    ->where(function ($query) use ($company_ids, $canteen_id, $dinner_id) {
                        if (!empty($dinner_id)) {
                            $query->where('a.dinner_id', $dinner_id);
                        } else {
                            if (!empty($canteen_id)) {
                                $query->where('a.canteen_id', $canteen_id);
                            } else {
                                if (strpos($company_ids, ',') !== false) {
                                    $query->whereIn('a.company_id', $company_ids);
                                } else {
                                    $query->where('a.company_id', $company_ids);
                                }
                            }
                        }

                    })
                    ->where(function ($query) {
                        $query->where('a.all_used', CommonEnum::STATE_IS_OK);
                    })
                    ->group('a.id');

            })->unionAll(function ($query) use ($time_begin, $end, $company_ids, $canteen_id, $dinner_id) {
                //$query->field("`a`.`id` AS `order_id`,0 AS `dinner_id`,'小卖部' AS `dinner`,`d`.`id` AS `canteen_id`,`d`.`name` AS `canteen`,`a`.`company_id` AS `company_id`,date_format(`a`.`create_time`,'%Y-%m%-%d') AS `ordering_date`,`a`.`u_id` AS `u_id`,`a`.`department_id` AS `department_id`,`c`.`name` AS `department`,`b`.`username` AS `username`,`a`.`phone` AS `phone`,1 AS `booking`,`a`.`used` AS `used`,`a`.`create_time` AS `used_time`,'shop' AS `type`,`a`.`money` AS `money`,'' AS `remark`,1 AS `consumption_type`")
                $query->field("`a`.`id` AS `order_id`,0 AS `dinner_id`,'小卖部' AS `dinner`,`d`.`id` AS `canteen_id`,`d`.`name` AS `canteen`,`a`.`company_id` AS `company_id`,date_format(`a`.`create_time`,'%Y-%m%-%d') AS `ordering_date`,`a`.`u_id` AS `u_id`,`a`.`department_id` AS `department_id`,`c`.`name` AS `department`,`b`.`username` AS `username`,`a`.`phone` AS `phone`,1 AS `booking`,`a`.`used` AS `used`,`a`.`create_time` AS `used_time`,'shop' AS `type`,`a`.`money` AS `money`,'' AS `remark`,1 AS `consumption_type`,GROUP_CONCAT(i.name) as account,b.state as staff_state")
                    ->table('canteen_shop_order_t')->alias('a')
                    ->leftJoin('canteen_company_staff_t b', "`a`.`staff_id` = `b`.`id`")
                    ->leftJoin('canteen_company_department_t c', '`b`.`d_id` = `c`.`id`')
                    ->leftJoin('canteen_shop_t d', '`a`.`shop_id` = `d`.`id`')
                    ->leftJoin('canteen_account_records_t h', 'a.id=h.order_id and h.type= "shop"')
                    ->leftJoin('canteen_company_account_t i', 'h.account_id=i.id')
                    ->where('a.create_time', '>=', $time_begin)
                    ->where('a.create_time', '<=', $end)
                    ->where(function ($query) use ($company_ids, $canteen_id, $dinner_id) {
                        if (!empty($dinner_id)) {
                            $query->where('a.dinner_id', $dinner_id);
                        } else {
                            if (!empty($canteen_id)) {
                                $query->where('a.shop_id', $canteen_id);
                            } else {
                                if (strpos($company_ids, ',') !== false) {
                                    $query->whereIn('a.company_id', $company_ids);
                                } else {
                                    $query->where('a.company_id', $company_ids);
                                }
                            }
                        }

                    })
                    ->where('a.state', CommonEnum::STATE_IS_OK)->group('a.id');

            })->buildSql();
        return $sql;
    }

    public static function getBuildSql($time_begin, $time_end, $company_ids, $canteen_id, $dinner_id)
    {
        $end=addDay(1,$time_end);
        $sql = Db::field("`a`.`id` AS `order_id`,`a`.`d_id` AS `dinner_id`,`b`.`name` AS `dinner`,`a`.`c_id` AS `canteen_id`,`c`.`name` AS `canteen`,`a`.`company_id` AS `company_id`,`a`.`ordering_date` AS `ordering_date`,`a`.`u_id` AS `u_id`,`a`.`department_id` AS `department_id`,`e`.`name` AS `department`,`f`.`username` AS `username`,`a`.`phone` AS `phone`,`a`.`booking` AS `booking`,`a`.`used` AS `used`,`a`.`used_time` AS `used_time`,'canteen' AS `type`,((`a`.`money`+`a`.`sub_money`)+`a`.`delivery_fee`) AS `money`,'' AS `remark`,`a`.`type` AS `consumption_type`,f.state as staff_state")
            ->table('canteen_order_t')->alias('a')
            ->leftJoin('canteen_dinner_t b', 'a.d_id=b.id')
            ->leftJoin('canteen_canteen_t c', 'a.c_id=c.id')
            ->leftJoin('canteen_company_department_t e', 'a.department_id=e.id')
            ->leftJoin('canteen_company_staff_t f', 'a.staff_id=f.id')
            ->where('a.ordering_date', '>=', $time_begin)
            ->where('a.ordering_date', '<=', $time_end)
            ->where('a.state', CommonEnum::STATE_IS_OK)
            ->where('a.pay', PayEnum::PAY_SUCCESS)
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
            ->unionAll(function ($query) use ($time_begin, $time_end, $company_ids, $canteen_id, $dinner_id) {
                $query->field("`a`.`id` AS `order_id`,`a`.`dinner_id` AS `dinner_id`,`b`.`name` AS `dinner`,`a`.`canteen_id` AS `canteen_id`,`c`.`name` AS `canteen`,`a`.`company_id` AS `company_id`,`a`.`consumption_date` AS `ordering_date`,'' AS `u_id`,`e`.`d_id` AS `department_id`,`f`.`name` AS `department`,`e`.`username` AS `username`,`e`.`phone` AS `phone`,0 AS `booking`,0 AS `used`,`a`.`create_time` AS `used_time`,IF ((`a`.`type`=1),'recharge','deduction') AS `type`,IF ((`a`.`type`=1),`a`.`money`,(0-`a`.`money`)) AS `money`,`a`.`remark` AS `remark`,1 AS `consumption_type`,e.state as staff_state")
                    // $query->field("`a`.`id` AS `order_id`,`a`.`dinner_id` AS `dinner_id`,`b`.`name` AS `dinner`,`a`.`canteen_id` AS `canteen_id`,`c`.`name` AS `canteen`,`a`.`company_id` AS `company_id`,`a`.`consumption_date` AS `ordering_date`,'' AS `u_id`,`e`.`d_id` AS `department_id`,`f`.`name` AS `department`,`e`.`username` AS `username`,`e`.`phone` AS `phone`,0 AS `booking`,0 AS `used`,`a`.`create_time` AS `used_time`,IF ((`a`.`type`=1),'recharge','deduction') AS `type`,IF ((`a`.`type`=1),`a`.`money`,(0-`a`.`money`)) AS `money`,`a`.`remark` AS `remark`,1 AS `consumption_type`")
                    ->table('canteen_recharge_supplement_t')->alias('a')
                    ->leftJoin('canteen_dinner_t b', "`a`.`dinner_id` = `b`.`id`")
                    ->leftJoin('canteen_canteen_t c', '`a`.`canteen_id` = `c`.`id`')
                    ->leftJoin('canteen_company_staff_t e', "`a`.`staff_id` = `e`.`id`")
                    ->leftJoin('canteen_company_department_t f', "`e`.`d_id` = `f`.`id`")
                    ->where('a.consumption_date', '>=', $time_begin)
                    ->where('a.consumption_date', '<=', $time_end)
                    ->where(function ($query) use ($company_ids, $canteen_id, $dinner_id) {
                        if (!empty($dinner_id)) {
                            $query->where('a.dinner_id', $dinner_id);
                        } else {
                            if (!empty($canteen_id)) {
                                $query->where('a.canteen_id', $canteen_id);
                            } else {
                                if (strpos($company_ids, ',') !== false) {
                                    $query->whereIn('a.company_id', $company_ids);
                                } else {
                                    $query->where('a.company_id', $company_ids);
                                }
                            }
                        }

                    });


            })->unionAll(function ($query) use ($time_begin, $time_end, $company_ids, $canteen_id, $dinner_id) {
                $query->field("`g`.`id` AS `order_id`,`a`.`dinner_id` AS `dinner_id`,`b`.`name` AS `dinner`,`a`.`canteen_id` AS `canteen_id`,`c`.`name` AS `canteen`,`a`.`company_id` AS `company_id`,`a`.`ordering_date` AS `ordering_date`,`a`.`u_id` AS `u_id`,`a`.`department_id` AS `department_id`,`e`.`name` AS `department`,`f`.`username` AS `username`,`a`.`phone` AS `phone`,`a`.`booking` AS `booking`,`g`.`used` AS `used`,`g`.`used_time` AS `used_time`,'canteen' AS `type`,(`g`.`money`+`g`.`sub_money`) AS `money`,'' AS `remark`,`a`.`type` AS `consumption_type`,f.state as staff_state")
                    ->table('canteen_order_sub_t')->alias('g')
                    ->leftJoin('canteen_order_parent_t a', "`g`.`order_id` = `a`.`id`")
                    ->leftJoin('canteen_dinner_t b', '`a`.`dinner_id` = `b`.`id`')
                    ->leftJoin('canteen_canteen_t c', '`a`.`canteen_id` = `c`.`id` ')
                    ->leftJoin('canteen_company_department_t e', '`a`.`department_id` = `e`.`id`')
                    ->leftJoin('canteen_company_staff_t f', '`a`.`staff_id` = `f`.`id` ')
                    ->where('a.ordering_date', '>=', $time_begin)
                    ->where('a.ordering_date', '<=', $time_end)
                    ->where(function ($query) use ($company_ids, $canteen_id, $dinner_id) {
                        if (!empty($dinner_id)) {
                            $query->where('a.dinner_id', $dinner_id);
                        } else {
                            if (!empty($canteen_id)) {
                                $query->where('a.canteen_id', $canteen_id);
                            } else {
                                if (strpos($company_ids, ',') !== false) {
                                    $query->whereIn('a.company_id', $company_ids);
                                } else {
                                    $query->where('a.company_id', $company_ids);
                                }
                            }
                        }

                    })
                    ->where('a.type', OrderEnum::EAT_CANTEEN)
                    ->where('g.state', CommonEnum::STATE_IS_OK)
                    ->where('a.pay', PayEnum::PAY_SUCCESS);
            })->unionAll(function ($query) use ($time_begin, $time_end, $company_ids, $canteen_id, $dinner_id) {
                $query->field("`a`.`id` AS `order_id`,`a`.`dinner_id` AS `dinner_id`,`b`.`name` AS `dinner`,`a`.`canteen_id` AS `canteen_id`,`c`.`name` AS `canteen`,`a`.`company_id` AS `company_id`,`a`.`ordering_date` AS `ordering_date`,`a`.`u_id` AS `u_id`,`a`.`department_id` AS `department_id`,`e`.`name` AS `department`,`f`.`username` AS `username`,`a`.`phone` AS `phone`,`a`.`booking` AS `booking`,`a`.`used` AS `used`,`a`.`used_time` AS `used_time`,'canteen' AS `type`,((`a`.`money`+`a`.`sub_money`)+`a`.`delivery_fee`) AS `money`,'' AS `remark`,`a`.`type` AS `consumption_type`,f.state as staff_state")
                    ->table('canteen_order_parent_t')->alias('a')
                    ->leftJoin('canteen_dinner_t b', "`a`.`dinner_id` = `b`.`id`")
                    ->leftJoin('canteen_canteen_t c', '`a`.`canteen_id` = `c`.`id` ')
                    ->leftJoin('canteen_company_department_t e', '`a`.`department_id` = `e`.`id`')
                    ->leftJoin('canteen_company_staff_t f', '`a`.`staff_id` = `f`.`id`')
                    ->where('a.ordering_date', '>=', $time_begin)
                    ->where('a.ordering_date', '<=', $time_end)
                    ->where('a.type', OrderEnum::EAT_OUTSIDER)
                    ->where('a.state', CommonEnum::STATE_IS_OK)
                    ->where('a.pay', PayEnum::PAY_SUCCESS)
                    ->where(function ($query) use ($company_ids, $canteen_id, $dinner_id) {
                        if (!empty($dinner_id)) {
                            $query->where('a.dinner_id', $dinner_id);
                        } else {
                            if (!empty($canteen_id)) {
                                $query->where('a.canteen_id', $canteen_id);
                            } else {
                                if (strpos($company_ids, ',') !== false) {
                                    $query->whereIn('a.company_id', $company_ids);
                                } else {
                                    $query->where('a.company_id', $company_ids);
                                }
                            }
                        }

                    });

            })->unionAll(function ($query) use ($time_begin, $end, $company_ids, $canteen_id, $dinner_id) {
                $query->field("`a`.`id` AS `order_id`,0 AS `dinner_id`,'小卖部' AS `dinner`,`d`.`id` AS `canteen_id`,`d`.`name` AS `canteen`,`a`.`company_id` AS `company_id`,date_format(`a`.`create_time`,'%Y-%m%-%d') AS `ordering_date`,`a`.`u_id` AS `u_id`,`a`.`department_id` AS `department_id`,`c`.`name` AS `department`,`b`.`username` AS `username`,`a`.`phone` AS `phone`,1 AS `booking`,`a`.`used` AS `used`,`a`.`create_time` AS `used_time`,'shop' AS `type`,`a`.`money` AS `money`,'' AS `remark`,1 AS `consumption_type`,b.state as staff_state")
                    ->table('canteen_shop_order_t')->alias('a')
                    ->leftJoin('canteen_company_staff_t b', "`a`.`staff_id` = `b`.`id`")
                    ->leftJoin('canteen_company_department_t c', '`b`.`d_id` = `c`.`id`')
                    ->leftJoin('canteen_shop_t d', '`a`.`shop_id` = `d`.`id`')
                    ->where('a.create_time', '>=', $time_begin)
                    ->where('a.create_time', '<=', $end)
                    ->where(function ($query) use ($company_ids, $canteen_id, $dinner_id) {
                        if (!empty($dinner_id)) {
                            $query->where('a.dinner_id', $dinner_id);
                        } else {
                            if (!empty($canteen_id)) {
                                $query->where('a.shop_id', $canteen_id);
                            } else {
                                if (strpos($company_ids, ',') !== false) {
                                    $query->whereIn('a.company_id', $company_ids);
                                } else {
                                    $query->where('a.company_id', $company_ids);
                                }
                            }
                        }

                    })
                    ->where('a.state', CommonEnum::STATE_IS_OK);

            })->buildSql();
        return $sql;
    }


    public static function orderSettlement($page, $size,
                                           $name, $phone, $canteen_id, $department_id, $dinner_id,
                                           $consumption_type, $time_begin, $time_end, $company_ids, $type)
    {
        $subQuery = self::getBuildSql($time_begin, $time_end, $company_ids, $canteen_id, $dinner_id);
        $list = Db::table($subQuery . ' a')
            ->where('staff_state', CommonEnum::STATE_IS_OK)
            ->where(function ($query) use ($name, $phone, $department_id) {
                if (strlen($name)) {
                    $query->where('username', $name);
                }
                if (strlen($phone)) {
                    $query->where('phone', $phone);
                }
                if (!empty($department_id)) {
                    $query->where('department_id', $department_id);
                }
            })->where(function ($query) use ($type) {
                if ($type == 'shop') {
                    $query->where('type', $type);
                }
            })
            ->where(function ($query) use ($consumption_type) {
                if ($consumption_type) {
                    if ($consumption_type == 1) {
                        //订餐就餐
                        $query->where('booking', CommonEnum::STATE_IS_OK)
                            ->where('used', CommonEnum::STATE_IS_OK);
                    } else if ($consumption_type == 2) {
                        //订餐未就餐
                        $query->where('booking', CommonEnum::STATE_IS_OK)
                            ->where('used', CommonEnum::STATE_IS_FAIL);
                    } else if ($consumption_type == 3) {
                        //未订餐就餐
                        $query->where('booking', CommonEnum::STATE_IS_FAIL)
                            ->where('used', CommonEnum::STATE_IS_OK);
                    } else if ($consumption_type == 4) {
                        //系统补充
                        $query->where('type', 'recharge');
                    } else if ($consumption_type == 5) {
                        //系统补扣
                        $query->where('type', 'deduction');
                    } else if ($consumption_type == 6) {
                        //小卖部消费
                        $query->where('type', 'shop')->where('money', '>', 0);
                    } else if ($consumption_type == 7) {
                        //小卖部退款
                        $query->where('type', 'shop')->where('money', '<', 0);
                    }
                }

            })
            ->field('order_id,used_time,username,phone,canteen,department,dinner,booking,used,type,ordering_date,money,consumption_type')
            ->order('ordering_date DESC,phone')
            ->paginate($size, false, ['page' => $page])->toArray();
        return $list;

    }

    public static function orderSettlementWithAccount($page, $size,
                                                      $name, $phone, $canteen_id, $department_id, $dinner_id,
                                                      $consumption_type, $time_begin, $time_end, $company_ids, $type)
    {
        $subQuery = self::getBuildSqlWithAccount($time_begin, $time_end, $company_ids, $canteen_id, $dinner_id);
        $list = Db::table($subQuery . ' a')
            ->where('staff_state', CommonEnum::STATE_IS_OK)
            ->where(function ($query) use ($name, $phone, $department_id) {
                if (strlen($name)) {
                    $query->where('username', $name);
                }
                if (strlen($phone)) {
                    $query->where('phone', $phone);
                }
                if (!empty($department_id)) {
                    $query->where('department_id', $department_id);
                }
            })->where(function ($query) use ($type) {
                if ($type == 'shop') {
                    $query->where('type', $type);
                }
            })
            ->where(function ($query) use ($consumption_type) {
                if ($consumption_type) {
                    if ($consumption_type == 1) {
                        //订餐就餐
                        $query->where('booking', CommonEnum::STATE_IS_OK)
                            ->where('used', CommonEnum::STATE_IS_OK);
                    } else if ($consumption_type == 2) {
                        //订餐未就餐
                        $query->where('booking', CommonEnum::STATE_IS_OK)
                            ->where('used', CommonEnum::STATE_IS_FAIL);
                    } else if ($consumption_type == 3) {
                        //未订餐就餐
                        $query->where('booking', CommonEnum::STATE_IS_FAIL)
                            ->where('used', CommonEnum::STATE_IS_OK);
                    } else if ($consumption_type == 4) {
                        //系统补充
                        $query->where('type', 'recharge');
                    } else if ($consumption_type == 5) {
                        //系统补扣
                        $query->where('type', 'deduction');
                    } else if ($consumption_type == 6) {
                        //小卖部消费
                        $query->where('type', 'shop')->where('money', '>', 0);
                    } else if ($consumption_type == 7) {
                        //小卖部退款
                        $query->where('type', 'shop')->where('money', '<', 0);
                    }
                }

            })
            ->field('order_id,used_time,username,phone,canteen,department,dinner,booking,used,type,ordering_date,money,consumption_type,account')
            ->order('ordering_date DESC,phone')
        ->paginate($size, false, ['page' => $page])->toArray();
        return $list;

    }


    public static function exportOrderSettlement($name, $phone, $canteen_id, $department_id, $dinner_id,
                                                 $consumption_type, $time_begin, $time_end, $company_ids, $type)
    {
        $subQuery = self::getBuildSql($time_begin, $time_end, $company_ids, $canteen_id, $dinner_id);
        $list = Db::table($subQuery . ' a')
            ->where('staff_state', CommonEnum::STATE_IS_OK)
            ->where(function ($query) use ($name, $phone, $department_id) {
                if (strlen($name)) {
                    $query->where('username', $name);
                }
                if (strlen($phone)) {
                    $query->where('phone', $phone);
                }
                if (!empty($department_id)) {
                    $query->where('department_id', $department_id);
                }
            })
            ->where(function ($query) use ($type) {
                if ($type == 'shop') {
                    $query->where('type', $type);
                }
            })
            ->where(function ($query) use ($consumption_type) {
                if ($consumption_type) {
                    if ($consumption_type == 1) {
                        //订餐就餐
                        $query->where('booking', CommonEnum::STATE_IS_OK)
                            ->where('used', CommonEnum::STATE_IS_OK);
                    } else if ($consumption_type == 2) {
                        //订餐未就餐
                        $query->where('booking', CommonEnum::STATE_IS_OK)
                            ->where('used', CommonEnum::STATE_IS_FAIL);
                    } else if ($consumption_type == 3) {
                        //未订餐就餐
                        $query->where('booking', CommonEnum::STATE_IS_FAIL)
                            ->where('used', CommonEnum::STATE_IS_OK);
                    } else if ($consumption_type == 4) {
                        //系统补充
                        $query->where('type', 'recharge');
                    } else if ($consumption_type == 5) {
                        //系统补扣
                        $query->where('type', 'deduction');
                    } else if ($consumption_type == 6) {
                        //系统补扣
                        $query->where('type', 'shop')->where('money', '>', 0);
                    } else if ($consumption_type == 7) {
                        //系统补扣
                        $query->where('type', 'shop')->where('money', '<', 0);
                    }
                }

            })->field('ordering_date,used_time,department,username,phone,canteen,dinner,booking,used,type,money,remark,consumption_type')
            ->order('ordering_date DESC,phone')
            ->select()->toArray();
        return $list;

    }

    public static function exportOrderSettlementWithAccount($name, $phone, $canteen_id, $department_id, $dinner_id,
                                                            $consumption_type, $time_begin, $time_end, $company_ids, $type)
    {
        $subQuery = self::getBuildSqlWithAccount($time_begin, $time_end, $company_ids, $canteen_id, $dinner_id);
        $list = Db::table($subQuery . ' a')
            ->where('staff_state', CommonEnum::STATE_IS_OK)
            ->where(function ($query) use ($name, $phone, $department_id) {
                if (strlen($name)) {
                    $query->where('username', $name);
                }
                if (strlen($phone)) {
                    $query->where('phone', $phone);
                }
                if (!empty($department_id)) {
                    $query->where('department_id', $department_id);
                }
            })
            ->where(function ($query) use ($type) {
                if ($type == 'shop') {
                    $query->where('type', $type);
                }
            })
            ->where(function ($query) use ($consumption_type) {
                if ($consumption_type) {
                    if ($consumption_type == 1) {
                        //订餐就餐
                        $query->where('booking', CommonEnum::STATE_IS_OK)
                            ->where('used', CommonEnum::STATE_IS_OK);
                    } else if ($consumption_type == 2) {
                        //订餐未就餐
                        $query->where('booking', CommonEnum::STATE_IS_OK)
                            ->where('used', CommonEnum::STATE_IS_FAIL);
                    } else if ($consumption_type == 3) {
                        //未订餐就餐
                        $query->where('booking', CommonEnum::STATE_IS_FAIL)
                            ->where('used', CommonEnum::STATE_IS_OK);
                    } else if ($consumption_type == 4) {
                        //系统补充
                        $query->where('type', 'recharge');
                    } else if ($consumption_type == 5) {
                        //系统补扣
                        $query->where('type', 'deduction');
                    } else if ($consumption_type == 6) {
                        //系统补扣
                        $query->where('type', 'shop')->where('money', '>', 0);
                    } else if ($consumption_type == 7) {
                        //系统补扣
                        $query->where('type', 'shop')->where('money', '<', 0);
                    }
                }

            })->field('ordering_date,used_time,department,username,phone,canteen,account,dinner,booking,used,type,money,remark,consumption_type')
            ->order('ordering_date DESC,phone')
            ->select()->toArray();
        return $list;

    }

}