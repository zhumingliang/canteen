<?php


namespace app\api\validate;


class Wallet extends BaseValidate
{
    protected $rule = [
        'id' => 'require|isPositiveInteger',
        'account_id' => 'require|isPositiveInteger',
        'order_id' => 'require|isNotEmpty',
        'module_id' => 'require|isPositiveInteger',
        'canteen_id' => 'require|isPositiveInteger',
        'dinner_id' => 'require|isPositiveInteger',
        'staff_ids' => 'require|isNotEmpty',
        'type' => 'require|in:1,2',
        'method_id' => 'require|in:1,2',
        'phone' => 'require|isMobile',
        'detail' => 'require|isNotEmpty',
        'card_num' => 'require|isNotEmpty',
        'time_begin' => 'require|isNotEmpty',
        'time_end' => 'require|isNotEmpty',
        'consumption_date' => 'require|isNotEmpty',
        'company_id' => 'require|isPositiveInteger',
        'money' => 'require|isNotEmpty',
    ];

    protected $scene = [
        'rechargeCash' => ['detail','money'],
        'rechargeSupplement' => ['canteen_id','money','staff_ids','type','consumption_date','dinner_id'],
        'rechargeAdmins' => ['module_id'],
        'rechargeRecords' => ['time_begin','time_end'],
        'getPreOrder' => ['order_id'],
        'saveOrder' => ['money','method_id']
    ];
}