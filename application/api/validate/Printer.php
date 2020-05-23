<?php


namespace app\api\validate;


class Printer extends BaseValidate
{


    protected $rule = [
        'id' => 'require|isPositiveInteger',
        'company_id' => 'require|isPositiveInteger',
        'canteen_id' => 'require|isPositiveInteger',
        'name' => 'require|isNotEmpty',
        'number' => 'require|isNotEmpty',
        'code' => 'require|isNotEmpty',
        'out' => 'require|in:1,2,3',

    ];

    protected $scene = [
        'save' => ['company_id', 'canteen_id','name','number','code','out'],
        'delete' => ['id'],
        'update' => ['id'],
        'printers' => ['canteen_id'],
        //'save' => ['canteens', 'parent_id'],
    ];
}