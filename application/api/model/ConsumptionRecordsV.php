<?php


namespace app\api\model;


use app\api\service\LogService;
use think\Model;

class ConsumptionRecordsV extends Model
{

    public function getUsedTypeAttr($value)
    {
        $data = [
            'shop' => '小卖部', 'inside' => '就餐', 'outside' => '外卖', 'cash' => '现金充值', 'weixin' => '微信充值'
        ];
        return $data[$value];
    }

    public static function records($u_id, $consumption_time, $page, $size)
    {
        $time_begin = date('Y-m-d H:i:s', strtotime($consumption_time));
        $time_end = date('Y-m-d H:i:s', strtotime("+1 month", strtotime($consumption_time)));
        $records = self::where('u_id', $u_id)
            ->whereBetweenTime('create_time', $time_begin, $time_end)
            ->hidden(['u_id', 'location_id', 'dinner_id'])
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page]);
        return $records;
    }

    public static function recordsByPhone($phone, $consumption_time, $page, $size)
    {
        $time_begin = date('Y-m-d', strtotime($consumption_time));
        $time_end = date('Y-m-d', strtotime("+1 month", strtotime($consumption_time)));

        $records = self::where('phone', $phone)
            ->whereBetweenTime('create_time', $time_begin, $time_end)
            ->hidden(['u_id', 'location_id', 'dinner_id'])
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page]);
        return $records;
    }

    public static function monthConsumptionMoney($u_id, $consumption_time)
    {
        $time_begin = date('Y-m-d H:i:s', strtotime($consumption_time));
        $time_end = date('Y-m-d H:i:s', strtotime("+1 month", strtotime($consumption_time)));
        $money = self::where('u_id', $u_id)
            ->whereIn('order_type', 'canteen,shop')
            ->whereBetweenTime('create_time', $time_begin, $time_end)
            ->sum('money');
        return 0 - $money;

    }

    public static function monthConsumptionMoneyByPhone($phone, $consumption_time)
    {
        $time_begin = date('Y-m-d H:i:s', strtotime($consumption_time));
        $time_end = date('Y-m-d H:i:s', strtotime("+1 month", strtotime($consumption_time)));
        $money = self::where('phone', $phone)
            ->whereIn('order_type', 'canteen,shop')
            ->whereBetweenTime('create_time', $time_begin, $time_end)
            ->sum('money');
        return 0 - $money;

    }

}