<?php


namespace app\api\validate;


class Supplier extends BaseValidate
{
    protected $rule = [
        'id' => 'require|isPositiveInteger',
        'c_id ' => 'require|isPositiveInteger',
        'company_id ' => 'require|isPositiveInteger',
        'name' => 'require|isNotEmpty',
        'account' => 'require|isNotEmpty',
        'pwd' => 'require|isNotEmpty'
    ];

    protected $scene = [
        'save' => ['c_id','name', 'pwd'],
        'update' => ['id'],
        'delete' => ['id'],
        'suppliers' => ['c_id']
    ];

}