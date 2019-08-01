<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class DepartmentV extends Model
{
    public static function departments($c_id)
    {
        $departments = self::where('c_id', $c_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->field('id,parent_id,name,count')
            ->select()->toArray();
        return $departments;
    }

}