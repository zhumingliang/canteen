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

    public function getConsumptionTypeAttr($value, $data)
    {
        if ($data['used'] == CommonEnum::STATE_IS_FAIL) {
            return "订餐未就餐";
        } else {
            if ($data['booking'] == CommonEnum::STATE_IS_OK) {
                return "订餐就餐";

            } else {
                return "未订餐就餐";
            }

        }
    }

    public
    function foods()
    {
        return $this->hasMany('OrderDetailT', 'o_id', 'id');
    }


    public
    function address()
    {
        return $this->belongsTo('UserAddressT', 'address_id', 'id');

    }

    public
    function dinner()
    {
        return $this->belongsTo('DinnerT', 'd_id', 'id');
    }

    public
    function canteen()
    {
        return $this->belongsTo('CanteenT', 'c_id', 'id');
    }


    public
    function user()
    {
        return $this->belongsTo('UserT', 'u_id', 'id');

    }


    /* protected function getQrcodeUrlAttr($value)
     {
         $finalUrl = config('setting.image') . $value;
         return $finalUrl;
     }*/

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
            ->field('id,d_id as dinner_id,c_id as canteen_id,ordering_date,count,type,money')
            ->find();
        return $info;
    }

    public
    static function orderInfo($id)
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

    public
    static function orderDetail($id)
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
            ->field('id,u_id,type as order_type,ordering_type,ordering_date,count,address_id,state,used,booking,
            c_id as canteen_id,d_id as dinner_id,wx_confirm,sort_code,outsider,1 as consumption_type,money,sub_money,delivery_fee,
            meal_money, meal_sub_money,no_meal_money,no_meal_sub_money,used_time')
            ->find();
        return $info;
    }

    public
    static function statisticToOfficial($canteen_id, $consumption_time, $key)
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

    public
    static function orderUsersNoUsed($dinner_id, $consumption_time)
    {

        $statistic = self::where('d_id', $dinner_id)
            ->where('ordering_date', $consumption_time)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->where('used', CommonEnum::STATE_IS_FAIL)
            ->field('id')
            ->select();
        return $statistic;
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
                }
            ])
            ->field('id,address_id,company_id,outsider,d_id,fixed,ordering_date,type,count,money,sub_money,delivery_fee,create_time,remark,ordering_type')
            ->find();

        return $info;
    }

    public
    static function infoToMachine($canteen_id, $staff_id, $dinner_id)
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

    public
    static function infoToCanteenMachine($order_id)
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

    public
    static function infoForPrinter($id)
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
            ->field('id,d_id,order_num,money,sub_money,phone,company_id,confirm_time,qrcode_url,remark,count,fixed,c_id,outsider,sort_code')
            ->find()->toArray();
        return $info;
    }

    public
    static function outsiderInfoForPrinter($id)
    {
        $info = self::where('id', $id)
            ->with([
                'foods' => function ($query) {
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->field('id as detail_id ,o_id,count,name,price');
                },
                'dinner' => function ($query) {
                    $query->field('id,name');
                },
                'address' => function ($query) {
                    $query->field('id,province,city,area,address,name,phone,sex');
                }
            ])
            ->find()->toArray();
        return $info;
    }


    public
    static function usersStatisticInfo($orderIds)
    {
        $info = self::where(function ($query) use ($orderIds) {
            if (strpos($orderIds, ',') !== false) {
                $query->whereIn('id', $orderIds);
            } else {
                $query->where('id', $orderIds);

            }
        })
            ->with([
                'foods' => function ($query) {
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->field('id as detail_id ,o_id,count,name,price');
                }
            ])
            ->field('id,count,money,sub_money,ordering_type,delivery_fee,wx_confirm,sort_code,remark')
            ->select();
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
            ->field('id,u_id,d_id,c_id,ordering_date')
            ->find();
        return $info;
    }


    public
    static function orderUsers($dinner_id, $consumption_time, $consumption_type, $page, $size)
    {
        $users = self::where('d_id', $dinner_id)
            ->where('ordering_date', $consumption_time)
            ->where(function ($query) use ($consumption_type) {
                if ($consumption_type == 'used') {
                    $query->where('booking', CommonEnum::STATE_IS_OK)
                        ->where('used', CommonEnum::STATE_IS_OK);
                } else if ($consumption_type == 'noOrdering') {
                    $query->where('booking', CommonEnum::STATE_IS_FAIL);
                } else if ($consumption_type == 'orderingNoMeal') {
                    $query->where('used', CommonEnum::STATE_IS_FAIL);
                }
            })
            ->with([
                'staff' => function ($query) {
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->field('id ,username');
                },
                'foods' => function ($query) {
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->field('id as detail_id ,o_id,count,name,price');
                }
            ])
            ->field('id,order_num,staff_id,phone,count,money,sub_money,delivery_fee,sort_code')
            ->paginate($size, false, ['page' => $page]);
        return $users;
    }

    public static function infoToStatisticDetail($orderId)
    {
        $order = self::where('id', $orderId)
            ->with([
                'dinner' => function ($query) {
                    $query->field('id,name,meal_time_end');
                }, 'foods' => function ($query) {
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->field('id as detail_id ,o_id,count,name,price');
                }
            ])
            ->field('id,create_time,ordering_type,d_id,ordering_date,state,used,count,money,sub_money,delivery_fee,type,wx_confirm,sort_code,booking')
            ->find();
        return $order;

    }


}