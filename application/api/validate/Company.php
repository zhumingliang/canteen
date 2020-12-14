<?php


namespace app\api\validate;


class Company extends BaseValidate
{
    protected $rule = [
        'id' => 'require|isPositiveInteger',
        'company_id' => 'require|isPositiveInteger',
        'name' => 'require|isNotEmpty',
        'mch_id' => 'require|isNotEmpty',
        'app_id' => 'require|isNotEmpty',
        'parent_id' => 'require'
    ];

    protected $scene = [
        'save' => ['name', 'parent_id'],
        'managerCompanies' => ['name'],
        'consumptionLocation' => ['company_id'],
        'saveCompanyWxConfig' => ['company_id', 'mch_id'],
    ];

}