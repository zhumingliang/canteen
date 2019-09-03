<?php


namespace app\api\validate;


class Canteen extends BaseValidate
{
    protected $rule = [
        'id' => 'require|isPositiveInteger',
        'canteens' => 'require|isNotEmpty',
        'c_id' => 'require|isPositiveInteger',
        'd_id' => 'require|isPositiveInteger',
        't_id' => 'require|isPositiveInteger',
        'dinners' => 'require',
        'account' => 'require',
        'type' => 'require|in:1,2',
        'clean_type' => 'require|in:1,2,3',
        'taste' => 'require|in:1,2,3,4,5',
        'service' => 'require|in:1,2,3,4,5',
        'clean_day' => 'require',
        'unordered_meals' => 'require|in:1,2',
    ];

    protected $scene = [
        'save' => ['name', 'parent_id'],
        'saveConfiguration' => ['c_id', 'dinners', 'account'],
        'configuration' => ['c_id'],
        'updateConfiguration' => ['c_id'],
        'saveConsumptionStrategy' => ['c_id', 't_id','unordered_meals'],
        'saveComment' => ['taste', 'service'],
    ];
}