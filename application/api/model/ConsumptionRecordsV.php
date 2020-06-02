<?php


namespace app\api\model;


use app\api\service\LogService;
use app\lib\Date;
use think\Model;
use function GuzzleHttp\Psr7\str;

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
        $consumption_time = strtotime($consumption_time);
        $consumption_time = Date::mFristAndLast(date('Y', $consumption_time), date('m', $consumption_time));
        $time_begin = $consumption_time['fist'];
        $time_end = $consumption_time['last'];
        $records = self::where('u_id', $u_id)
            ->where('ordering_date', '>=', $time_begin)
            ->where('ordering_date', '<=', $time_end)
            ->hidden(['u_id', 'location_id', 'dinner_id'])
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page]);
        return $records;
    }

    public static function recordsByPhone($phone, $consumption_time, $page, $size)
    {
        $consumption_time = strtotime($consumption_time);
        $consumption_time = Date::mFristAndLast(date('Y', $consumption_time), date('m', $consumption_time));
        $time_begin = $consumption_time['fist'];
        $time_end = $consumption_time['last'];
        $records = self::where('phone', $phone)
            ->where('ordering_date', '>=', $time_begin)
            ->where('ordering_date', '<=', $time_end)
            ->hidden(['u_id', 'location_id', 'dinner_id'])
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page]);
        return $records;
    }

    public static function monthConsumptionMoney($u_id, $consumption_time)
    {
        $consumption_time = strtotime($consumption_time);
        $consumption_time = Date::mFristAndLast(date('Y', $consumption_time), date('m', $consumption_time));
        $time_begin = $consumption_time['fist'];
        $time_end = $consumption_time['last'];
        $money = self::where('u_id', $u_id)
            ->whereIn('order_type', 'canteen,shop')
            ->where('ordering_date', '>=', $time_begin)
            ->where('ordering_date', '<=', $time_end)
            ->sum('money');
        return 0 - $money;

    }

    public static function monthConsumptionMoneyByPhone($phone, $consumption_time)
    {
        $consumption_time = strtotime($consumption_time);
        $consumption_time = Date::mFristAndLast(date('Y', $consumption_time), date('m', $consumption_time));
        $time_begin = $consumption_time['fist'];
        $time_end = $consumption_time['last'];
        $money = self::where('phone', $phone)
            ->whereIn('order_type', 'canteen,shop')
            ->whereBetweenTime('create_time', $time_begin, $time_end)
            ->sum('money');
        return 0 - $money;

    }

}