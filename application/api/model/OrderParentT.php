<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;
use think\Request;

class OrderParentT extends Model
{
    public
    function foods()
    {
        return $this->hasMany('SubFoodT', 'o_id', 'id');
    }


    public
    function address()
    {
        return $this->belongsTo('UserAddressT', 'address_id', 'id');

    }


    public
    function dinner()
    {
        return $this->belongsTo('DinnerT', 'dinner_id', 'id');
    }

    public function sub()
    {
        return $this->hasMany('OrderSubT', 'order_id', 'id');

    }

    public static function orderInfo($ordering_date, $canteen_id, $dinner_id, $phone)
    {
        $order = self::where('phone', $phone)
            ->where('ordering_date', $ordering_date)
            ->where('canteen_id', $canteen_id)
            ->where('dinner_id', $dinner_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->find();
        return $order;


    }

    public static function infoToStatisticDetail($orderId)
    {
        $order = self::where('id', $orderId)
            ->with([
                'dinner' => function ($query) {
                    $query->field('id,name,meal_time_end');
                },
                'sub' => function ($query) {
                    $query->field('id,order_id,state,used,state,money,sub_money,used,order_sort')->order('order_sort');
                }
            ])
            ->field('id,dinner_id,ordering_date,count,delivery_fee,type')
            ->find();
        return $order;
    }

    public static function detail($orderId)
    {

        $info = self::where('id', $orderId)
            ->with([
                'foods' => function ($query) {
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->field('id as detail_id ,o_id,f_id as food_id,count,name,price');
                },
                'address' => function ($query) {
                    $query->field('id,province,city,area,address,name,phone,sex');
                }
            ])
            ->field('id,address_id,type as order_type,ordering_type,ordering_date,canteen_id,dinner_id,delivery_fee,booking,outsider')
            ->find();

        return $info;
    }

}