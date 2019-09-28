<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class ShopProductCommentT extends Model
{
    public static function productComments($product_id, $page, $size)
    {
        $comments = self::where('product_id', $product_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->paginate($size, false, ['page' => $page]);
        return $comments;

    }

}