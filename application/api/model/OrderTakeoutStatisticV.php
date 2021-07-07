<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use app\lib\enum\OrderEnum;
use app\lib\enum\PayEnum;
use think\Db;
use think\Model;
use function GuzzleHttp\Promise\queue;

class OrderTakeoutStatisticV extends Model
{
    public function foods()
    {
        return $this->hasMany('OrderDetailT', 'o_id', 'order_id');
    }

    public function foods2()
    {
        return $this->hasMany('SubFoodT', 'o_id', 'order_id');
    }

    private static function getBuildSql($company_ids, $canteen_id, $dinner_id, $ordering_date, $department_id, $user_type, $username, $status)
    {
        $subQuery = Db::table('canteen_order_t')
            ->alias('a')
            ->field("`a`.`id` AS `order_id`,
	`a`.`ordering_date` AS `ordering_date`,
	`c`.`name` AS `canteen`,
	`f`.`username` AS `username`,
IF
	(
		( `a`.`outsider` = 1 ),
		`d`.`phone`,
		`f`.`phone` 
	) AS `phone`,
	`b`.`name` AS `dinner`,
	(
		( `a`.`money` + `a`.`sub_money` ) + `a`.`delivery_fee` 
	) AS `money`,
	`a`.`delivery_fee` AS `delivery_fee`,
	`d`.`address` AS `address`,
	0 AS `status`,
	`a`.`outsider` AS `outsider`,
	`a`.`used` AS `used`,
	`a`.`create_time` AS `create_time`,
	`a`.`d_id` AS `dinner_id`,
	`a`.`company_id` AS `company_id`,
	`a`.`c_id` AS `canteen_id`,
	`d`.`province` AS `province`,
	`d`.`area` AS `area`,
	`d`.`city` AS `city`,
	`a`.`department_id` AS `department_id`,
	`a`.`receive` AS `receive`,
	`a`.`pay` AS `pay`,
	`a`.`state` AS `state`,
	`a`.`count` AS `count`,
	`d`.`name` AS `address_username`,
	`d`.`phone` AS `address_phone`,
	'one' AS `consumption_type`,
	`a`.`fixed` AS `fixed` ")
            ->leftJoin('canteen_dinner_t b', 'a.d_id = b.id')
            ->leftJoin('canteen_canteen_t c', 'a.c_id = c.id')
            ->leftJoin('canteen_company_staff_t f', 'a.staff_id = f.id')
            ->leftJoin('canteen_user_address_t d', 'a.address_id = d.id')
            ->where('ordering_date', $ordering_date)
            ->where('a.type', OrderEnum::EAT_OUTSIDER)
            ->where('a.pay', PayEnum::PAY_SUCCESS)
            ->where(function ($query) use ($company_ids, $canteen_id, $dinner_id) {
                if (!empty($dinner_id)) {
                    $query->where('a.d_id', $dinner_id);
                } else {
                    if (!empty($canteen_id)) {
                       // $query->where('a.c_id', $canteen_id);
                        if (strpos($canteen_id, ',') !== false) {
                            $query->whereIn('a.c_id', $canteen_id);
                        } else {
                            $query->where('a.c_id', $canteen_id);
                        }
                    } else {
                        if (strpos($company_ids, ',') !== false) {
                            $query->whereIn('a.company_id', $company_ids);
                        } else {
                            $query->where('a.company_id', $company_ids);
                        }
                    }
                }
            })
            ->where(function ($query) use ($department_id) {
                if (!empty($department_id)) {
                    $query->where('a.department_id', $department_id);
                }
            })
            ->where(function ($query) use ($user_type) {
                if (!empty($user_type)) {
                    $query->where('a.outsider', $user_type);
                }
            })
            ->where(function ($query) use ($username) {
                if (!empty($username)) {
                    $query->where('f.username', $username);
                }
            })
            ->where(function ($query) use ($status) {
                if ($status == OrderEnum::STATUS_PAID) {
                    $query->where('a.state', CommonEnum::STATE_IS_OK)
                        ->where('a.pay', PayEnum::PAY_SUCCESS)
                        ->where('a.receive', CommonEnum::STATE_IS_FAIL);
                } elseif ($status == OrderEnum::STATUS_CANCEL) {
                    $query->where('a.state', CommonEnum::STATE_IS_FAIL);
                } elseif ($status == OrderEnum::STATUS_RECEIVE) {
                    $query->where('a.state', CommonEnum::STATE_IS_OK)
                        ->where('a.pay', PayEnum::PAY_SUCCESS)
                        ->where('a.receive', CommonEnum::STATE_IS_OK)
                        ->where('a.used', CommonEnum::STATE_IS_FAIL);
                } elseif ($status == OrderEnum::STATUS_COMPLETE) {
                    $query->where('a.used', CommonEnum::STATE_IS_OK);
                } elseif ($status == OrderEnum::STATUS_REFUND) {
                    $query->where('a.state', OrderEnum::REFUND);
                }
            })
            ->unionAll(function ($query) use ($company_ids, $canteen_id, $dinner_id, $ordering_date, $department_id, $user_type, $username, $status) {
                $query->table("canteen_order_parent_t")
                    ->alias('a')
                    ->field("`a`.`id` AS `order_id`,
	`a`.`ordering_date` AS `ordering_date`,
	`c`.`name` AS `canteen`,
	`f`.`username` AS `username`,
IF
	(
		( `a`.`outsider` = 1 ),
		`d`.`phone`,
		`f`.`phone` 
	) AS `phone`,
	`b`.`name` AS `dinner`,
	(
		( `a`.`money` + `a`.`sub_money` ) + `a`.`delivery_fee` 
	) AS `money`,
	`a`.`delivery_fee` AS `delivery_fee`,
	`d`.`address` AS `address`,
	0 AS `status`,
	`a`.`outsider` AS `outsider`,
	`a`.`used` AS `used`,
	`a`.`create_time` AS `create_time`,
	`a`.`dinner_id` AS `dinner_id`,
	`a`.`company_id` AS `company_id`,
	`a`.`canteen_id` AS `canteen_id`,
	`d`.`province` AS `province`,
	`d`.`area` AS `area`,
	`d`.`city` AS `city`,
	`a`.`department_id` AS `department_id`,
	`a`.`receive` AS `receive`,
	`a`.`pay` AS `pay`,
	`a`.`state` AS `state`,
	`a`.`count` AS `count`,
	`d`.`name` AS `address_username`,
	`d`.`phone` AS `address_phone`,
	'more' AS `consumption_type`,
	`a`.`fixed` AS `fixed`")
                    ->leftJoin('canteen_dinner_t b', 'a.dinner_id = b.id')
                    ->leftJoin('canteen_canteen_t c', 'a.canteen_id = c.id')
                    ->leftJoin('canteen_company_staff_t f', 'a.staff_id = f.id')
                    ->leftJoin('canteen_user_address_t d', 'a.address_id = d.id')
                    ->where('ordering_date', $ordering_date)
                    ->where('a.type', OrderEnum::EAT_OUTSIDER)
                    ->where('a.pay', PayEnum::PAY_SUCCESS)
                    ->where(function ($query2) use ($status) {
                        if ($status == OrderEnum::STATUS_PAID) {
                            $query2->where('a.state', CommonEnum::STATE_IS_OK)
                                ->where('a.pay', PayEnum::PAY_SUCCESS)
                                ->where('a.receive', CommonEnum::STATE_IS_FAIL);
                        } elseif ($status == OrderEnum::STATUS_CANCEL) {
                            $query2->where('a.state', CommonEnum::STATE_IS_FAIL);
                        } elseif ($status == OrderEnum::STATUS_RECEIVE) {
                            $query2->where('a.state', CommonEnum::STATE_IS_OK)
                                ->where('a.pay', PayEnum::PAY_SUCCESS)
                                ->where('a.receive', CommonEnum::STATE_IS_OK)
                                ->where('a.used', CommonEnum::STATE_IS_FAIL);
                        } elseif ($status == OrderEnum::STATUS_COMPLETE) {
                            $query2->where('a.used', CommonEnum::STATE_IS_OK);
                        } elseif ($status == OrderEnum::STATUS_REFUND) {
                            $query2->where('a.state', OrderEnum::REFUND);
                        }
                    })
                    ->where(function ($query2) use ($company_ids, $canteen_id, $dinner_id) {
                        if (!empty($dinner_id)) {
                            $query2->where('a.dinner_id', $dinner_id);
                        } else {
                            if (!empty($canteen_id)) {
                               // $query2->where('a.canteen_id', $canteen_id);
                                if (strpos($canteen_id, ',') !== false) {
                                    $query2->whereIn('a.canteen_id', $canteen_id);
                                } else {
                                    $query2->where('a.canteen_id', $canteen_id);
                                }
                            } else {
                                if (strpos($company_ids, ',') !== false) {
                                    $query2->whereIn('a.company_id', $company_ids);
                                } else {
                                    $query2->where('a.company_id', $company_ids);
                                }
                            }
                        }
                    })
                    ->where(function ($query2) use ($department_id) {
                        if (!empty($department_id)) {
                            $query2->where('a.department_id', $department_id);
                        }
                    })
                    ->where(function ($query2) use ($user_type) {
                        if (!empty($user_type)) {
                            $query2->where('a.outsider', $user_type);
                        }
                    })
                    ->where(function ($query2) use ($username) {
                        if (!empty($username)) {
                            $query2->where('f.username', $username);
                        }
                    });
            })
            ->buildSql();
        return $subQuery;
    }

    public function getStatusAttr($value, $data)
    {
        if ($data['state'] == CommonEnum::STATE_IS_FAIL) {
            return OrderEnum::STATUS_CANCEL;
        } elseif ($data['state'] == OrderEnum::REFUND) {
            return OrderEnum::STATUS_REFUND;
        } else {
            if ($data['used'] == CommonEnum::STATE_IS_OK) {
                return OrderEnum::STATUS_COMPLETE;
            }

            if ($data['receive'] == CommonEnum::STATE_IS_OK) {
                return OrderEnum::STATUS_RECEIVE;
            }
            return OrderEnum::STATUS_PAID;
        }
    }

    public static function statistic($page, $size,
                                     $ordering_date, $company_ids, $canteen_id, $dinner_id,
                                     $status, $department_id, $user_type, $username)
    {
        $sql = self::getBuildSql($company_ids, $canteen_id, $dinner_id, $ordering_date, $department_id, $user_type, $username, $status);
        $list = Db::table($sql . ' a')
            //->hidden(['create_time', 'canteen_id', 'company_id', 'dinner_id', 'state', 'receive', 'used', 'pay'])
            ->order('dinner,order_id')
            ->paginate($size, false, ['page' => $page])
            ->toArray();
        /*    $list = self::where('ordering_date', $ordering_date)
            ->where(function ($query) use ($status) {
                if ($status == OrderEnum::STATUS_PAID) {
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->where('pay', PayEnum::PAY_SUCCESS)
                        ->where('receive', CommonEnum::STATE_IS_FAIL);
                } elseif ($status == OrderEnum::STATUS_CANCEL) {
                    $query->where('state', CommonEnum::STATE_IS_FAIL);
                } elseif ($status == OrderEnum::STATUS_RECEIVE) {
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->where('pay', PayEnum::PAY_SUCCESS)
                        ->where('receive', CommonEnum::STATE_IS_OK)
                        ->where('used', CommonEnum::STATE_IS_FAIL);
                } elseif ($status == OrderEnum::STATUS_COMPLETE) {
                    $query->where('used', CommonEnum::STATE_IS_OK);
                } elseif ($status == OrderEnum::STATUS_REFUND) {
                    $query->where('state', OrderEnum::REFUND);
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
            ->where(function ($query) use ($department_id) {
                if (!empty($department_id)) {
                    $query->where('department_id', $department_id);
                }
            })
            ->where(function ($query) use ($user_type) {
                if (!empty($user_type)) {
                    $query->where('outsider', $user_type);
                }
            })
            ->where(function ($query) use ($username) {
                if (!empty($username)) {
                    $query->where('username', $username);
                }
            })
            ->where('pay', PayEnum::PAY_SUCCESS)
            ->hidden(['create_time', 'canteen_id', 'company_id', 'dinner_id', 'state', 'receive', 'used', 'pay'])
            ->order('dinner,order_id')
            ->paginate($size, false, ['page' => $page])->toArray();*/
        return $list;
    }

    public static function exportStatistic($ordering_date, $company_ids, $canteen_id, $dinner_id, $status, $department_id, $user_type, $username)
    {
        $list = self::where('ordering_date', $ordering_date)
            ->where(function ($query) use ($status) {
                if ($status == OrderEnum::STATUS_PAID) {
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->where('pay', 'paid')
                        ->where('receive', CommonEnum::STATE_IS_FAIL);
                } elseif ($status == OrderEnum::STATUS_CANCEL) {
                    $query->where('state', CommonEnum::STATE_IS_FAIL);
                } elseif ($status == OrderEnum::STATUS_RECEIVE) {
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->where('pay', 'paid')
                        ->where('receive', CommonEnum::STATE_IS_OK)
                        ->where('used', CommonEnum::STATE_IS_FAIL);
                } elseif ($status == OrderEnum::STATUS_COMPLETE) {
                    $query->where('used', CommonEnum::STATE_IS_OK);
                } elseif ($status == OrderEnum::STATUS_REFUND) {
                    $query->where('state', OrderEnum::REFUND);
                }
            })
            ->where(function ($query) use ($company_ids, $canteen_id, $dinner_id) {
                if (!empty($dinner_id)) {
                    $query->where('dinner_id', $dinner_id);
                } else {
                    if (!empty($canteen_id)) {
                       // $query->where('canteen_id', $canteen_id);
                        if (strpos($canteen_id, ',') !== false) {
                            $query->whereIn('canteen_id', $canteen_id);
                        } else {
                            $query->where('canteen_id', $canteen_id);
                        }
                    } else {
                        if (strpos($company_ids, ',') !== false) {
                            $query->whereIn('company_id', $company_ids);
                        } else {
                            $query->where('company_id', $company_ids);
                        }
                    }
                }
            })
            ->where(function ($query) use ($department_id) {
                if (!empty($department_id)) {
                    $query->where('department_id', $department_id);
                }
            })
            ->where(function ($query) use ($username) {
                if (!empty($username)) {
                    $query->where('username', $username);
                }
            })
            ->where(function ($query) use ($user_type) {
                if (!empty($user_type)) {
                    $query->where('outsider', $user_type);
                }
            })
            ->field('order_id,ordering_date,canteen,username,phone,dinner,money,CONCAT(province,city,area,address)  as address,state,used,receive,status')
            ->order('used DESC')
            ->select()->toArray();
        return $list;
    }

    public static function officialStatistic($page, $size,
                                             $ordering_date, $dinner_id, $status, $department_id, $canteen_id)
    {
        $sql = self::getBuildSql(0, $canteen_id, $dinner_id, $ordering_date, $department_id, '', '', $status);
        $list = $records = Db::table($sql . ' a')
            ->field('order_id,province,city,area,address,address_username as username,address_phone as phone,used,count,money,delivery_fee,canteen_id,consumption_type,receive,fixed')
            ->order('used DESC')
            ->paginate($size, false, ['page' => $page])->toArray();
        return $list;
        /*    $list = self::where('canteen_id', $canteen_id)
                ->where('ordering_date', $ordering_date)
                ->where(function ($query) use ($status) {
                    if ($status == OrderEnum::STATUS_RECEIVE) {
                        $query->where('state', CommonEnum::STATE_IS_OK)
                            ->where('pay', 'paid')
                            ->where('receive', CommonEnum::STATE_IS_OK)
                            ->where('used', CommonEnum::STATE_IS_FAIL);
                    } elseif ($status == OrderEnum::STATUS_COMPLETE) {
                        $query->where('used', CommonEnum::STATE_IS_OK);
                    } else if ($status == OrderEnum::STATUS_UN_RECEIVE) {
                        $query->where('state', CommonEnum::STATE_IS_OK)
                            ->where('pay', 'paid')
                            ->where('receive', CommonEnum::STATE_IS_FAIL);
                    }
                })
                ->where(function ($query) use ($dinner_id) {
                    if (!empty($dinner_id)) {
                        $query->where('dinner_id', $dinner_id);
                    }
                })
                ->where(function ($query) use ($department_id) {
                    if (!empty($department_id)) {
                        $query->where('department_id', $department_id);
                    }
                })
                ->field('order_id,province,city,area,address,address_username as username,address_phone as phone,used,count,money,delivery_fee,canteen_id,consumption_type,receive,fixed')
                ->order('used DESC')
                ->paginate($size, false, ['page' => $page])->toArray();*/
        return $list;
    }

}