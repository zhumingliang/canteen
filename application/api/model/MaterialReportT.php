<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class MaterialReportT extends Model
{
    public static function reports($page, $size, $time_begin, $time_end, $canteen_id)
    {
        $list = self::where('canteen_id', $canteen_id)
            ->whereBetweenTime('create_time', $time_begin, $time_end)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->order('create_time desc')
            ->hidden(['state', 'update_time', 'admin_id'])
            ->paginate($size, false, ['page' => $page]);
        return $list;
    }

}