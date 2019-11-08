<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class MaterialReportDetailT extends Model
{

    public static function statistic($report_id)
    {
        $list = self::where('report_id', $report_id)
            ->select()->toArray();
        return $list;
    }

}