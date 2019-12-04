<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class FoodV extends BaseModel
{
    public function getImgUrlAttr($value)
    {

        return $this->prefixImgUrl($value);
    }

    public function material()
    {
        return $this->hasMany('FoodMaterialT', 'f_id', 'id');
    }

    public static function foods($page, $size, $f_type, $selectField, $selectValue)
    {
        $list = self::where('f_type', $f_type)
            ->where(function ($query) use ($selectField, $selectValue) {
                if (strpos($selectValue, ',') !== false) {
                    $query->whereIn($selectField, $selectValue);
                } else {
                    $query->where($selectField, $selectValue);
                }
            })
            ->where('state', CommonEnum::STATE_IS_OK)
            ->order('create_time desc')
            ->hidden(['menu_id', 'dinner_id', 'canteen_id', 'company_id', 'f_type', 'create_time'])
            ->paginate($size, false, ['page' => $page]);
        return $list;
    }

    public static function foodInfo($id)
    {
        $info = self::where('id', $id)
            ->with([
                'material' => function ($query) {
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->field('id,f_id,name,count');
                }
            ])
            ->hidden([ 'company_id', 'f_type', 'create_time'])
            ->find();
        return $info;
    }

    public static function foodMaterials($page, $size, $selectField, $selectValue)
    {

        $list = self::where('f_type', 2)
            ->where(function ($query) use ($selectField, $selectValue) {
                if (strpos($selectValue, ',') !== false) {
                    $query->whereIn($selectField, $selectValue);
                } else {
                    $query->where($selectField, $selectValue);
                }
            })
            ->with([
                'material' => function ($query) {
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->field('id,f_id,name,count,unit');
                }
            ])
            ->where('state', CommonEnum::STATE_IS_OK)
            ->order('create_time desc')
            ->field('id,company,canteen,dinner,name')
            ->paginate($size, false, ['page' => $page]);
        return $list;
    }

}