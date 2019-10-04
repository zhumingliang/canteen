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
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page]);
        return $list;

    }

    public static function userCanteens($company_id, $phone)
    {
        $canteens = self::where('phone', $phone)->where('company_id', $company_id)
            ->field('c_id as canteen_id,canteen')
            ->select();
        return $canteens;
    }
}