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

    public function address()
    {
        return $this->belongsTo('UserAddressT', 'address_id', 'id');

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
                        ->field('id as detail_id ,o_id,f_id as food_id,count,name,price');
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
            ->where('pay', CommonEnum::STATE_IS_OK)
            ->whereBetweenTime('ordering_date', $consumption_time)
            ->field('d_id,used,booking,sum(count) as count')
            ->group('d_id,used,booking')
            ->select()->toArray();
        return $statistic;
    }

    public static function orderUsersNoUsed($dinner_id, $consumption_time)
    {

        $statistic = self::where('d_id', $dinner_id)
            ->whereBetweenTime('ordering_date', $consumption_time)
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
            ->field('id,address_id,d_id,type,create_time')
            ->find();

        return $info;
    }


}