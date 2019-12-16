<?php


namespace app\api\model;


use mysql_xdevapi\Statement;
use think\Model;

class OrderMaterialV extends Model
{
    public
    static function orderMaterialsStatistic($page, $size, $time_begin, $time_end, $canteen_id, $company_id)
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
            ->field('order_id,ordering_date,material,dinner_id,dinner,sum(order_count) * sum(material_count) as order_count')
            ->group('ordering_date,dinner_id,material')
            ->paginate($size, false, ['page' => $page])->toArray();
        return $statistic;
    }

    public
    static function orderMaterials($time_begin, $time_end, $canteen_id, $company_id)
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
            ->field('ordering_date,material,dinner_id,dinner,sum(order_count) * sum(material_count) as order_count')
            ->group('ordering_date,dinner_id,material')
            ->select()->toArray();
        return $statistic;
    }

    public
    static function allRecords($time_begin, $time_end, $canteen_id)
    {
        $time_end = addDay(1, $time_end);
        $statistic = self::where('canteen_id', $canteen_id)
            ->whereBetweenTime('ordering_date', $time_begin, $time_end)
            ->field('dinner_id,ordering_date,material,sum(order_count) * sum(material_count) as order_count')
            ->group('ordering_date,dinner_id,material')
            ->select();
        return $statistic;
    }

}