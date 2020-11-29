<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class CompanyAccountT extends Model
{
    public function getType($value)
    {
        $Type = [1 => '基本户', 2 => '附加户'];
        return $Type[$value];
    }

    public function admin()
    {
        return $this->belongsTo('AdminT', 'admin_id', 'id');
    }

    public function company()
    {
        return $this->belongsTo('CompanyT', 'company_id', 'id');

    }

    public function departments()
    {
        return $this->hasMany('AccountDepartmentT', 'account_id', 'id');
    }


    public static function accounts($companyId)
    {
        $accounts = self::where('company_id', $companyId)
            //->where('state', CommonEnum::STATE_IS_OK)
            ->with([
                'admin' => function ($query) {
                    $query->field('id,role,phone');
                },
                'company' => function ($query) {
                    $query->field('id,name');
                },
                'departments' => function ($query) {
                    $query->with(['department' => function ($query2) {
                        $query2->field('id,name');
                    }])
                        ->where('state', CommonEnum::STATE_IS_OK)->field('id,account_id,department_id');
                }
            ])
            ->field('id,admin_id,company_id,type,department_all,name,clear,sort,fixed_type,next_time,create_time,state')
            ->select();
        return $accounts;
    }

    public static function accountForSearch($companyId)
    {
        $accounts = self::where('company_id', $companyId)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->field('id,type,sort,fixed_type,name')
            ->select();
        return $accounts;
    }

    public static function account($id)
    {
        $accounts = self::where('id', $id)
            ->with([
                'company' => function ($query) {
                    $query->field('id,name');
                },
                'departments' => function ($query) {
                    $query->with(['department' => function ($query2) {
                        $query2->field('id,name');
                    }])
                        ->where('state', CommonEnum::STATE_IS_OK)->field('id,account_id,department_id');
                }
            ])
            ->hidden(['create_time', 'update_time', 'admin_id'])
            ->find();
        return $accounts;
    }

    public static function accountsWithSorts($companyId)
    {
        $accounts = self::where('company_id', $companyId)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->field('id,name,sort,type,fixed_type')
            ->order('sort')
            ->select()->toArray();
        return $accounts;
    }

    public static function accountsWithoutNonghang($companyId)
    {
        $accounts = self::where('company_id', $companyId)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->field('id,name')
            ->select();
        return $accounts;
    }

    public static function accountsWithSortsAndDepartment($companyId)
    {
        $accounts = self::where('company_id', $companyId)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->with([
                'departments' => function ($query) {
                    $query->with(['department' => function ($query2) {
                        $query2->field('id,name');
                    }])
                        ->where('state', CommonEnum::STATE_IS_OK)->field('id,account_id,department_id');
                }
            ])
            ->field('id,name,sort,department_all')
            ->order('sort')
            ->select()->toArray();
        return $accounts;
    }

}