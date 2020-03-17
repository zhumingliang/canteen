<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class CompanyStaffV extends BaseModel
{
    public function getUrlAttr($value, $data)
    {
        return $this->prefixImgUrl($value, $data);
    }

    public function canteens()
    {
        return $this->hasMany('StaffCanteenT', 'staff_id', 'id');
    }

    public static function companyStaffs($page, $size, $c_id, $d_id)
    {
        $list = self::where('company_id', '=', $c_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->where(function ($query) use ($d_id) {
                if ($d_id) {
                    $query->where('d_id', '=', $d_id);
                }
            })
            ->with([
                'canteens' => function ($query) {
                    $query->with(['info' => function ($query2) {
                        $query2->field('id,name');
                    }])
                        ->field('id,staff_id,canteen_id')
                        ->where('state', '=', CommonEnum::STATE_IS_OK);
                }
            ])
            ->hidden(['company_id', 'state'])
            ->order('id desc')
            ->paginate($size, false, ['page' => $page]);
        return $list;

    }

    public static function exportStaffs($company_id, $department_id)
    {
        $list = self::where('company_id', $company_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->where(function ($query) use ($department_id) {
                if ($department_id) {
                    $query->where('d_id', $department_id);
                }
            })
            ->with([
                'canteens' => function ($query) {
                    $query->with(['info' => function ($query2) {
                        $query2->field('id,name');
                    }])
                        ->field('id,staff_id,canteen_id')
                        ->where('state', '=', CommonEnum::STATE_IS_OK);
                }
            ])
            ->field('id,company,department,state,type,code,username,phone,card_num')
            ->order('create_time desc')
            ->select()->toArray();
        return $list;

    }

    public static function userCanteens($company_id, $phone)
    {
        $canteens = self::where('phone', $phone)->where('company_id', $company_id)
            ->field('c_id as canteen_id,canteen')
            ->select();
        return $canteens;
    }

    public static function staffsForRecharge($page, $size, $department_id, $key, $company_id)
    {
        $list = self::where('company_id', '=', $company_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->where(function ($query) use ($department_id) {
                if ($department_id) {
                    $query->where('d_id', '=', $department_id);
                }
            })
            ->where(function ($query) use ($key) {
                if ($key) {
                    $query->where('username|phone|code', 'like', "%" . $key . "%");
                }
            })
            ->field('id,company,department,code,card_num,username,phone')
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page]);
        return $list;
    }
}