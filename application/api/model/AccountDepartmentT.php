<?php


namespace app\api\model;


use think\Model;

class AccountDepartmentT extends Model
{
    public function department()
    {
        return $this->belongsTo('CompanyDepartmentT',
            'department_id', 'id');
    }

}