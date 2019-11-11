<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use app\lib\enum\ShopEnum;

class ShopProductT extends BaseModel
{
    public function getImageAttr($value)
    {
        return $this->prefixImgUrl($value);
    }

    public function purchase()
    {
        return $this->hasMany('ShopProductStockT', 'product_id', 'id');

    }

    public function sale()
    {
        return $this->hasMany('ShopProductStatisticV', 'product_id', 'id');

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

    public static function supplierProducts($page, $size, $time_begin, $time_end, $supplier_id)
    {

        $time_end = addDay(1, $time_end);
        $products = self::where('supplier_id', $supplier_id)
            ->field('id,name,price,unit')
            ->withSum(['purchase' => function ($query) use ($time_begin, $time_end) {
                $query->whereBetweenTime('create_time', $time_begin, $time_end)
                    ->where('state', CommonEnum::STATE_IS_OK);

            }], 'count')
            ->withSum(['sale' => function ($query) use ($time_begin, $time_end) {
                $query->whereBetweenTime('create_time', $time_begin, $time_end);

            }], 'count')
            ->paginate($size, false, ['page' => $page])->toArray();
        return $products;
    }

}