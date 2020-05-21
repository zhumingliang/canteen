<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class PrinterT extends Model
{

    public static function getPrinter($canteenID, $outsider)
    {
        return self::where('canteen_id', $canteenID)
            ->where('out', $outsider)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->find();
    }
}