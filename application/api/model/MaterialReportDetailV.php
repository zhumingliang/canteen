<?php


namespace app\api\model;


use think\Model;

class MaterialReportDetailV extends Model
{
    public static function orderRecords($report_id)
    {
        $list = self::where('report_id', $report_id)
            ->select()
            ->toArray();
        return $list;

    }
}