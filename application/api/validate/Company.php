<?php


namespace app\api\validate;


class Company extends BaseValidate
{
    protected $rule = [
        'id' => 'require|isPositiveInteger',
        'name' => 'require|isNotEmpty',
        'parent_id' => 'require'
    ];

    protected $scene = [
        'save' => ['name', 'parent_id'],
        'managerCompanies' => ['name'],
    ];

}