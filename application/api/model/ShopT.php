<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class ShopT extends Model
{
    public static function shop($shop_id)
    {
        $shop = self::where('id', $shop_id)
            ->find();
        return $shop;
    }

    public static function shopWithCompanyID($company_id)
    {
        $shop = self::where('c_id', $company_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->find();
        return $shop;
    }
}