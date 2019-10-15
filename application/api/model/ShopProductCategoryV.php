<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class ShopProductCategoryV extends Model
{
    public static function categories($c_id, $page, $size)
    {
        $orderings = self::where('c_id', $c_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->paginate($size, false, ['page' => $page])
            ->toArray();
        return $orderings;
    }
}