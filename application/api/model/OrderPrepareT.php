<?php


namespace app\api\model;


use think\Model;

class OrderPrepareT extends Model
{
    public function foods()
    {
        return $this->hasMany('OrderPrepareFoodT', 'prepare_order_id', 'prepare_order_id');
    }

    public static function orders($prepareId)
    {
        return self::where('prepare_id', $prepareId)
            ->with(['foods' => function ($query) {
                $query->field('prepare_order_id,name,price,count');
            }])
            ->field('id,prepare_order_id,type,ordering_date,dinner,money,sub_money,delivery_fee')
            ->select();

    }

}