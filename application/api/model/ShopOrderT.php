<?php


namespace app\api\model;


use think\Model;

class ShopOrderT extends Model
{
    public function foods()
    {
        return $this->hasMany('ShopOrderDetailT', 'o_id', 'id');
    }

    public function address()
    {
        return $this->belongsTo('UserAddress', 'address_id', 'id');

    }


    public static function orderInfo($id)
    {
        $order = self::where('id', $id)
            ->with([
                'foods' => function ($query) {
                    $query->where('state', 1)->field('id as  detail_id,o_id,product_id,name,unit,price,count');
                },
                'address' => function ($query) {
                    $query->field('id,province,city,area,address,name,phone,sex');
                }
            ])
            ->field('id,u_id,count,address_id,state')
            ->find();
        return $order;
    }

}