<?php


namespace app\api\validate;


class Wallet extends BaseValidate
{
    protected $rule = [
        'id' => 'require|isPositiveInteger',
        'module_id' => 'require|isPositiveInteger',
        'phone' => 'require|isMobile',
        'detail' => 'require|isNotEmpty',
        'card_num' => 'require|isNotEmpty',
        'time_begin' => 'require|isNotEmpty',
        'time_end' => 'require|isNotEmpty',
        'company_id' => 'require|isPositiveInteger',
        'money' => 'require|isPositiveInteger',
    ];

    protected $scene = [
        'rechargeCash' => ['detail','money'],
        'rechargeAdmins' => ['module_id'],
        'rechargeRecords' => ['time_begin','time_end']
    ];
}