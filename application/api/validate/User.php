<?php


namespace app\api\validate;


class User extends BaseValidate
{
    protected $rule = [
        'id' => 'require|isPositiveInteger',
        'phone' => 'require|isMobile',
        'code' => 'require|isNotEmpty',
        'type' => 'require|in:1,2',
        'canteen_id' => 'require|isPositiveInteger',
    ];

    protected $scene = [
        'bindPhone' => ['phone', 'code'],
        'bindCanteen' => ['canteen_id']
    ];
}