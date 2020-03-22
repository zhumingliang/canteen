<?php


namespace app\api\model;



use think\Model;

class ShopOrderSupplierV extends Model
{
    public static function orderDetailStatisticToSupplier($page, $size, $category_id, $product_id, $time_begin, $time_end, $supplier_id)
    {
        $time_end = addDay(1, $time_end);
        $orderings = self::where('supplier_id', $supplier_id)
            ->where(function ($query) use ($category_id, $product_id) {
                if (!empty($category_id)) {
                    $query->where('category_id', $category_id);
                }
                if (!empty($product_id)) {
                    $query->where('product_id', $product_id);
                }
            })->whereBetweenTime('create_time', $time_begin, $time_end)
            ->field('create_time,product,price*product_count as price,product_count as count,category')
            ->paginate($size, false, ['page' => $page])
            ->toArray();
        return $orderings;
    }

    public static function exportOrderDetailStatisticToSupplier($category_id, $product_id, $time_begin, $time_end, $supplier_id)
    {
        $time_end = addDay(1, $time_end);
        $orderings = self::where('supplier_id', $supplier_id)
            ->where(function ($query) use ($category_id, $product_id) {
                if (!empty($category_id)) {
                    $query->where('category_id', $category_id);
                }
                if (!empty($product_id)) {
                    $query->where('product_id', $product_id);
                }
            })->whereBetweenTime('create_time', $time_begin, $time_end)
            ->field('create_time,product,price*product_count as price,product_count as count,category')
           ->select()
            ->toArray();
        return $orderings;
    }

}