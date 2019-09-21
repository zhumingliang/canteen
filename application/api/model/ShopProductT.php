<?php


namespace app\api\model;


use app\lib\enum\ShopEnum;

class ShopProductT extends BaseModel
{
    public function getImageAttr($value)
    {
        return $this->prefixImgUrl($value);
    }

    public static function companyProducts($company_id)
    {
        $products = self::where('company_id', $company_id)
            ->where('state', ShopEnum::PRODUCT_STATE_UP)
            ->field('id,category_id,name,price,unit,image')
            ->order('category_id')
            ->select()->toArray();
        return $products;
    }

}