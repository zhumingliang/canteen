<?php


namespace app\api\model;


class ShopProductStockV extends BaseModel
{

    public function getImageAttr($value)
    {
        return $this->prefixImgUrl($value);
    }

    public static function supplierProducts($supplier_id, $category_id, $page, $size)
    {
        $list = self::where('supplier_id', $supplier_id)
            ->where(function ($query) use ($category_id) {
                if (!empty($category_id)) {
                    $query->where('category_id', $category_id);
                }
            })
            ->field('product_id,image,name,category,unit,price,sum(count) as stock,supplier')
            ->order('create_time desc')
            ->group('product_id')
            ->paginate($size, false, ['page' => $page]);
        return $list;
    }

    public static function cmsProducts($company_id, $supplier_id, $category_id, $page, $size)
    {
        $list = self::where('company_id', $company_id)
            ->where(function ($query) use ($supplier_id) {
                if (!empty($supplier_id)) {
                    $query->where('supplier_id', $supplier_id);
                }
            })
            ->where(function ($query) use ($category_id) {
                if (!empty($category_id)) {
                    $query->where('category_id', $category_id);
                }
            })
            ->field('product_id,image,name,category,unit,price,sum(count) as stock,supplier,state')
            ->order('create_time desc')
            ->group('product_id')
            ->paginate($size, false, ['page' => $page]);
        return $list;
    }

}