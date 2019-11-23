<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class PayWxConfigT extends Model
{
    public static function info($company_id)
    {
        return self::where('company_id', $company_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->find();
    }

}