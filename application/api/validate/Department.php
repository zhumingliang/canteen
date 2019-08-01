<?php


namespace app\api\validate;


class Department extends BaseValidate
{
    protected $rule = [
        'id' => 'require|isPositiveInteger',
        'c_id' => 'require|isPositiveInteger',
        'name' => 'require|isNotEmpty',
        'parent_id' => 'require'
    ];

    protected $scene = [
        'save' => ['name', 'parent_id'],
        'update' => ['name', 'id'],
        'delete' => ['id'],
        'departments' => ['c_id'],
        'addStaff' => ['c_id'],
    ];

}