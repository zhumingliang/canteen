<?php


namespace app\api\validate;


class Takeout extends BaseValidate
{
    protected $rule = [
        'order_id' => 'require|isPositiveInteger',
        'used' => 'require|in:1,2,3',
        'ordering_date' => 'require|isNotEmpty',
        'ids' => 'require|isNotEmpty',
        'canteen_id ' => 'require',
        'company_ids ' => 'require',
        'dinner_id' => 'require',
    ];

    protected $scene = [
        'statistic' => ['canteen_id', 'company_ids', 'ordering_date', 'dinner_id','used'],
        'used'=>['ids'],
        'infoToPrint'=>['order_id']
    ];

}