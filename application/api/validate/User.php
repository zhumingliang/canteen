<?php


namespace app\api\validate;


class User extends BaseValidate
{
    protected $rule = [
        'id' => 'require|isPositiveInteger',
        'phone' => 'require|isMobile',
        'code' => 'require|isNotEmpty',
        'company_id' => 'require|isPositiveInteger',
    ];

    protected $scene = [
        'bindPhone' => ['phone', 'code'],
        'bindCompany' => ['company_id']
    ];
}