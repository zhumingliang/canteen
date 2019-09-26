<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class SupplierT extends Model
{
    public static function companySuppliers($company_id, $page, $size)
    {
        $orderings = self::where('c_id', $company_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->field('id,name')
            ->paginate($size, false, ['page' => $page])->toArray();
        return $orderings;
    }
}