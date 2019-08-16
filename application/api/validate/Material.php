<?php


namespace app\api\validate;


class Material extends BaseValidate
{
    protected $rule = [
        'id' => 'require|isPositiveInteger',
        'c_id' => 'require|isPositiveInteger',
        'name' => 'require|isNotEmpty',
        'price' => 'require|isNotEmpty',
        'unit' => 'require|isNotEmpty'
    ];

    protected $scene = [
        'save' => ['c_id', 'name', 'price','unit'],
        'uploadMaterials' => ['c_id'],
        'update' => ['id'],
        'handel' => ['id'],
    ];
}