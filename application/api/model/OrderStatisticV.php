<?php


namespace app\api\model;


use think\Model;

class OrderStatisticV extends Model
{
    public static function statistic($time_begin, $time_end, $company_ids, $canteen_id, $page, $size)
    {
        $time_end = addDay(1, $time_end);
        $list = self::whereBetweenTime('ordering_date', $time_begin, $time_end)
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
            ->field('ordering_date,company,canteen,dinner,count(order_id) as count')
            ->order('ordering_date DESC')
            ->group('dinner_id')
            ->paginate($size, false, ['page' => $page]);
        return $list;

    }

    public static function detail($company_ids, $time_begin,
                                  $time_end, $page, $size, $name,
                                  $phone, $canteen_id, $department_id,
                                  $dinner_id)
    {
        $time_end = addDay(1, $time_end);
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
            ->field('order_id,ordering_date,username,canteen,department,dinner')
            ->order('order_id DESC')
            ->paginate($size, false, ['page' => $page]);
        return $list;

    }

}