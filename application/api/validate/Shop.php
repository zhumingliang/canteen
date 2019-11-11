<?php


namespace app\api\validate;


class Shop extends BaseValidate
{
    protected $rule = [
        'id' => 'require|isPositiveInteger',
        'product_id' => 'require|isPositiveInteger',
        'c_id' => 'require|isPositiveInteger',
        'company_id ' => 'require|isPositiveInteger',
        'supplier_id ' => 'require|isPositiveInteger',
        'category_id ' => 'require|isPositiveInteger',
        'name' => 'require|isNotEmpty',
        'unit' => 'require|isNotEmpty',
        'price' => 'require|isNotEmpty',
        'count' => 'require|isPositiveInteger',
        'state' => 'require|in:1,2,3',
        'distribution' => 'require|in:1,2',
        'products' => 'require|isNotEmpty',
        'time_begin' => 'require|isNotEmpty',
        'time_end' => 'require|isNotEmpty',
    ];

    protected $scene = [
        'saveProduct' => [ 'unit', 'name', 'price', 'count'],
        'saveShop' => ['c_id','name'],
        'updateProduct' => ['id'],
        'handel' => ['id', 'state'],
        'product' => ['id'],
        'saveProductStock' => ['product_id', 'count'],
        'saveOrder' => ['count', 'distribution', 'products'],
        'orderCancel' => ['id'],
        'orderStatisticToManager' => ['time_begin','time_end'],
        'orderDetailStatisticToSupplier' => ['time_begin','time_end'],
    ];
}