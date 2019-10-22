<?php


namespace app\api\model;


use think\Model;

class OrderStatisticV extends Model
{
    public static function statistic($time_begin, $time_end, $company_id, $canteen_id, $page, $size)
    {
        $list = self::whereBetweenTime('ordering_date', $time_begin, $time_end)
            ->where(function ($query) use ($company_id, $canteen_id) {
                if (empty($canteen_id)) {
                    if (strpos($company_id, ',') !== false) {
                        $query->whereIn('company_id', $company_id);
                    } else {
                        $query->where('company_id', $company_id);
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

}