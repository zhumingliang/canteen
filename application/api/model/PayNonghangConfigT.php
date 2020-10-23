<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class PayNonghangConfigT extends Model
{
    public static function config($companyId)
    {
        $config = PayNonghangConfigT::where('company_id', $companyId)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->find();
        return $config;

    }
}