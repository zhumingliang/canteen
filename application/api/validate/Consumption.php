<?php


namespace app\api\validate;


class Consumption extends BaseValidate
{
    protected $rule = [
        'type' => 'require|isNotEmpty',
        'code' => 'require|isNotEmpty',
    ];

    protected $scene = [
        'staff' => ['type', 'code'],
        'managerCompanies' => ['name']
    ];

}