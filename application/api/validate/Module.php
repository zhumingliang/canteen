<?php


namespace app\api\validate;


class Module extends BaseValidate
{
    protected $rule = [
        'id' => 'require|isPositiveInteger',
        'c_id' => 'require|isPositiveInteger',
        'name' => 'require|isNotEmpty',
        'url' => 'require|isNotEmpty',
        'parent_id' => 'require',
        'type' => 'require|in:1,2,3',
        'default' => 'require|in:1,2'
    ];

    protected $scene = [
        'saveSystem' => ['name', 'url', 'parent_id'],
        'saveSystemCanteen' => ['name', 'url', 'parent_id', 'type', 'default'],
        'saveSystemShop' => ['name', 'url', 'parent_id', 'type', 'default'],
        'modules' => ['type'],
        'handelSystem' => ['id', 'state'],
        'updateModule' => ['id', 'type'],
        'canteenModule' => ['c_id']
    ];

}