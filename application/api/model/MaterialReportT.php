<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class MaterialReportT extends Model
{
    public function canteen()
    {
        return $this->belongsTo('CanteenT', 'canteen_id', 'id');
    }


    public static function reports($timeBegin, $timeEnd, $companyId, $canteenId, $page, $size)
    {

        $list = self::  where(function ($query) use ($companyId, $canteenId) {
            if (!$canteenId) {
                $query->where('canteen_id', $canteenId);
            } else {
                $query->where('company_id', $companyId);
            }
        })
            ->whereBetweenTime('create_time', $timeBegin, addDay(1, $timeEnd))
            ->where('state', CommonEnum::STATE_IS_OK)
            ->with([
                'canteen' => function ($query) {
                    $query->field('id,name');
                }
            ])
            ->order('create_time desc')
            ->field('id,canteen_id,title,create_time')
            ->paginate($size, false, ['page' => $page]);
        return $list;
    }

    public static function exportReports($report_id)
    {
        $report = self::where('id', $report_id)
            ->with([
                'canteen' => function ($query) {
                    $query->field('id,name');
                },
                'detail'
            ])
            ->order('create_time desc')
            ->field('id,canteen_id,CONCAT(time_begin,"~",time_end,title) as title,create_time,money')
            ->find()->toArray();
        return $report;
    }


}