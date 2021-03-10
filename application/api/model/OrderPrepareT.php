<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class OrderPrepareT extends Model
{
    public function foods()
    {
        return $this->hasMany('OrderPrepareFoodT', 'prepare_order_id', 'prepare_order_id');
    }

    public function sub()
    {
        return $this->hasMany('OrderPrepareSubT', 'order_id', 'id');

    }

    public static function orders($prepareId)
    {
        return self::where('prepare_id', $prepareId)
            ->with(['foods' => function ($query) {
                $query->field('prepare_order_id,name,price,count');
            },
                'sub' => function ($query) {
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->field('id,order_id,sort_code,money,sub_money,count');

                }])
            ->field('id,outsider,fixed,consumption_type,prepare_order_id,type,ordering_date,dinner,money,sub_money,delivery_fee')
            ->select();

    }

    public static function order($id)
    {
        return self::where('id', $id)
            ->with(['foods' => function ($query) {
                $query->field('prepare_order_id,name,price,count');
            },
                'sub' => function ($query) {
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->field('id,order_id,sort_code,money,sub_money,count');

                }])
            ->field('id,fixed,outsider,consumption_type,prepare_order_id,type,ordering_date,dinner,money,sub_money,delivery_fee,count')
            ->find();

    }

    public static function ordersMoney($prepareId)
    {
        $money = self::where('prepare_id', $prepareId)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->field('sum(money+sub_money) as money')
            ->find();
        return $money->money;
    }


    public static function ordersForSubmit($prepareId)
    {
        return self::where('prepare_id', $prepareId)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->with(['foods' => function ($query) {
                $query->field('prepare_order_id,name,price,count');
            },
                'sub' => function ($query) {
                    $query->where('state', CommonEnum::STATE_IS_OK);

                }])
            ->hidden(['id', 'create_time', 'update_time'])
            ->select();

    }

}