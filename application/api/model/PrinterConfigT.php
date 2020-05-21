<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class PrinterConfigT extends Model
{
    public static function getPrinterConfig()
    {

        return self::where('state', CommonEnum::STATE_IS_OK)
            ->find();
    }

}