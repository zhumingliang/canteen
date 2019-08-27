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

    public static function companyStaffs($page, $size, $c_id, $d_id)
    {
        $list = self::where('company_id', '=', $c_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->where(function ($query) use ($d_id) {
                if ($d_id) {
                    $query->where('d_id', '=', $d_id);
                }
            })
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