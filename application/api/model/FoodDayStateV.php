<?php
/**
 * Created by PhpStorm.
 * User: 明良
 * Date: 2019/9/3
 * Time: 2:02
 */

namespace app\api\model;


use app\lib\enum\CommonEnum;
use app\lib\enum\FoodEnum;
use think\Model;

class FoodDayStateV extends BaseModel
{
    public static function foodsForOfficialPersonChoice($d_id)
    {
        $foods = self:: where('d_id', $d_id)
            ->whereTime('day', '>=', date('Y-m-d H:i:s'))
            ->where('status',CommonEnum::STATE_IS_OK)
            ->where('f_type',FoodEnum::CHOICE)
            ->order('day')
            ->select()->toArray();
        return $foods;
    }

}