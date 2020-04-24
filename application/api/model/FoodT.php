<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;

class FoodT extends BaseModel
{
    public function getImgUrlAttr($value)
    {
        return $this->prefixImgUrl($value);
    }

    public function menu()
    {
        return $this->belongsTo('MenuT', 'm_id', 'id');
    }

    public function comments()
    {
        return $this->hasMany('FoodCommentT', 'f_id', 'id');
    }

    public static function foodsForOfficialManager($menu_id, $food_type, $page, $size)
    {
        $foods = self::where('m_id', $menu_id)
            ->where('f_type', $food_type)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->field('id,name,img_url,price,external_price')
            ->paginate($size, false, ['page' => $page])->toArray();
        return $foods;
    }

    public static function infoForComment($food_id)
    {
        $info = self::where('id', $food_id)
            ->with([
                'comments'=> function ($query) {
                    $query->field('id,u_id,f_id,taste,service,remark')
                        ->order('create_time desc')
                        ->limit(0,3);
                },
            ])
            ->field('id,name,price,external_price,img_url,chef')
            ->find();
        return $info;
    }

}