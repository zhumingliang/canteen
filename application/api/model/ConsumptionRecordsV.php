<?php


namespace app\api\model;


use app\api\service\LogService;
use app\lib\Date;
use app\lib\enum\CommonEnum;
use app\lib\enum\OrderEnum;
use app\lib\enum\PayEnum;
use think\Db;
use think\Model;
use function GuzzleHttp\Psr7\str;

class ConsumptionRecordsV extends Model
{

    /*public function getUsedTypeAttr($value)
    {
        $data = [
            'shop' => '小卖部', 'inside' => '就餐',
            'outside' => '外卖', 'cash' => '现金充值',
            'weixin' => '微信充值','recharge'=>"系统补充 ",
            'deduction'=>'系统补扣'
        ];
        return $data[$value];
    }*/

    public function getBalanceAttr($value)
    {
        return round($value, 2);
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

    public static function recordsByPhone($phone, $company_id, $consumption_time, $page, $size)
    {
        $consumption_time = strtotime($consumption_time);
        $consumption_time = Date::mFristAndLast(date('Y', $consumption_time), date('m', $consumption_time));
        $time_begin = $consumption_time['fist'];
        $time_end = $consumption_time['last'];
        $subQuery = Db::table('canteen_order_t')
            ->alias('a')
            ->field("a.id as order_id,a.c_id as location_id,c.name as location,'canteen' as order_type,a.create_time,a.ordering_date,b.name as dinner,
            (0-a.money-a.sub_money-a.delivery_fee) as money, a.phone,a.count,a.sub_money,a.delivery_fee,a.booking,a.used,a.type as eating_type, 'one' as consumption_type,a.company_id,a.sort_code")
            ->leftJoin('canteen_dinner_t b', 'a.d_id = b.id')
            ->leftJoin('canteen_canteen_t c', 'a.c_id = c.id')
            ->where('a.phone', $phone)
            ->where('a.company_id', $company_id)
            ->where('a.ordering_date', ">=", $time_begin)
            ->where('a.ordering_date', "<=", $time_end)
            ->where('a.state', CommonEnum::STATE_IS_OK)
            ->where('a.pay', PayEnum::PAY_SUCCESS)
            ->unionAll(function ($query) use ($phone, $company_id, $time_begin, $time_end) {
                $query->table("canteen_order_parent_t")
                    ->alias('a')
                    ->field("a.id as order_id,a.canteen_id as location_id,c.name as location,'canteen' as order_type,a.create_time,a.ordering_date,b.name as dinner,(0-a.money-a.sub_money-a.delivery_fee) as money, a.phone,a.count,a.sub_money,a.delivery_fee,a.booking,a.used,a.type as eating_type,'more' as consumption_type,a.company_id,0 as sort_code")
                    ->leftJoin('canteen_dinner_t b', 'a.dinner_id = b.id')
                    ->leftJoin('canteen_canteen_t c', 'a.canteen_id = c.id')
                    ->where('a.phone', $phone)
                    ->where('a.company_id', $company_id)
                    ->where('a.ordering_date', ">=", $time_begin)
                    ->where('a.ordering_date', "<=", $time_end)
                    ->where('a.type', OrderEnum::EAT_OUTSIDER)
                    ->where('a.state', CommonEnum::STATE_IS_OK)
                    ->where('a.pay', PayEnum::PAY_SUCCESS);
            })
            ->unionAll(function ($query) use ($phone, $company_id, $time_begin, $time_end) {
                $query->table("canteen_order_sub_t")
                    ->alias('a')
                    ->field("a.id as order_id,d.canteen_id as location_id,c.name as location,'canteen' as order_type,a.create_time,
                    a.ordering_date,b.name as dinner,(0-a.money-a.sub_money) as money, 
                    d.phone,a.count,a.sub_money,d.delivery_fee,d.booking,a.used,
                    d.type as eating_type,'more' as consumption_type,d.company_id, a.sort_code")
                    ->leftJoin('canteen_order_parent_t d', 'a.order_id = d.id')
                    ->leftJoin('canteen_dinner_t b', 'd.dinner_id = b.id')
                    ->leftJoin('canteen_canteen_t c', 'd.canteen_id = c.id')
                    ->where('d.phone', $phone)
                    ->where('d.company_id', $company_id)
                    ->where('a.ordering_date', ">=", $time_begin)
                    ->where('a.ordering_date', "<=", $time_end)
                    ->where('d.type', OrderEnum::EAT_CANTEEN)
                    ->where('a.state', CommonEnum::STATE_IS_OK)
                    ->where('d.pay', PayEnum::PAY_SUCCESS);
            })
            ->unionAll(function ($query) use ($phone, $company_id, $time_begin, $time_end) {
                $query->table("canteen_shop_order_t")
                    ->alias('a')
                    ->leftJoin('canteen_shop_t b', 'a.shop_id = b.id')
                    ->field("a.id as order_id,a.shop_id as location_id,b.name as location,'shop' as order_type,a.create_time,date_format(a.create_time, '%Y-%m-%d' ) AS ordering_date,'小卖部' AS dinner,( 0-a.money ) AS money,a.phone,a.count,0 AS sub_money,0 AS delivery_fee,1 AS booking,1 AS used,1 AS eating_type,'one' AS consumption_type,a.company_id,0 AS sort_code")
                    ->where('a.phone', $phone)
                    ->where('a.company_id', $company_id)
                    ->where('a.create_time', ">=", $time_begin)
                    ->where('a.create_time', "<=", $time_end)
                    ->where('a.state', CommonEnum::STATE_IS_OK);
            })
            ->unionAll(function ($query) use ($phone, $company_id, $time_begin, $time_end) {
                $query->table("canteen_recharge_supplement_t")
                    ->alias('a')
                    ->leftJoin('canteen_company_t b', 'a.company_id = b.id')
                    ->leftJoin('canteen_dinner_t c', 'a.dinner_id = c.id')
                    ->leftJoin('canteen_company_staff_t d', 'a.staff_id = d.id')
                    ->leftJoin('canteen_canteen_t e', 'a.canteen_id = e.id')
                    ->field("a.id as order_id,a.canteen_id as location_id,e.name as location,'recharge' as order_type,a.create_time,a.consumption_date AS ordering_date,c.name AS dinner,a.money AS money,d.phone,1 as count,0 AS sub_money,0 AS delivery_fee,1 AS booking,1 AS used,1 AS eating_type,'one' AS consumption_type,a.company_id,0 AS sort_code")
                    ->where('a.phone', $phone)
                    ->where('a.company_id', $company_id)
                    ->where('a.consumption_date', ">=", $time_begin)
                    ->where('a.consumption_date', "<=", $time_end);
            })
            ->buildSql();

        $records = Db::table($subQuery . ' a')
            ->order('create_time', 'desc')
            ->paginate($size, false, ['page' => $page])
            ->toArray();
        /* ->paginate($size, false, ['page' => $page])
           ->toArray();*/


        return $records;
    }

    public static function recordsByPhoneWithRecharge($phone, $company_id, $consumption_time, $page, $size)
    {
        $consumption_time = strtotime($consumption_time);
        $consumption_time = Date::mFristAndLast(date('Y', $consumption_time), date('m', $consumption_time));
        $time_begin = $consumption_time['fist'];
        $time_end = $consumption_time['last'];
        $subQuery = Db::table('canteen_order_t')
            ->alias('a')
            ->field("a.id as order_id,a.c_id as location_id,c.name as location,'canteen' as order_type,a.create_time,a.ordering_date,b.name as dinner,
            (0-a.money-a.sub_money-a.delivery_fee) as money, a.phone,a.count,a.sub_money,a.delivery_fee,a.booking,a.used,a.type as eating_type, 'one' as consumption_type,a.company_id,a.sort_code")
            ->leftJoin('canteen_dinner_t b', 'a.d_id = b.id')
            ->leftJoin('canteen_canteen_t c', 'a.c_id = c.id')
            ->where('a.phone', $phone)
            ->where('a.company_id', $company_id)
            ->where('a.ordering_date', ">=", $time_begin)
            ->where('a.ordering_date', "<=", $time_end)
            ->where('a.state', CommonEnum::STATE_IS_OK)
            ->where('a.pay', PayEnum::PAY_SUCCESS)
            ->unionAll(function ($query) use ($phone, $company_id, $time_begin, $time_end) {
                $query->table("canteen_order_parent_t")
                    ->alias('a')
                    ->field("a.id as order_id,a.canteen_id as location_id,c.name as location,'canteen' as order_type,a.create_time,a.ordering_date,b.name as dinner,(0-a.money-a.sub_money-a.delivery_fee) as money, a.phone,a.count,a.sub_money,a.delivery_fee,a.booking,a.used,a.type as eating_type,'more' as consumption_type,a.company_id,0 as sort_code")
                    ->leftJoin('canteen_dinner_t b', 'a.dinner_id = b.id')
                    ->leftJoin('canteen_canteen_t c', 'a.canteen_id = c.id')
                    ->where('a.phone', $phone)
                    ->where('a.company_id', $company_id)
                    ->where('a.ordering_date', ">=", $time_begin)
                    ->where('a.ordering_date', "<=", $time_end)
                    ->where('a.type', OrderEnum::EAT_OUTSIDER)
                    ->where('a.state', CommonEnum::STATE_IS_OK)
                    ->where('a.pay', PayEnum::PAY_SUCCESS);
            })
            ->unionAll(function ($query) use ($phone, $company_id, $time_begin, $time_end) {
                $query->table("canteen_order_sub_t")
                    ->alias('a')
                    ->field("a.id as order_id,d.canteen_id as location_id,c.name as location,'canteen' as order_type,a.create_time,
                    a.ordering_date,b.name as dinner,(0-a.money-a.sub_money) as money, 
                    d.phone,a.count,a.sub_money,d.delivery_fee,d.booking,a.used,
                    d.type as eating_type,'more' as consumption_type,d.company_id, a.sort_code")
                    ->leftJoin('canteen_order_parent_t d', 'a.order_id = d.id')
                    ->leftJoin('canteen_dinner_t b', 'd.dinner_id = b.id')
                    ->leftJoin('canteen_canteen_t c', 'd.canteen_id = c.id')
                    ->where('d.phone', $phone)
                    ->where('d.company_id', $company_id)
                    ->where('a.ordering_date', ">=", $time_begin)
                    ->where('a.ordering_date', "<=", $time_end)
                    ->where('d.type', OrderEnum::EAT_CANTEEN)
                    ->where('a.state', CommonEnum::STATE_IS_OK)
                    ->where('d.pay', PayEnum::PAY_SUCCESS);
            })
            ->unionAll(function ($query) use ($phone, $company_id, $time_begin, $time_end) {
                $query->table("canteen_shop_order_t")
                    ->alias('a')
                    ->leftJoin('canteen_shop_t b', 'a.shop_id = b.id')
                    ->field("a.id as order_id,a.shop_id as location_id,b.name as location,'shop' as order_type,a.create_time,date_format(a.create_time, '%Y-%m-%d' ) AS ordering_date,'小卖部' AS dinner,( 0-a.money ) AS money,a.phone,a.count,0 AS sub_money,0 AS delivery_fee,1 AS booking,1 AS used,1 AS eating_type,'one' AS consumption_type,a.company_id,0 AS sort_code")
                    ->where('a.phone', $phone)
                    ->where('a.company_id', $company_id)
                    ->where('a.create_time', ">=", $time_begin)
                    ->where('a.create_time', "<=", $time_end)
                    ->where('a.state', CommonEnum::STATE_IS_OK);
            })
            ->unionAll(function ($query) use ($phone, $company_id, $time_begin, $time_end) {
                $query->table("canteen_recharge_supplement_t")
                    ->alias('a')
                    ->leftJoin('canteen_company_t b', 'a.company_id = b.id')
                    ->leftJoin('canteen_dinner_t c', 'a.dinner_id = c.id')
                    ->leftJoin('canteen_company_staff_t d', 'a.staff_id = d.id')
                    ->leftJoin('canteen_canteen_t e', 'a.canteen_id = e.id')
                    ->field("a.id as order_id,a.canteen_id as location_id,e.name as location,'recharge' as order_type,a.create_time,a.consumption_date AS ordering_date,c.name AS dinner,a.money AS money,d.phone,1 as count,0 AS sub_money,0 AS delivery_fee,1 AS booking,1 AS used,1 AS eating_type,'one' AS consumption_type,a.company_id,0 AS sort_code")
                    ->where('a.phone', $phone)
                    ->where('a.company_id', $company_id)
                    ->where('a.consumption_date', ">=", $time_begin)
                    ->where('a.consumption_date', "<=", $time_end);
            })
            ->unionAll(function ($query) use ($phone, $company_id, $time_begin, $time_end) {
                $query->table("canteen_pay_t")
                    ->alias('a')
                    ->leftJoin('canteen_company_t b', 'a.company_id = b.id')
                    ->leftJoin('canteen_company_staff_t d', 'a.staff_id = d.id')
                    ->field("a.id as order_id,0 as  location_id,'' as location,'pay' as order_type,a.create_time,date_format(a.create_time, '%Y-%m-%d' ) AS ordering_date,'' AS dinner,a.money AS money,d.phone,1 as count,0 AS sub_money,0 AS delivery_fee,1 AS booking,1 AS used,1 AS eating_type,'one' AS consumption_type,a.company_id,0 AS sort_code, a.method_id as supplement_type,1 as unused_handel")
                    ->where('a.phone', $phone)
                    ->where('a.company_id', $company_id)
                    ->where('a.create_time', ">=", $time_begin)
                    ->where('a.create_time', "<=", $time_end)
                    ->where('a.status', PayEnum::PAY_SUCCESS)
                    ->where('a.refund', CommonEnum::STATE_IS_FAIL);
            })
            ->buildSql();

        $records = Db::table($subQuery . ' a')
            ->order('create_time', 'desc')
            ->paginate($size, false, ['page' => $page])
            ->toArray();
        /* ->paginate($size, false, ['page' => $page])
           ->toArray();*/


        return $records;
    }

    public static function recordsByStaffId($staffId, $consumption_time, $page, $size)
    {
        $consumption_time = strtotime($consumption_time);
        $consumption_time = Date::mFristAndLast(date('Y', $consumption_time), date('m', $consumption_time));
        $time_begin = $consumption_time['fist'];
        $time_end = $consumption_time['last'];
        $subQuery = Db::table('canteen_order_t')
            ->alias('a')
            ->field("a.id as order_id,a.c_id as location_id,c.name as location,'canteen' as order_type,a.create_time,a.ordering_date,b.name as dinner,
            (0-a.money-a.sub_money-a.delivery_fee) as money, a.phone,a.count,a.sub_money,a.delivery_fee,a.booking,a.used,a.type as eating_type, 'one' as consumption_type,a.company_id,a.sort_code ,1 as supplement_type,a.unused_handel")
            ->leftJoin('canteen_dinner_t b', 'a.d_id = b.id')
            ->leftJoin('canteen_canteen_t c', 'a.c_id = c.id')
            ->where('a.staff_id', $staffId)
            ->where('a.ordering_date', ">=", $time_begin)
            ->where('a.ordering_date', "<=", $time_end)
            ->where('a.state', CommonEnum::STATE_IS_OK)
            ->where('a.pay', PayEnum::PAY_SUCCESS)
            ->unionAll(function ($query) use ($staffId, $time_begin, $time_end) {
                $query->table("canteen_order_parent_t")
                    ->alias('a')
                    ->field("a.id as order_id,a.canteen_id as location_id,c.name as location,'canteen' as order_type,a.create_time,a.ordering_date,b.name as dinner,(0-a.money-a.sub_money-a.delivery_fee) as money, a.phone,a.count,a.sub_money,a.delivery_fee,a.booking,a.used,a.type as eating_type,'more' as consumption_type,a.company_id,0 as sort_code,1 as supplement_type,a.used as unused_handel")
                    ->leftJoin('canteen_dinner_t b', 'a.dinner_id = b.id')
                    ->leftJoin('canteen_canteen_t c', 'a.canteen_id = c.id')
                    ->where('a.staff_id', $staffId)
                    ->where('a.ordering_date', ">=", $time_begin)
                    ->where('a.ordering_date', "<=", $time_end)
                    ->where('a.type', OrderEnum::EAT_OUTSIDER)
                    ->where('a.state', CommonEnum::STATE_IS_OK)
                    ->where('a.pay', PayEnum::PAY_SUCCESS);
            })
            ->unionAll(function ($query) use ($staffId, $time_begin, $time_end) {
                $query->table("canteen_order_sub_t")
                    ->alias('a')
                    ->field("a.id as order_id,d.canteen_id as location_id,c.name as location,'canteen' as order_type,a.create_time,
                    a.ordering_date,b.name as dinner,(0-a.money-a.sub_money) as money, 
                    d.phone,a.count,a.sub_money,d.delivery_fee,d.booking,a.used,
                    d.type as eating_type,'more' as consumption_type,d.company_id, a.sort_code,1 as supplement_type,a.unused_handel")
                    ->leftJoin('canteen_order_parent_t d', 'a.order_id = d.id')
                    ->leftJoin('canteen_dinner_t b', 'd.dinner_id = b.id')
                    ->leftJoin('canteen_canteen_t c', 'd.canteen_id = c.id')
                    ->where('d.staff_id', $staffId)
                    ->where('a.ordering_date', ">=", $time_begin)
                    ->where('a.ordering_date', "<=", $time_end)
                    ->where('d.type', OrderEnum::EAT_CANTEEN)
                    ->where('a.state', CommonEnum::STATE_IS_OK)
                    ->where('d.pay', PayEnum::PAY_SUCCESS);
            })
            ->unionAll(function ($query) use ($staffId, $time_begin, $time_end) {
                $query->table("canteen_shop_order_t")
                    ->alias('a')
                    ->leftJoin('canteen_shop_t b', 'a.shop_id = b.id')
                    ->field("a.id as order_id,a.shop_id as location_id,b.name as location,'shop' as order_type,a.create_time,date_format(a.create_time, '%Y-%m-%d' ) AS ordering_date,'小卖部' AS dinner,( 0-a.money ) AS money,a.phone,a.count,0 AS sub_money,0 AS delivery_fee,1 AS booking,1 AS used,1 AS eating_type,'one' AS consumption_type,a.company_id,0 AS sort_code,1 as supplement_type,1 as unused_handel")
                    ->where('a.staff_id', $staffId)
                    ->where('a.create_time', ">=", $time_begin)
                    ->where('a.create_time', "<=", $time_end)
                    ->where('a.state', CommonEnum::STATE_IS_OK);
            })
            ->unionAll(function ($query) use ($staffId, $time_begin, $time_end) {
                $query->table("canteen_recharge_supplement_t")
                    ->alias('a')
                    ->leftJoin('canteen_company_t b', 'a.company_id = b.id')
                    ->leftJoin('canteen_dinner_t c', 'a.dinner_id = c.id')
                    ->leftJoin('canteen_company_staff_t d', 'a.staff_id = d.id')
                    ->leftJoin('canteen_canteen_t e', 'a.canteen_id = e.id')
                    ->field("a.id as order_id,a.canteen_id as location_id,e.name as location,'recharge' as order_type,a.create_time,a.consumption_date AS ordering_date,c.name AS dinner,a.money AS money,d.phone,1 as count,0 AS sub_money,0 AS delivery_fee,1 AS booking,1 AS used,1 AS eating_type,'one' AS consumption_type,a.company_id,0 AS sort_code, a.type as supplement_type,1 as unused_handel")
                    ->where('a.staff_id', $staffId)
                    ->where('a.consumption_date', ">=", $time_begin)
                    ->where('a.consumption_date', "<=", $time_end);
            })
            ->buildSql();

        $records = Db::table($subQuery . ' a')
            ->order('create_time', 'desc')
            ->paginate($size, false, ['page' => $page])
            ->toArray();
        /* ->paginate($size, false, ['page' => $page])
           ->toArray();*/


        return $records;
    }


    public static function recordsByStaffIdWithRecharge($staffId, $consumption_time, $page, $size)
    {
        $consumption_time = strtotime($consumption_time);
        $consumption_time = Date::mFristAndLast(date('Y', $consumption_time), date('m', $consumption_time));
        $time_begin = $consumption_time['fist'];
        $time_end = $consumption_time['last'];
        $subQuery = Db::table('canteen_order_t')
            ->alias('a')
            ->field("a.id as order_id,a.c_id as location_id,c.name as location,'canteen' as order_type,a.create_time,a.ordering_date,b.name as dinner,
            (0-a.money-a.sub_money-a.delivery_fee) as money, a.phone,a.count,a.sub_money,a.delivery_fee,a.booking,a.used,a.type as eating_type, 'one' as consumption_type,a.company_id,a.sort_code ,1 as supplement_type,a.unused_handel")
            ->leftJoin('canteen_dinner_t b', 'a.d_id = b.id')
            ->leftJoin('canteen_canteen_t c', 'a.c_id = c.id')
            ->where('a.staff_id', $staffId)
            ->where('a.ordering_date', ">=", $time_begin)
            ->where('a.ordering_date', "<=", $time_end)
            ->where('a.state', CommonEnum::STATE_IS_OK)
            ->where('a.pay', PayEnum::PAY_SUCCESS)
            ->unionAll(function ($query) use ($staffId, $time_begin, $time_end) {
                $query->table("canteen_order_parent_t")
                    ->alias('a')
                    ->field("a.id as order_id,a.canteen_id as location_id,c.name as location,'canteen' as order_type,a.create_time,a.ordering_date,b.name as dinner,(0-a.money-a.sub_money-a.delivery_fee) as money, a.phone,a.count,a.sub_money,a.delivery_fee,a.booking,a.used,a.type as eating_type,'more' as consumption_type,a.company_id,0 as sort_code,1 as supplement_type,a.used as unused_handel")
                    ->leftJoin('canteen_dinner_t b', 'a.dinner_id = b.id')
                    ->leftJoin('canteen_canteen_t c', 'a.canteen_id = c.id')
                    ->where('a.staff_id', $staffId)
                    ->where('a.ordering_date', ">=", $time_begin)
                    ->where('a.ordering_date', "<=", $time_end)
                    ->where('a.type', OrderEnum::EAT_OUTSIDER)
                    ->where('a.state', CommonEnum::STATE_IS_OK)
                    ->where('a.pay', PayEnum::PAY_SUCCESS);
            })
            ->unionAll(function ($query) use ($staffId, $time_begin, $time_end) {
                $query->table("canteen_order_sub_t")
                    ->alias('a')
                    ->field("a.id as order_id,d.canteen_id as location_id,c.name as location,'canteen' as order_type,a.create_time,
                    a.ordering_date,b.name as dinner,(0-a.money-a.sub_money) as money, 
                    d.phone,a.count,a.sub_money,d.delivery_fee,d.booking,a.used,
                    d.type as eating_type,'more' as consumption_type,d.company_id, a.sort_code,1 as supplement_type,a.unused_handel")
                    ->leftJoin('canteen_order_parent_t d', 'a.order_id = d.id')
                    ->leftJoin('canteen_dinner_t b', 'd.dinner_id = b.id')
                    ->leftJoin('canteen_canteen_t c', 'd.canteen_id = c.id')
                    ->where('d.staff_id', $staffId)
                    ->where('a.ordering_date', ">=", $time_begin)
                    ->where('a.ordering_date', "<=", $time_end)
                    ->where('d.type', OrderEnum::EAT_CANTEEN)
                    ->where('a.state', CommonEnum::STATE_IS_OK)
                    ->where('d.pay', PayEnum::PAY_SUCCESS);
            })
            ->unionAll(function ($query) use ($staffId, $time_begin, $time_end) {
                $query->table("canteen_shop_order_t")
                    ->alias('a')
                    ->leftJoin('canteen_shop_t b', 'a.shop_id = b.id')
                    ->field("a.id as order_id,a.shop_id as location_id,b.name as location,'shop' as order_type,a.create_time,date_format(a.create_time, '%Y-%m-%d' ) AS ordering_date,'小卖部' AS dinner,( 0-a.money ) AS money,a.phone,a.count,0 AS sub_money,0 AS delivery_fee,1 AS booking,1 AS used,1 AS eating_type,'one' AS consumption_type,a.company_id,0 AS sort_code,1 as supplement_type,1 as unused_handel")
                    ->where('a.staff_id', $staffId)
                    ->where('a.create_time', ">=", $time_begin)
                    ->where('a.create_time', "<=", $time_end)
                    ->where('a.state', CommonEnum::STATE_IS_OK);
            })
            ->unionAll(function ($query) use ($staffId, $time_begin, $time_end) {
                $query->table("canteen_recharge_supplement_t")
                    ->alias('a')
                    ->leftJoin('canteen_company_t b', 'a.company_id = b.id')
                    ->leftJoin('canteen_dinner_t c', 'a.dinner_id = c.id')
                    ->leftJoin('canteen_company_staff_t d', 'a.staff_id = d.id')
                    ->leftJoin('canteen_canteen_t e', 'a.canteen_id = e.id')
                    ->field("a.id as order_id,a.canteen_id as location_id,e.name as location,'recharge' as order_type,a.create_time,a.consumption_date AS ordering_date,c.name AS dinner,a.money AS money,d.phone,1 as count,0 AS sub_money,0 AS delivery_fee,1 AS booking,1 AS used,1 AS eating_type,'one' AS consumption_type,a.company_id,0 AS sort_code, a.type as supplement_type,1 as unused_handel")
                    ->where('a.staff_id', $staffId)
                    ->where('a.consumption_date', ">=", $time_begin)
                    ->where('a.consumption_date', "<=", $time_end);
            })
            ->unionAll(function ($query) use ($staffId, $time_begin, $time_end) {
                $query->table("canteen_pay_t")
                    ->alias('a')
                    ->leftJoin('canteen_company_t b', 'a.company_id = b.id')
                    ->leftJoin('canteen_company_staff_t d', 'a.staff_id = d.id')
                    ->field("a.id as order_id,0 as  location_id,'' as location,'pay' as order_type,a.create_time,date_format(a.create_time, '%Y-%m-%d' ) AS ordering_date,'' AS dinner,a.money AS money,d.phone,1 as count,0 AS sub_money,0 AS delivery_fee,1 AS booking,1 AS used,1 AS eating_type,'one' AS consumption_type,a.company_id,0 AS sort_code, a.method_id as supplement_type,1 as unused_handel")
                    ->where('a.staff_id', $staffId)
                    ->where('a.create_time', ">=", $time_begin)
                    ->where('a.create_time', "<=", $time_end)
                    ->where('a.status', PayEnum::PAY_SUCCESS)
                    ->where('a.refund', CommonEnum::STATE_IS_FAIL);
            })->unionAll(function ($query) use ($staffId, $time_begin, $time_end) {
                $query->table("canteen_recharge_cash_t")
                    ->alias('a')
                    ->leftJoin('canteen_company_t b', 'a.company_id = b.id')
                    ->leftJoin('canteen_company_staff_t d', 'a.staff_id = d.id')
                    ->field("a.id as order_id,0 as  location_id,'' as location,'pay' as order_type,a.create_time,date_format(a.create_time, '%Y-%m-%d' ) AS ordering_date,'' AS dinner,a.money AS money,d.phone,1 as count,0 AS sub_money,0 AS delivery_fee,1 AS booking,1 AS used,1 AS eating_type,'one' AS consumption_type,a.company_id,0 AS sort_code, 'cash' as supplement_type,1 as unused_handel")
                    ->where('a.staff_id', $staffId)
                    ->where('a.create_time', ">=", $time_begin)
                    ->where('a.create_time', "<=", $time_end)
                    ->where('a.state', CommonEnum::STATE_IS_OK);
            })
            ->buildSql();

        $records = Db::table($subQuery . ' a')
            ->order('create_time', 'desc')
            ->paginate($size, false, ['page' => $page])
            ->toArray();
        /* ->paginate($size, false, ['page' => $page])
           ->toArray();*/


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

    public static function monthConsumptionMoneyByPhone($phone, $consumption_time, $company_id)
    {
        $consumption_time = strtotime($consumption_time);
        $consumption_time = Date::mFristAndLast(date('Y', $consumption_time), date('m', $consumption_time));
        $time_begin = $consumption_time['fist'];
        $time_end = $consumption_time['last'];

        $statistic = Db::table('canteen_order_t')
            ->field('sum(money+sub_money+delivery_fee) as money')
            ->where('phone', $phone)
            ->where('company_id', $company_id)
            ->where('ordering_date', ">=", $time_begin)
            ->where('ordering_date', "<=", $time_end)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->where('pay', PayEnum::PAY_SUCCESS)
            ->unionAll(function ($query) use ($phone, $time_begin, $time_end, $company_id) {
                $query->table("canteen_order_parent_t")
                    ->field('sum(money+sub_money+delivery_fee) as money')
                    ->where('phone', $phone)
                    ->where('company_id', $company_id)
                    ->where('ordering_date', ">=", $time_begin)
                    ->where('ordering_date', "<=", $time_end)
                    ->where('state', CommonEnum::STATE_IS_OK)
                    ->where('pay', PayEnum::PAY_SUCCESS);
            })->unionAll(function ($query) use ($phone, $time_begin, $time_end, $company_id) {
                $query->table("canteen_shop_order_t")
                    ->field('sum(money) as money')
                    ->where('phone', $phone)
                    ->where('company_id', $company_id)
                    ->where('create_time', ">=", $time_begin)
                    ->where('create_time', "<=", $time_end)
                    ->where('state', CommonEnum::STATE_IS_OK);
            })->unionAll(function ($query) use ($phone, $time_begin, $time_end, $company_id) {
                $query->table("canteen_recharge_supplement_t")
                    ->field('sum(0-money) as money')
                    ->where('phone', $phone)
                    ->where('company_id', $company_id)
                    ->where('consumption_date', ">=", $time_begin)
                    ->where('consumption_date', "<=", $time_end);
            })
            ->select()->toArray();
        return array_sum(array_column($statistic, 'money'));
    }

    public static function monthConsumptionMoneyByStaffId($staffId, $consumption_time)
    {
        $consumption_time = strtotime($consumption_time);
        $consumption_time = Date::mFristAndLast(date('Y', $consumption_time), date('m', $consumption_time));
        $time_begin = $consumption_time['fist'];
        $time_end = $consumption_time['last'];

        $statistic = Db::table('canteen_order_t')
            ->field('sum(money+sub_money+delivery_fee) as money')
            ->where('staff_id', $staffId)
            ->where('ordering_date', ">=", $time_begin)
            ->where('ordering_date', "<=", $time_end)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->where('pay', PayEnum::PAY_SUCCESS)
            ->unionAll(function ($query) use ($staffId, $time_begin, $time_end) {
                $query->table("canteen_order_parent_t")
                    ->field('sum(money+sub_money+delivery_fee) as money')
                    ->where('staff_id', $staffId)
                    ->where('ordering_date', ">=", $time_begin)
                    ->where('ordering_date', "<=", $time_end)
                    ->where('state', CommonEnum::STATE_IS_OK)
                    ->where('pay', PayEnum::PAY_SUCCESS);
            })->unionAll(function ($query) use ($staffId, $time_begin, $time_end) {
                $query->table("canteen_shop_order_t")
                    ->field('sum(money) as money')
                    ->where('staff_id', $staffId)
                    ->where('create_time', ">=", $time_begin)
                    ->where('create_time', "<=", $time_end)
                    ->where('state', CommonEnum::STATE_IS_OK);
            })->unionAll(function ($query) use ($staffId, $time_begin, $time_end) {
                $query->table("canteen_recharge_supplement_t")
                    ->field('sum(0-money) as money')
                    ->where('staff_id', $staffId)
                    ->where('consumption_date', ">=", $time_begin)
                    ->where('consumption_date', "<=", $time_end);
            })
            ->select()->toArray();
        return array_sum(array_column($statistic, 'money'));
    }


    public static function fixedRecords($phone, $company_id, $page, $size)
    {
        $subQuery = Db::table('canteen_order_t')
            ->alias('a')
            ->field("a.id as order_id,a.c_id as location_id,c.name as location,'canteen' as order_type,a.create_time,a.ordering_date,b.name as dinner,
            (0-a.money-a.sub_money-a.delivery_fee) as money, a.phone,a.count,a.sub_money,a.delivery_fee,a.booking,a.used,a.type as eating_type, 'one' as consumption_type,a.company_id,a.sort_code")
            ->leftJoin('canteen_dinner_t b', 'a.d_id = b.id')
            ->leftJoin('canteen_canteen_t c', 'a.c_id = c.id')
            ->where('a.phone', $phone)
            ->where('a.company_id', $company_id)
            ->where('a.used', CommonEnum::STATE_IS_FAIL)
            ->where('a.unused_handel', CommonEnum::STATE_IS_FAIL)
            ->where('a.state', CommonEnum::STATE_IS_OK)
            ->where('a.pay', PayEnum::PAY_SUCCESS)
            ->unionAll(function ($query) use ($phone, $company_id) {
                $query->table("canteen_order_parent_t")
                    ->alias('a')
                    ->field("a.id as order_id,a.canteen_id as location_id,c.name as location,'canteen' as order_type,a.create_time,a.ordering_date,b.name as dinner,(0-a.money-a.sub_money-a.delivery_fee) as money, a.phone,a.count,a.sub_money,a.delivery_fee,a.booking,a.used,a.type as eating_type,'more' as consumption_type,a.company_id,0 as sort_code")
                    ->leftJoin('canteen_dinner_t b', 'a.dinner_id = b.id')
                    ->leftJoin('canteen_canteen_t c', 'a.canteen_id = c.id')
                    ->where('a.phone', $phone)
                    ->where('a.company_id', $company_id)
                    ->where('a.used', CommonEnum::STATE_IS_FAIL)
                    ->where('a.unused_handel', CommonEnum::STATE_IS_FAIL)
                    ->where('a.type', OrderEnum::EAT_OUTSIDER)
                    ->where('a.state', CommonEnum::STATE_IS_OK)
                    ->where('a.pay', PayEnum::PAY_SUCCESS);
            })
            ->unionAll(function ($query) use ($phone, $company_id) {
                $query->table("canteen_order_sub_t")
                    ->alias('a')
                    ->field("a.id as order_id,d.canteen_id as location_id,c.name as location,'canteen' as order_type,a.create_time,
                    a.ordering_date,b.name as dinner,(0-a.money-a.sub_money) as money, 
                    d.phone,a.count,a.sub_money,d.delivery_fee,d.booking,a.used,
                    d.type as eating_type,'more' as consumption_type,d.company_id, a.sort_code")
                    ->leftJoin('canteen_order_parent_t d', 'a.order_id = d.id')
                    ->leftJoin('canteen_dinner_t b', 'd.dinner_id = b.id')
                    ->leftJoin('canteen_canteen_t c', 'd.canteen_id = c.id')
                    ->where('d.phone', $phone)
                    ->where('d.company_id', $company_id)
                    ->where('d.type', OrderEnum::EAT_CANTEEN)
                    ->where('a.used', CommonEnum::STATE_IS_FAIL)
                    ->where('a.unused_handel', CommonEnum::STATE_IS_FAIL)
                    ->where('a.state', CommonEnum::STATE_IS_OK)
                    ->where('d.pay', PayEnum::PAY_SUCCESS);
            })
            ->buildSql();

        $records = Db::table($subQuery . ' a')
            ->order('create_time', 'desc')
            ->paginate($size, false, ['page' => $page])
            ->toArray();
        return $records;
    }


}