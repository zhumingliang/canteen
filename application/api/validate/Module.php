<?php


namespace app\api\validate;


class Module extends BaseValidate
{
    protected $rule = [
        'id' => 'require|isPositiveInteger',
        'c_id' => 'require|isPositiveInteger',
        'company_id' => 'require|isPositiveInteger',
        'name' => 'require|isNotEmpty',
        'url' => 'require|isNotEmpty',
        'modules' => 'require|isNotEmpty',
        'shop' => 'require',
        'canteen' => 'require',
        'parent_id' => 'require',
        'state' => 'require|in:1,2,3',
        'type' => 'require|in:1,2,3',
        'default' => 'require|in:1,2'
    ];

    protected $scene = [
        'saveSystem' => ['name', 'url', 'parent_id'],
        'saveSystemCanteen' => ['name', 'url', 'parent_id', 'type', 'default'],
        'saveSystemShop' => ['name', 'url', 'parent_id', 'type', 'default'],
        'modules' => ['type'],
        'handelSystem' => ['id', 'state'],
        'updateModule' => ['id'],
        'canteenModule' => ['c_id'],
        'updateCompanyModule' => ['company_id','canteen'],
        'canteenModulesWithoutSystem' => ['company_id'],
        'handelModuleDefaultStatus' => ['modules']
    ];

}