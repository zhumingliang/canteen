<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class OfficialTemplateT extends Model
{
    public static function template($type)
    {
        return self::where('type',$type)
            ->where('state',CommonEnum::STATE_IS_OK)
            ->find();

    }

}