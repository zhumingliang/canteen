<?php


namespace app\api\model;


use think\Model;
use function GuzzleHttp\Promise\queue;

class OrderTakeoutStatisticV extends Model
{
    public static function statistic($page, $size,
                                     $ordering_date, $company_ids, $canteen_id, $dinner_id, $used)
    {
        $list = self::whereBetweenTime('ordering_date', $ordering_date)
            ->where(function ($query) use ($used) {
                if ($used < 3) {
                    $query->where('used', $used);
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
            ->hidden(['create_time', 'canteen_id', 'company_id', 'dinner_id'])
            ->order('used DESC')
            ->paginate($size, false, ['page' => $page]);
        return $list;
    }

    public static function exportStatistic($ordering_date, $company_ids, $canteen_id, $dinner_id, $used)
    {
        $list = self::whereBetweenTime('ordering_date', $ordering_date)
            ->where(function ($query) use ($used) {
                if ($used < 3) {
                    $query->where('used', $used);
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
            ->field('order_id,ordering_date,canteen,username,phone,dinner,money,address,if(used=1,"已派单","未派单") as used')
            ->order('used DESC')
            ->select()->toArray();
        return $list;
    }

}