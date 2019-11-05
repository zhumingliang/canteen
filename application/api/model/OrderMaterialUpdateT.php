<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class OrderMaterialUpdateT extends Model
{
    public static function orderRecords($time_begin,$time_end,$canteen_id,$company_id)
    {
        $list = self::where('state', CommonEnum::STATE_IS_OK)
            ->whereBetweenTime('ordering_date', $time_begin, $time_end)
            ->where(function ($query) use ($company_id, $canteen_id) {
                if (empty($canteen_id)) {
                    $query->where('company_id', $company_id);
                } else {
                    $query->where('canteen_id', $canteen_id);
                }
            })
            ->select();
        return $list;


    }

}