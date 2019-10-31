<?php


namespace app\api\validate;


class Wallet extends BaseValidate
{
    protected $rule = [
        'id' => 'require|isPositiveInteger',
        'phone' => 'require|isMobile',
        'detail' => 'require|isNotEmpty',
        'card_num' => 'require|isNotEmpty',
        'company_id' => 'require|isPositiveInteger',
        'money' => 'require|isPositiveInteger',
    ];

    protected $scene = [
        'rechargeCash' => ['detail','money'],
        'bindCanteen' => ['canteen_id']
    ];
}