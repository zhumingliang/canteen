<?php


namespace app\api\model;


use think\Model;

class MaterialReportDetailV extends Model
{
    public static function orderRecords($page, $size, $report_id)
    {
        $list = self::where('report_id', $report_id)
            ->field('id,ordering_date，material,dinner_id,dinner,order_count,update_count as material_count,update_price as material_price')
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page]);
        return $list;

    }
}