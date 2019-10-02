<?php
/**
 * Created by PhpStorm.
 * User: 明良
 * Date: 2019/9/9
 * Time: 11:30
 */

namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class OrderT extends Model
{
    public function foods()
    {
        return $this->hasMany('OrderDetailT', 'o_id', 'id');
    }

    public function address()
    {
        return $this->belongsTo('UserAddress', 'address_id', 'id');

    }

    public static function personalChoiceInfo($id)
    {
        $info = self::where('id', $id)
            ->with([
                'foods' => function ($query) {
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->field('id as detail_id ,o_id,f_id as food_id,m_id as menu_id,count');
                },
                'address' => function ($query) {
                    $query->field('id,province,city,area,address,name,phone,sex');
                }
            ])
            ->field('id,d_id as dinner_id,c_id as canteen_id,ordering_date,count,type,money')
            ->find();
        return $info;
    }

    public function orderInfo($id)
    {
        $info = self::where('id', $id)
            ->with([
                'foods' => function ($query) {
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->field('id as detail_id ,o_id,f_id as food_id,m_id as menu_id,count');
                }
            ])
            ->field('id,u_id,type,count,address_id,state')
            ->find();
        return $info;
    }

}