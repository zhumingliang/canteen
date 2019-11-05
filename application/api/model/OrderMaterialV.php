<?php


namespace app\api\model;


use think\Model;

class OrderMaterialV extends Model
{
    public static function orderMaterialsStatistic($page, $size, $time_begin, $time_end, $canteen_id, $company_id)
    {
        $time_end = addDay(1, $time_end);
        $statistic = self::where(function ($query) use ($company_id, $canteen_id) {
            if (empty($canteen_id)) {
                $query->where('company_id', $company_id);
            } else {
                $query->where('canteen_id', $canteen_id);

            }
        })
            ->whereBetweenTime('ordering_date', $time_begin, $time_end)
            ->field('order_id,detail_id,ordering_date,material,dinner_id,dinner,sum(order_count) as order_count,sum(material_count) as material_count')
            ->group('ordering_date,dinner_id,material')
            ->paginate($size, false, ['page' => $page])->toArray();
        return $statistic;
    }

}