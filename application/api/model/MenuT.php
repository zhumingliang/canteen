<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class MenuT extends Model
{

    public static function dinnerMenus($dinner_id){
        $menus=self::where('d_id',$dinner_id)
            ->where('state',CommonEnum::STATE_IS_OK)
            ->field('id,category,status,count')
            ->select();
        return $menus;
    }
    public static function dinnerMenusCategory($dinner_id){
        $menus=self::where('d_id',$dinner_id)
            ->where('state',CommonEnum::STATE_IS_OK)
            ->field('id,category as name')
            ->select();
        return $menus;
    }

}