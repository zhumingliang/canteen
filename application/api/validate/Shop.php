<?php


namespace app\api\validate;


class Shop extends BaseValidate
{
    protected $rule = [
        'id' => 'require|isPositiveInteger',
        'product_id' => 'require|isPositiveInteger',
        'company_id ' => 'require|isPositiveInteger',
        'supplier_id ' => 'require|isPositiveInteger',
        'category_id ' => 'require|isPositiveInteger',
        'name' => 'require|isNotEmpty',
        'unit' => 'require|isNotEmpty',
        'price' => 'require|isNotEmpty',
        'count' => 'require|isPositiveInteger',
        'state' => 'require|in:1,2,3',
        'distribution' => 'require|in:1,2',
        'products' => 'require|isNotEmpty'
    ];

    protected $scene = [
        'saveProduct' => ['company_id', 'supplier_id', 'category_id', 'unit', 'name', 'price', 'count'],
        'updateProduct' => ['id'],
        'handel' => ['id', 'state'],
        'product' => ['id'],
        'saveProductStock' => ['product_id', 'count'],
        'saveOrder' => ['count', 'distribution', 'products'],
        'orderCancel' => ['id'],
    ];
}