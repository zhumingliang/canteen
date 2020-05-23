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

    public static function printers($page, $size, $canteenId)
    {
        $printers = self::where('canteen_id', $canteenId)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->hidden(['create_time','update_time','stae'])
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page])->toArray();
        return $printers;
    }
}