<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use app\lib\enum\OrderEnum;
use think\Model;

class OrderSettlementV extends Model
{
    public function getMoneyAttr($value)
    {
        return abs($value);
    }

    public static function orderSettlement($page, $size,
                                           $name, $phone, $canteen_id, $department_id, $dinner_id,
                                           $consumption_type, $time_begin, $time_end, $company_ids)
    {
        //$time_end = addDay(1, $time_end);
        $list = self::whereBetweenTime('ordering_date', $time_begin, $time_end)
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
            ->where(function ($query) use ($consumption_type) {
                if ($consumption_type < 6) {
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
                    }
                }

            })
            ->field('order_id,used_time,username,phone,canteen,department,dinner,booking,used,type,ordering_date,money')
            ->order('order_id DESC')
            ->paginate($size, false, ['page' => $page])->toArray();
        return $list;

    }


    public static function exportOrderSettlement($name, $phone, $canteen_id, $department_id, $dinner_id,
                                                 $consumption_type, $time_begin, $time_end, $company_ids)
    {
        // $time_end = addDay(1, $time_end);
        $list = self::whereBetweenTime('ordering_date', $time_begin, $time_end)
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
            ->where(function ($query) use ($consumption_type) {
                if ($consumption_type < 6) {
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
                    }
                }

            })
            ->field('used_time,department,username,phone,canteen,dinner,booking,used,type,money,remark')
            ->order('order_id DESC')
            ->select()->toArray();
        return $list;

    }

}