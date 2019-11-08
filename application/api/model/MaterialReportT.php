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

    public static function reports($page, $size, $time_begin, $time_end, $canteen_id)
    {
        $list = self::where('canteen_id', $canteen_id)
            ->whereBetweenTime('create_time', $time_begin, $time_end)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->with([
                'canteen'=>function($query){
                    $query->field('id,name');
                }
            ])
            ->order('create_time desc')
            ->field('id,canteen_id,CONCAT(time_begin,"~",time_end,title) as title,create_time')
            ->paginate($size, false, ['page' => $page]);
        return $list;
    }

}