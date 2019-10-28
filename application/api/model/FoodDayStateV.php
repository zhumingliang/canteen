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

class FoodDayStateV extends BaseModel
{

    public function materials(){
        return $this->hasMany('FoodMaterialT','f_id','id');
    }

    public static function foodsForOfficialPersonChoice($d_id)
    {
        $foods = self:: where('d_id', $d_id)
            ->whereTime('day', '>=', date('Y-m-d'))
            ->where('status',CommonEnum::STATE_IS_OK)
            ->where('f_type',FoodEnum::CHOICE)
            ->order('day')
            ->select();
        return $foods;
    }

    public static function foodsForOfficialMenu($d_id)
    {
        $foods = self:: where('d_id', $d_id)
            ->whereTime('day', '>=', date('Y-m-d H:i:s'))
            ->where('status',CommonEnum::STATE_IS_OK)
            ->where('f_type',FoodEnum::NO_CHOICE)
            ->with([
                'materials'=>function ($query) {
                    $query->field('id,f_id,name,count,unit');
                }
            ])
            ->order('day')
            ->select()->toArray();
        return $foods;
    }

}