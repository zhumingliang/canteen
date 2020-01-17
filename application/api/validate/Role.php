<?php


namespace app\api\validate;


class Role extends BaseValidate
{
    protected $rule = [
        'id' => 'require|isPositiveInteger',
        'phone' => 'require|isMobile',
        'account' => 'require|isNotEmpty',
        'passwd' => 'require|isNotEmpty',
        'role' => 'require|isNotEmpty',
        'c_id' => 'require|isPositiveInteger',
        'state' => 'require|in:1,2,3',
        'belong_ids' => 'require|isNotEmpty',
    ];

    protected $scene = [
        'save' => ['account', 'passwd', 'role', 'phone'],
        'handel' => ['id', 'state'],
        'role' => ['id']
    ];
}