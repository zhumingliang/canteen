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
    function canteen()
    {
        return $this->belongsTo('CanteenT', 'canteen_id', 'id');
    }

    public
    function address()
    {
        return $this->belongsTo('UserAddressT', 'address_id', 'id');

    }

    public
    function user()
    {
        return $this->belongsTo('UserT', 'u_id', 'id');

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
            ->where('ordering_type', 'online')
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
                    $query->field('id,order_id,state,used,state,money,sub_money,used,order_sort,wx_confirm,sort_code,consumption_sort')
                        ->order('order_sort');
                },
                'foods' => function ($query) {
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->field('id as detail_id ,o_id,f_id as food_id,count,name,price,m_id as menu_id');
                }, 'address' => function ($query) {
                    $query->field('id,province,city,area,address,name,phone,sex');
                }

            ])
            ->field('id,fixed,address_id,create_time,booking,canteen_id,dinner_id,ordering_type,ordering_date,count,delivery_fee,type,count,remark,receive')
            ->find();
        return $order;
    }

    public static function detail($orderId)
    {

        $info = self::where('id', $orderId)
            ->with([
                'foods' => function ($query) {
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->field('id as detail_id ,o_id,f_id as food_id,count,name,price,m_id as menu_id');
                },
                'address' => function ($query) {
                    $query->field('id,province,city,area,address,name,phone,sex');
                }
            ])
            ->field('id,address_id,type as order_type,ordering_type,ordering_date,canteen_id,dinner_id,delivery_fee,booking,outsider,receive,fixed')
            ->find();

        return $info;
    }

    public
    static function infoToPrint($id)
    {
        $info = self::where('id', $id)
            ->with([
                'foods' => function ($query) {
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->field('id as detail_id ,o_id,f_id as food_id,count,name,price');
                },
                'address' => function ($query) {
                    $query->field('id,province,city,area,address,name,phone,sex');
                },
                'sub' => function ($query) {
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->field('id,order_id,consumption_sort,count,money,sub_money');
                },
                'dinner' => function ($query) {
                    $query->field('id,name');
                }
            ])
            ->field('id,address_id,company_id,outsider,dinner_id,fixed,type,count,money,sub_money,delivery_fee,create_time,ordering_date,remark,ordering_type')
            ->find();

        return $info;
    }

    public
    static function infoToPrintDetail($id)
    {
        $info = self::where('id', $id)
            ->with([
                'foods' => function ($query) {
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->field('id as detail_id ,o_id,f_id as food_id,count,name,price');
                },
                'dinner' => function ($query) {
                    $query->field('id,name');
                }
            ])
            ->field('id,address_id,canteen_id,phone,company_id,outsider,
            dinner_id,fixed,type,count,money,sub_money,delivery_fee,
            create_time,ordering_date,remark,ordering_type')
            ->find();

        return $info;
    }

    public
    static function infoToReceive($id)
    {
        $info = self::where('id', $id)
            ->with([
                'user' => function ($query) {
                    $query->field('id,openid');
                },
                'canteen' => function ($query) {
                    $query->field('id,name');
                },
                'dinner' => function ($query) {
                    $query->field('id,name');
                }
            ])
            ->field('id,u_id,dinner_id,canteen_id,ordering_date')
            ->find();
        return $info;
    }

    public
    static function infoToRefund($id)
    {
        $info = self::where('id', $id)
            ->with([
                'user' => function ($query) {
                    $query->field('id,openid');
                }
            ])
            ->field('id,u_id,pay_way,(money + sub_money + delivery_fee) as money')
            ->find();
        return $info;
    }

    public
    static function personalChoiceInfo($id)
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
            ->field('id, dinner_id, canteen_id,ordering_date,count,type,money')
            ->find();
        return $info;
    }

    public static function canteenOrders($canteenId, $consumptionDate)
    {
        return self::where('canteen_id', $canteenId)
            ->where('ordering_date', $consumptionDate)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->where('pay', 'paid')
            ->with([
                'sub' => function ($query) {
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->field('id,order_id,consumption_sort,money,sub_money,meal_money,meal_sub_money,consumption_type');
                }
            ])
            ->field('id,staff_id,fixed')
            ->select();
    }


}