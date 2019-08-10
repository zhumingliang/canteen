<?php


namespace app\api\validate;


class Food extends BaseValidate
{
    protected $rule = [
        'id' => 'require|isPositiveInteger',
        'c_id' => 'require|isPositiveInteger',
        'f_type' => 'require|in:1,2',
        'm_d_id' => 'require|isPositiveInteger',
        'name' => 'require|isNotEmpty',
        'price' => 'require|isNotEmpty',
        'chef' => 'require|isNotEmpty',
        'des' => 'require|isNotEmpty',
        'img_url' => 'require|isNotEmpty'
    ];

    protected $scene = [
        'save' => ['f_type', 'c_id', 'm_d_id', 'name', 'price', 'chef', 'des', 'img_url'],
        'foods' => ['f_type'],
        'update' => ['id'],
        'food' => ['id'],
    ];
}