<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Db;
use think\Model;

class MaterialReportDetailT extends Model
{
    public static function getSql()
    {
        $subQuery = Db::table('canteen_material_report_detail_t')
            ->alias('a')
            ->leftJoin('canteen_dinner_t b', 'a.dinner_id=b.id')
            ->leftJoin('canteen_canteen_t c', 'a.canteen_id=c.id')
            ->field('a.*,b.name as dinner,c.name as canteen')
            ->buildSql();
        return $subQuery;
    }

    public static function statistic($report_id)
    {
        $list = self::where('report_id', $report_id)
            ->select()->toArray();
        return $list;
    }

    public static function orderRecords($page, $size, $report_id)
    {
        $list = self::where('report_id', $report_id)
            ->field('id,ordering_date,material,dinner_id,dinner,order_count,update_count as material_count,update_price as material_price')
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page]);
        return $list;

    }

    public static function info($id)
    {
        $sql = self::getSql();
        return Db::table($sql . ' a')
            ->where('report_id', $id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->field('id,DATE_FORMAT(create_time, "%Y-%m-%d %H:%i") as create_time,dinner,canteen,material,order_count,count,price')
            ->select()
            ->toArray();

    }

}