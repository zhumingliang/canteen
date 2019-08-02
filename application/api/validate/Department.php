<?php


namespace app\api\validate;


class Department extends BaseValidate
{
    protected $rule = [
        'id' => 'require|isPositiveInteger',
        'c_id' => 'require|isPositiveInteger',
        'd_id' => 'require|isPositiveInteger',
        't_id' => 'require|isPositiveInteger',
        'username' => 'require|isNotEmpty',
        'code' => 'require|isNotEmpty',
        'phone' => 'require|isMobile',
        'card_num' => 'require|isNotEmpty',
        'name' => 'require|isNotEmpty',
        'parent_id' => 'require'
    ];

    protected $scene = [
        'save' => ['name', 'parent_id'],
        'update' => ['name', 'id'],
        'delete' => ['id'],
        'departments' => ['c_id'],
        'addStaff' => ['c_id', 'd_id', 't_id', 'username', 'code', 'phone', 'card_num'],
    ];

}