<?php


namespace app\api\validate;


class Canteen extends BaseValidate
{
    protected $rule = [
        'id' => 'require|isPositiveInteger',
        'canteens' => 'require|isNotEmpty',
        'c_id' => 'require|isPositiveInteger'
    ];

    protected $scene = [
        'save' => ['name', 'parent_id'],
    ];
}