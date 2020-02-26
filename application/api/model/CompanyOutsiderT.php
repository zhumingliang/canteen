<?php


namespace app\api\model;


use think\Model;

class CompanyOutsiderT extends Model
{
    public function canteen()
    {
        return $this->hasMany('OutsiderCanteenV', 'outsider_id', 'id');

    }

    public static function outsiders($page, $size, $company_id)
    {
        $list = self::with([
            'canteen' => function ($query) {
                $query->field('id,canteen_id,outsider_id,canteen_name');
            }
        ])
            ->where(function ($query) use ($company_id) {
                if ($company_id) {
                    $query->where('company_id', $company_id);
                }
            })
            ->field('id,rules,company_id,create_time')
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page]);
        return $list;

    }

    public static function outsider($id)
    {
        $role = self::where('id', $id)
            ->with([
                'canteen' => function ($query) {
                    $query->field('id,canteen_id,outsider_id,canteen_name');
                }
            ])
            ->field('id,rules,company_id,create_time')
            ->find();
        return $role;
    }

    public static function getCompanyOutsiderWithCompanyId($company_id)
    {
        $outsider = self::where('company_id', $company_id)->find();
        return $outsider;

    }
}