<?php


namespace app\api\validate;


class Category extends BaseValidate
{
    protected $rule = [
        'id' => 'require|isPositiveInteger',
        'c_id ' => 'require|isPositiveInteger',
        'name' => 'require|isNotEmpty'
    ];

    protected $scene = [
        'save' => ['c_id', 'name',],
        'update' => ['id'],
        'delete' => ['id'],
        'categories' => ['c_id'],
    ];
}