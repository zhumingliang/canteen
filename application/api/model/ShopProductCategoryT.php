<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class ShopProductCategoryT extends Model
{

    public static function companyCategories($company_id)
    {
        $categories = self::where('c_id', $company_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->field('id,name,null as products')
            ->select()->toArray();
        return $categories;
    }
}