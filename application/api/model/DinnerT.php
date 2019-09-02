<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class DinnerT extends Model
{
    public function menus()
    {

        return $this->hasMany('MenuT', 'd_id', 'id');

    }

    public static function dinners($c_id)
    {
        $info = self::where('c_id', $c_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->hidden(['update_time', 'state'])
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
            ->field('id,name')
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

}