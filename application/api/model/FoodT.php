<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class FoodT extends BaseModel
{
    public function getImgUrlAttr($value){
        return $this->prefixImgUrl($value);
    }

    public static function foodsForOfficialManager($menu_id, $food_type, $page, $size)
    {
        $foods = self::where('m_id', $menu_id)
            ->where('f_type', $food_type)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->field('id,name,img_url,price')
            ->paginate($size, false, ['page' => $page])->toArray();
        return $foods;
    }

}