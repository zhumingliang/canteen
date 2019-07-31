<?php


namespace app\api\validate;


class Role extends BaseValidate
{
    protected $rule = [
        'id' => 'require|isPositiveInteger',
        'account' => 'require|isNotEmpty',
        'passwd' => 'require|isNotEmpty',
        'role' => 'require|isNotEmpty',
        'c_id' => 'require|isPositiveInteger',
        'state' => 'require|in:1,2',
        'belong_ids' => 'require|isNotEmpty',
    ];

    protected $scene = [
        'save' => ['account', 'passwd', 'role'],
        'handel' => ['id', 'state'],
        'distribution' => ['id', 'belong_ids'],
        'distributionHandel' => ['id', 'state'],
    ];
}