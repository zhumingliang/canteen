<?php


namespace app\api\validate;


class Takeout extends BaseValidate
{
    protected $rule = [
        'order_id' => 'require|isPositiveInteger',
        'status' => 'require|in:1,2,3,4,5,6',
        'type' => 'require|in:1,2,3,4',
        'ordering_date' => 'require|isNotEmpty',
        'ids' => 'require|isNotEmpty',
        'canteen_id ' => 'require',
        'company_ids ' => 'require',
        'dinner_id' => 'require',
        'department_id' => 'require',
    ];

    protected $scene = [
        'statistic' => ['canteen_id', 'company_ids', 'ordering_date', 'dinner_id', 'status'],
        'officialStatistic' => ['ordering_date', 'dinner_id', 'status', 'department_id'],
        'used' => ['id'],
        'handel' => ['ids', 'type', 'canteen_id'],
        'infoToPrint' => ['order_id']
    ];

}