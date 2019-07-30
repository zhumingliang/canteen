<?php


namespace app\api\validate;


class Canteen extends BaseValidate
{
    protected $rule = [
        'id' => 'require|isPositiveInteger',
        'canteens' => 'require|isNotEmpty',
        'c_id' => 'require|isPositiveInteger',
        'dinners' => 'require',
        'account' => 'require',
        'type' => 'require|in:1,2',
        'clean_type' => 'require|in:1,2,3',
        'clean_day' => 'require',
    ];

    protected $scene = [
        'save' => ['name', 'parent_id'],
        'saveConfiguration' => ['c_id', 'dinners', 'account'],
        'configuration' => ['c_id'],
        'updateConfiguration' => ['c_id'],
    ];
}