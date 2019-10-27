<?php


namespace app\api\validate;


class Menu extends BaseValidate
{
    protected $rule = [
        'id' => 'require|isPositiveInteger',
        'c_id' => 'require|isPositiveInteger',
        'company_id' => 'require',
        'canteen_id' => 'require',
        'd_id' => 'require|isPositiveInteger',
        'detail' => 'require|isNotEmpty'
    ];

    protected $scene = [
        'save' => ['c_id', 'd_id', 'detail'],
        'companyMenus' => ['company_id'],
        'canteenMenus' => ['canteen_id'],
    ];
}