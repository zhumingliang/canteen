<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class MaterialPriceV extends Model
{
    public static function materials($page, $size, $key, $selectField, $selectValue)
    {

        $list = self::where('state', CommonEnum::STATE_IS_OK)
            ->where(function ($query) use ($selectField, $selectValue) {
                if (strpos($selectValue, ',') !== false) {
                    $query->whereIn($selectField, $selectValue);
                } else {
                    $query->where($selectField, $selectValue);
                }
            })
            ->where(function ($query) use ($key) {
                if (!empty($key)) {
                    $query->where('name', 'like', '%' . $key . '%');
                }
            })
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page]);
        return $list;
    }

    public static function exportMaterials($key, $selectField, $selectValue)
    {

        $list = self::where(function ($query) use ($selectField, $selectValue) {
                if (strpos($selectValue, ',') !== false) {
                    $query->whereIn($selectField, $selectValue);
                } else {
                    $query->where($selectField, $selectValue);
                }
            })
            ->where('state',CommonEnum::STATE_IS_OK)
            ->where(function ($query) use ($key) {
                if (!empty($key)) {
                    $query->where('name', 'like', '%' . $key . '%');
                }
            })
            ->field('id,company,canteen,name,unit,price')
            ->order('create_time desc')
            ->select()->toArray();
        return $list;
    }

}

