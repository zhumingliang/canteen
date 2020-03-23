<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use app\lib\enum\PayEnum;
use think\Model;

class ShopOrderingV extends Model
{

    public static function userOrderings($u_id, $company_id, $page, $size)
    {
        $orderings = self::where('u_id', $u_id)
            ->where(function ($query) use ($company_id) {
                if (!empty($company_id)) {
                    $query->where('company_id', $company_id);
                }
            })
            ->where('state', CommonEnum::STATE_IS_OK)
            ->where('pay', PayEnum::PAY_SUCCESS)
            ->field('id,company as address,"小卖部" as type,create_time,dinner,money')
            ->paginate($size, false, ['page' => $page]);
        return $orderings;
    }
}