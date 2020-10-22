<?php


namespace app\api\validate;


class Account extends BaseValidate
{
    protected $rule = [
        'id' => 'require|isPositiveInteger',
        'company_id' => 'require|isPositiveInteger',
        'type' => 'require|in:1,2',
        'department_all' => 'require|in:1,2',
        'name' => 'require|isNotEmpty',
        'clear' => 'require|in:1,2,3',
        'clear_type' => 'require|in:day,week,month,quarter,year',
        'sort' => 'require|isPositiveInteger',

    ];

    protected $scene = [
        'save' => ['company_id', 'type','department_all','name','clear','clear_type','sort'],
    ];

}