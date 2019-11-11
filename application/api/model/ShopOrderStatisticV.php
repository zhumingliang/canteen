<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class ShopOrderStatisticV extends Model
{
    public static function shopOrderConsumptionStatisticToSupplier($page, $size, $category_id,
                                                                   $product_id,
                                                                   $status, $time_begin, $time_end, $type, $supplier_id)
    {
        $time_end = addDay(1, $time_end);
        $statistic = self::where('supplier_id', $supplier_id)
            ->whereBetweenTime('create_time', $time_begin, $time_end)
            ->where(function ($query) use ($category_id, $product_id) {
                if (!empty($category_id)) {
                    $query->where('category_id', $category_id);
                }
                if (!empty($product_id)) {
                    $query->where('product_id', $product_id);
                }
            })
            ->where(function ($query) use ($status) {
                if ($status == 1) {
                    //已完成
                    $query->where('used', CommonEnum::STATE_IS_OK);
                } elseif ($status == 2) {
                    //已取消
                    $query->where('state', CommonEnum::STATE_IS_FAIL);
                } elseif ($status == 3) {
                    //待取货
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->where('distribution', 1)
                        ->where('used', CommonEnum::STATE_IS_FAIL);
                } elseif ($status == 4) {
                    //待送货
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->where('distribution', 2)
                        ->where('used', CommonEnum::STATE_IS_FAIL);
                }

            })
            ->group(function ($query) use ($type) {

            })
            ->paginate($size, false, ['page' => $page]);


    }

}