<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class DownExcelT extends Model
{
    public static function excels($adminId)
    {
        return self::where('admin_id', $adminId)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->hidden(['create_time', 'update_time'])
            ->order('create_time desc')
            ->select()->toArray();
    }

}