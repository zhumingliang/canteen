<?php
/**
 * Created by PhpStorm.
 * User: 明良
 * Date: 2019/9/9
 * Time: 11:30
 */

namespace app\api\model;


use app\lib\enum\CommonEnum;
use app\lib\enum\OrderEnum;
use app\lib\enum\PayEnum;
use think\Model;

class OrderT extends Model
{
    public function foods()
    {
        return $this->hasMany('OrderDetailT', 'o_id', 'id');
    }

    public function dinner()
    {
        return $this->belongsTo('DinnerT', 'd_id', 'id');
    }

    public function address()
    {
        return $this->belongsTo('UserAddressT', 'address_id', 'id');

    }

    protected function getQrcodeUrlAttr($value)
    {
        $finalUrl = config('setting.image') . $value;
        return $finalUrl;
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

    public static function orderInfo($id)
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

    public static function orderDetail($id)
    {
        $info = self::where('id', $id)
            ->with([
                'foods' => function ($query) {
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->field('id as detail_id ,o_id,f_id as food_id,count,name,price,m_id as menu_id');
                },
                'address' => function ($query) {
                    $query->field('id,province,city,area,address,name,phone,sex');
                }
            ])
            ->field('id,u_id,type as order_type,ordering_type,ordering_date,count,address_id,state,used,
            c_id as canteen_id,d_id as dinner_id')
            ->find();
        return $info;
    }

    public static function statisticToOfficial($canteen_id, $consumption_time)
    {
        $statistic = self::where('c_id', $canteen_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->where('pay', PayEnum::PAY_SUCCESS)
            ->where('ordering_date', $consumption_time)
            ->field('d_id,used,booking,sum(count) as count')
            ->group('d_id,used,booking')
            ->select()->toArray();
        return $statistic;
    }

    public static function orderUsersNoUsed($dinner_id, $consumption_time)
    {

        $statistic = self::where('d_id', $dinner_id)
            ->where('ordering_date', $consumption_time)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->where('used', CommonEnum::STATE_IS_FAIL)
            ->field('id')
            ->select();
        return $statistic;
    }

    public static function infoToPrint($id)
    {
        $info = self::where('id', $id)
            ->with([
                'foods' => function ($query) {
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->field('id as detail_id ,o_id,f_id as food_id,count,name,price');
                },
                'address' => function ($query) {
                    $query->field('id,province,city,area,address,name,phone,sex');
                }
            ])
            ->field('id,address_id,d_id,type,money,sub_money,delivery_fee,create_time')
            ->find();

        return $info;
    }

    public static function infoToMachine($canteen_id, $staff_id, $dinner_id)
    {
        $info = self::where('c_id', $canteen_id)
            ->where('staff_id', $staff_id)
            ->where('d_id', $dinner_id)
            ->whereTime('ordering_date', 'd')
            ->where('used', CommonEnum::STATE_IS_FAIL)
            ->with([
                'foods' => function ($query) {
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->field('id as detail_id ,o_id,f_id as food_id,count,name,price');
                }
            ])
            ->field('id,d_id,type,pay_way,money,sub_money,(money+sub_money) as all_money ,consumption_type,meal_sub_money,meal_money')
            ->find();

        return $info;
    }

    public static function infoToCanteenMachine($order_id)
    {
        $info = self::where('id', $order_id)
            ->with([
                'foods' => function ($query) {
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->field('id as detail_id ,o_id,f_id as food_id,count,name,price');
                }
            ])
            ->field('id,d_id,type,pay_way,money,sub_money,(money+sub_money) as all_money ,consumption_type,meal_sub_money,meal_money')
            ->find()->toArray();
        return $info;
    }

    public static function infoForPrinter($id)
    {
        $info = self::where('id', $id)
            ->with([
                'foods' => function ($query) {
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->field('id as detail_id ,o_id,count,name,price');
                },
                'dinner' => function ($query) {
                    $query->field('id,name');
                }
            ])
            ->field('id,d_id,money,sub_money,phone,outsider,company_id,confirm_time,qrcode_url,remark,count')
            ->find()->toArray();
        return $info;
    }

}