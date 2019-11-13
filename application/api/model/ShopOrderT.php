<?php


namespace app\api\model;


use think\Model;

class ShopOrderT extends Model
{
    public function foods()
    {
        return $this->hasMany('ShopOrderDetailT', 'o_id', 'id');
    }

    public function products()
    {
        return $this->hasMany('ShopOrderDetailV', 'order_id', 'id');
    }

    public function address()
    {
        return $this->belongsTo('UserAddressT', 'address_id', 'id');

    }


    public static function orderInfo($id)
    {
        $order = self::where('id', $id)
            ->with([
                'foods' => function ($query) {
                    $query->where('state', 1)
                        ->field('id as  detail_id,o_id,product_id as food_id,name,unit,price,count');
                },
                'address' => function ($query) {
                    $query->field('id,province,city,area,address,name,phone,sex');
                }
            ])
            ->field('id,distribution as order_type,u_id,count,"shop" as ordering_type,address_id,state,used')
            ->find();
        return $order;
    }

    public static function orderInfoForStatistic($id)
    {
        $order = self::where('id', $id)
            ->with([
                'products',
                'address' => function ($query) {
                    $query->field('id,province,city,area,address,name,phone,sex');
                }
            ])
            ->field('id,distribution as order_type,u_id,count,"shop" as ordering_type,address_id,state,used')
            ->find();
        return $order;
    }

}