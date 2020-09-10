<?php


namespace app\api\validate;


class Department extends BaseValidate
{
    protected $rule = [
        'id' => 'require|isPositiveInteger',
        'c_id' => 'require|isPositiveInteger',
        'company_id' => 'require|isPositiveInteger',
        'd_id' => 'require|isPositiveInteger',
        't_id' => 'require|isPositiveInteger',
        'username' => 'require|isNotEmpty',
        'code' => 'require|isNotEmpty',
        'phone' => 'require|isMobile',
        'state' => 'require|in:1,2',
        'card_num' => 'require|isNotEmpty',
        'name' => 'require|isNotEmpty',
        'canteens' => 'require|isNotEmpty',
        'parent_id' => 'require'
    ];

    protected $scene = [
        'save' => ['name', 'parent_id'],
        'update' => ['name', 'id'],
        'delete' => ['id'],
        'addStaff' => ['company_id', 'canteens', 'd_id', 't_id', 'username', 'code', 'phone', 'card_num'],
        'updateStaff' => ['id'],
        'handleStaff' => ['id', 'state'],
        'uploadStaffs' => ['c_id'],
        'moveStaffDepartment' => ['id', 'd_id'],
    ];

}