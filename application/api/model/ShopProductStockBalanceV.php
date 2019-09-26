<?php


namespace app\api\model;


use think\Model;

class ShopProductStockBalanceV extends Model
{
    public static function getProductStock($product_id)
    {
        $stock = self::where('product_id', $product_id)
            ->sum('count');
        return $stock;

    }

}