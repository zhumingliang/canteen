<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class DinnerT extends Model
{

    public function getMealTimeBeginAttr($value)
    {
        return date('H:i', strtotime($value));
    }
    public function getMealTimeEndAttr($value)
    {
        return date('H:i', strtotime($value));
    }

    public function menus()
    {

        return $this->hasMany('MenuT', 'd_id', 'id');

    }

    public static function dinners($c_id)
    {
        $info = self::where('c_id', $c_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->hidden(['update_time', 'state'])
            ->order('create_time')
            ->select();
        return $info;
    }

    public static function dinnerNames($c_id)
    {
        $info = self::where('c_id', $c_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->field('id,name')
            ->order('create_time')
            ->select();
        return $info;
    }

    public static function canteenDinnerMenus($canteen_id)
    {
        $menus = self::where('c_id', $canteen_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->with([
                'menus' => function ($query) {
                    $query->where('state', '=', CommonEnum::STATE_IS_OK)
                        ->field('id,d_id,category,status,count');
                }
            ])
            ->field('id,name,fixed,type,type_number,limit_time')
            ->select();

        return $menus;
    }

    public static function dinnerMenusForFoodManager($canteen_id)
    {
        $menus = self::where('c_id', $canteen_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->with([
                'menus' => function ($query) {
                    $query->where('state', '=', CommonEnum::STATE_IS_OK)
                        ->field('id,d_id,category,status,count');
                }
            ])
            ->field('id,name')
            ->select();

        return $menus;
    }

    public static function dinnerInfo($dinner_id)
    {
        $dinner = self::where('id', $dinner_id)
            ->find();
        return $dinner;
    }

}