<?php


namespace app\api\model;


use think\Model;

class OrderConsumptionV extends Model
{

    public function getStatusAttr($value)
    {
        $status = ['1' => '订餐就餐', 2 => '未订餐就餐', 3 => '未订餐就餐', 4 => '补录操作'];
        return $status[$value];
    }

    public static function consumptionStatisticByDepartment($canteen_id, $status, $department_id,
                                                            $username, $staff_type_id, $time_begin,
                                                            $time_end, $company_id)
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
            ->whereBetweenTime('consumption_date', $time_begin, $time_end)
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
                    $query->where('username', $username);
                }
                if (!empty($status)) {
                    $query->where('staff_type_id', $staff_type_id);
                }

            })
            ->field('department_id,department,dinner_id,dinner,sum(order_count) as order_count,sum(order_money) as order_money')
            ->group('department_id,dinner')
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
            ->whereBetweenTime('consumption_date', $time_begin, $time_end)
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
                    $query->where('username', $username);
                }
                if (!empty($status)) {
                    $query->where('staff_type_id', $staff_type_id);
                }

            })
            ->field('sum(order_count) as order_count,sum(order_money) as order_money')
            ->find();
        return $statistic;
    }

    public static function consumptionStatisticByStatus($canteen_id, $status, $department_id,
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
                    $query->where('username', $username);
                }
                if (!empty($staff_type_id)) {
                    $query->where('staff_type_id', $staff_type_id);
                }

            })
            ->field('status,department,dinner_id,dinner,sum(order_count) as order_count,sum(order_money) as order_money')
            ->group('status,dinner')
            ->select()
            ->toArray();
        return $statistic;
    }

    public static function consumptionStatisticByCanteen($canteen_id, $status, $department_id,
                                                         $username, $staff_type_id, $time_begin,
                                                         $time_end, $company_id)
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
            ->whereBetweenTime('consumption_date', $time_begin, $time_end)
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
                    $query->where('username', $username);
                }
                if (!empty($status)) {
                    $query->where('staff_type_id', $staff_type_id);
                }

            })
            ->field('canteen,dinner_id,dinner,sum(order_count) as order_count,sum(order_money) as order_money')
            ->group('canteen_id,dinner')
            ->select()
            ->toArray();
        return $statistic;
    }

    public static function consumptionStatisticByStaff($canteen_id, $status, $department_id,
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
            ->whereBetweenTime('consumption_date', $time_begin, $time_end)
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
                    $query->where('username', $username);
                }
                if (!empty($status)) {
                    $query->where('staff_type_id', $staff_type_id);
                }

            })
            ->field('staff_type,dinner_id,dinner,sum(order_count) as order_count,sum(order_money) as order_money')
            ->group('staff_type_id,dinner')
            ->select()
            ->toArray();
        return $statistic;
    }


}