<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use app\lib\enum\PayEnum;
use think\Db;
use think\Model;

class AccountRecordsV
{

    public static function getSql()
    {
        $sql = Db::field('a.account_id,a.staff_id,d.name,a.company_id,a.money,c.phone,c.username,b.department_id as department_id,b.staff_type_id,b.ordering_date as consumption_date ,IF ((b.booking=2),3,IF ((b.used=2),2,1)) AS status,"canteen" as location,b.c_id as location_id')->table('canteen_account_records_t')
            ->alias('a')
            ->leftJoin('canteen_order_t b', 'a.order_id=b.id')
            ->leftJoin('canteen_company_staff_t c', 'b.staff_id=c.id')
            ->leftJoin('canteen_company_account_t d', 'a.account_id=d.id')
            ->where('a.type', 'one')
            ->unionAll(function ($query) {
                $query->field('a.account_id,a.staff_id,e.name,a.company_id,a.money,c.phone,c.username,d.department_id,d.staff_type_id,d.ordering_date as consumption_date ,IF ((b.booking=2),3,IF ((b.used=2),2,1)) AS status,"canteen" as location,d.canteen_id as location_id')->table('canteen_account_records_t')
                    ->alias('a')
                    ->leftJoin('canteen_order_sub_t b', 'a.order_id=b.id')
                    ->leftJoin('canteen_order_parent_t d', 'b.order_id=d.id')
                    ->leftJoin('canteen_company_staff_t c', 'd.staff_id=c.id')
                    ->leftJoin('canteen_company_account_t e', 'a.account_id=e.id')
                    ->where('a.type', 'more')
                    ->where('a.outsider', CommonEnum::STATE_IS_FAIL);
            })->unionAll(function ($query) {
                $query->field('a.account_id,a.staff_id,e.name,a.company_id,a.money,c.phone,c.username,d.department_id,d.staff_type_id,d.ordering_date as consumption_date ,1 AS status,"canteen" as location,d.canteen_id as location_id')
                    ->table('canteen_account_records_t')
                    ->alias('a')
                    ->leftJoin('canteen_order_parent_t d', 'a.order_id=d.id')
                    ->leftJoin('canteen_company_staff_t c', 'd.staff_id=c.id')
                    ->leftJoin('canteen_company_account_t e', 'a.account_id=e.id')
                    ->where('a.type', 'more')
                    ->where('a.outsider', CommonEnum::STATE_IS_OK);
            })
            ->unionAll(function ($query) {
                $query->field('a.account_id,a.staff_id,d.name,a.company_id,a.money,c.phone,c.username,c.d_id as department_id,c.t_id as staff_type_id,b.consumption_date,IF ((b.type=1),4,5) AS status,"recharge" as location,b.canteen_id as location_id')->table('canteen_account_records_t')
                    ->alias('a')
                    ->leftJoin('canteen_recharge_supplement_t b', 'a.order_id=b.id')
                    ->leftJoin('canteen_company_staff_t c', 'b.staff_id=c.id')
                    ->leftJoin('canteen_company_account_t d', 'a.account_id=d.id')
                    ->where('a.type', 'supplement');
            })->unionAll(function ($query) {
                $query->field('a.account_id,a.staff_id,d.name,a.company_id,a.money,c.phone,c.username,c.d_id as department_id,b.staff_type_id,date_format(b.create_time, "%Y%-%m%-%d" ) AS consumption_date,IF ((b.money> 0),6,7) AS status,"shop" as location,b.shop_id as location_id')->table('canteen_account_records_t')
                    ->alias('a')
                    ->leftJoin('canteen_shop_order_t b', 'a.order_id=b.id')
                    ->leftJoin('canteen_company_staff_t c', 'b.staff_id=c.id')
                    ->leftJoin('canteen_company_account_t d', 'a.account_id=d.id')
                    ->where('a.type', 'shop');
            })->buildSql();
        return $sql;

    }

    public static function getAccountDetailSql($staffId,$personID, $nongHangID,$consumptionDate)
    {
        $sql = Db::field('a.account_id,a.staff_id,a.money as money,d.name as type,a.consumption_date')
            ->alias('a')
            ->leftJoin('canteen_order_t b', 'a.order_id=b.id')
            ->leftJoin('canteen_dinner_id c', 'b.d_id=c.id')
            ->where('a.consumptionDate', $consumptionDate)
            ->where('a.staff_id', $staffId)
            ->where('a.type', 'one')
            ->where('state', CommonEnum::STATE_IS_OK)
            ->unionAll(function ($query) use ($staffId,$consumptionDate){
                $query->field('a.account_id,a.staff_id,a.money,d.name as type,a.consumption_date')
                    ->table('canteen_account_records_t')
                    ->alias('a')
                    ->leftJoin('canteen_order_sub_t b', 'a.order_id=b.id')
                    ->leftJoin('canteen_parent c', 'b.order_id=c.id')
                    ->leftJoin('canteen_dinner_id d', 'c.dinner_id=d.id')
                    ->where('a.consumptionDate', $consumptionDate)
                    ->where('a.staff_id', $staffId)
                    ->where('a.type', 'more')
                    ->where('a.outsider', CommonEnum::STATE_IS_FAIL)
                    ->where('state', CommonEnum::STATE_IS_OK);
            })->unionAll(function ($query)use ($staffId) {
                $query->field('a.account_id,a.staff_id,a.money,c.name as type,a.consumption_date')
                    ->table('canteen_account_records_t')
                    ->alias('a')
                    ->leftJoin('canteen_order_parent_t b', 'a.order_id=b.id')
                    ->leftJoin('canteen_dinner_t c', 'b.dinner_id=c.id')
                    ->where('a.staff_id', $staffId)
                    ->where('a.type', 'more')
                    ->where('a.outsider', CommonEnum::STATE_IS_OK)
                    ->where('state', CommonEnum::STATE_IS_OK);
            })
            ->unionAll(function ($query) use ($staffId){
                $query->field('a.account_id,a.staff_id,a.money as money,IF ((b.type=1),"系统补口","系统补扣") AS as type,a.consumption_date')
                    ->table('canteen_account_records_t')
                    ->alias('a')
                    ->leftJoin('canteen_recharge_supplement_t b', 'a.order_id=b.id')
                    ->where('a.staff_id', $staffId)
                    ->where('a.type', 'supplement')
                    ->where('state', CommonEnum::STATE_IS_OK);
            })->unionAll(function ($query) use ($staffId){
                $query->field('a.account_id,a.staff_id,a.money,if(a.money>0,"小卖部退款"，"小卖部消费") type,a.consumption_date')
                    ->table('canteen_account_records_t')
                    ->alias('a')
                    ->where('a.staff_id', $staffId)
                    ->where('a.type', 'shop')
                    ->where('state', CommonEnum::STATE_IS_OK);
            })->unionAll(function ($query)use ($staffId) {
                $query->field('a.account_id,a.staff_id,a.money,"系统充值" as  type,a.consumption_date')
                    ->table('canteen_account_records_t')
                    ->alias('a')
                    ->where('a.staff_id', $staffId)
                    ->where('a.type', 'cash')
                    ->where('state', CommonEnum::STATE_IS_OK);
            })->unionAll(function ($query) use ($personID,$staffId) {
                if ($personID) {
                    $query->field($personID . ',a.staff_id,a.money,"微信充值" as  type,date_format( a.create_time, "%Y%-%m%-%d" ) as consumption_date')
                        ->table('canteen_pay_t')
                        ->alias('a')
                        ->where('a.staff_id', $staffId)
                        ->where('a.method_id', PayEnum::PAY_METHOD_WX)
                        ->where('a.status', PayEnum::PAY_SUCCESS);
                }

            })->unionAll(function ($query) use ($nongHangID,$staffId) {
                if ($nongHangID) {
                    $query->field($nongHangID . ',a.staff_id,a.money,"农行充值" as  type,date_format( a.create_time, "%Y%-%m%-%d" ) as consumption_date')
                        ->table('canteen_pay_t')
                        ->alias('a')
                        ->where('a.staff_id', $staffId)
                        ->where('a.method_id', PayEnum::PAY_METHOD_NH)
                        ->where('a.status', PayEnum::PAY_SUCCESS);
                }

            })->unionAll(function ($query) use ($personID, $nongHangID,$staffId) {
                $query->field('a.account_id,a.staff_id,a.money,"到期清零" as type,a.consumption_date')
                    ->table('canteen_account_records_t')
                    ->alias('a')
                    ->where('a.staff_id', $staffId)
                    ->where('a.type', 'clear')
                    ->where('a.state', CommonEnum::STATE_IS_OK);
            })
            ->buildSql();
        return $sql;

    }

    public static function consumptionStatisticByDepartment($canteen_id, $status, $department_id,
                                                            $username, $staff_type_id, $time_begin,
                                                            $time_end, $company_id, $phone, $order_type)
    {
        $sql = self::getSql();
        $statistic = Db::table($sql . ' a')->where(function ($query) use ($company_id, $canteen_id) {
            if (!empty($canteen_id)) {
                $query->where('location_id', $canteen_id);
            } else {
                if (strpos($company_id, ',') !== false) {
                    $query->whereIn('company_id', $company_id);
                } else {
                    $query->where('company_id', $company_id);
                }
            }
        })
            ->where(function ($query) use ($order_type) {
                if ($order_type !== 'all') {
                    $query->where('location', $order_type);
                }
            })
            ->where('consumption_date', '>=', $time_begin)
            ->where('consumption_date', '<=', $time_end)
            ->where(function ($query) use (
                $status, $department_id,
                $username, $staff_type_id, $phone
            ) {
                if (!empty($status)) {
                    $query->where('status', $status);
                }
                if (!empty($department_id)) {
                    $query->where('department_id', $department_id);
                }
                if (!empty($phone)) {
                    $query->where('phone', $phone);
                }
                if (!empty($username)) {
                    $query->where('username', 'like', '%' . $username . '%');
                }
                if (!empty($staff_type_id)) {
                    $query->where('staff_type_id', $staff_type_id);
                }

            })
            ->field('account_id,name,department_id as statistic_id,sum(0-money) as money')
            ->group('account_id,department_id')
            ->select()
            ->toArray();
        return $statistic;
    }


    public static function consumptionStatisticByStaff($canteen_id, $status, $department_id,
                                                       $username, $staff_type_id, $time_begin,
                                                       $time_end, $company_id, $phone, $order_type)
    {
        $sql = self::getSql();
        $statistic = Db::table($sql . ' a')
            ->where(function ($query) use ($company_id, $canteen_id) {
                if (!empty($canteen_id)) {
                    $query->where('location_id', $canteen_id);
                } else {
                    if (strpos($company_id, ',') !== false) {
                        $query->whereIn('company_id', $company_id);
                    } else {
                        $query->where('company_id', $company_id);
                    }
                }
            })
            ->where(function ($query) use ($order_type) {
                if ($order_type !== 'all') {
                    $query->where('location', $order_type);
                }
            })
            ->where('consumption_date', '>=', $time_begin)
            ->where('consumption_date', '<=', $time_end)
            ->where(function ($query) use (
                $status, $department_id,
                $username, $staff_type_id, $phone
            ) {
                if (!empty($status)) {
                    $query->where('status', $status);
                }
                if (!empty($department_id)) {
                    $query->where('department_id', $department_id);
                }
                if (!empty($phone)) {
                    $query->where('phone', $phone);
                }
                if (!empty($username)) {
                    $query->where('username', 'like', '%' . $username . '%');
                }
                if (!empty($staff_type_id)) {
                    $query->where('staff_type_id', $staff_type_id);
                }

            })
            ->field('account_id,name,staff_type_id as statistic_id,sum(0-money) as money')
            ->group('account_id,staff_type_id')
            ->select()
            ->toArray();
        return $statistic;
    }


    public static function consumptionStatisticByCanteen($canteen_id, $status, $department_id,
                                                         $username, $staff_type_id, $time_begin,
                                                         $time_end, $company_id, $phone, $order_type)
    {
        $sql = self::getSql();
        $statistic = Db::table($sql . ' a')
            ->where(function ($query) use ($company_id, $canteen_id) {
                if (!empty($canteen_id)) {
                    $query->where('location_id', $canteen_id);
                } else {
                    if (strpos($company_id, ',') !== false) {
                        $query->whereIn('company_id', $company_id);
                    } else {
                        $query->where('company_id', $company_id);
                    }
                }
            })
            ->where(function ($query) use ($order_type) {
                if ($order_type !== 'all') {
                    $query->where('location', $order_type);
                }
            })
            ->where('consumption_date', '>=', $time_begin)
            ->where('consumption_date', '<=', $time_end)
            ->where(function ($query) use (
                $status, $department_id,
                $username, $staff_type_id, $phone
            ) {
                if (!empty($status)) {
                    $query->where('status', $status);
                }
                if (!empty($phone)) {
                    $query->where('phone', $phone);
                }
                if (!empty($department_id)) {
                    $query->where('department_id', $department_id);
                }
                if (!empty($username)) {
                    $query->where('username', 'like', '%' . $username . '%');
                }
                if (!empty($staff_type_id)) {
                    $query->where('staff_type_id', $staff_type_id);
                }

            })
            ->field('account_id,name,location_id as statistic_id,sum(0-money) as money')
            ->group('account_id,location_id')
            ->select()
            ->toArray();
        return $statistic;
    }

    public static function consumptionStatisticByStatus($canteen_id, $status, $department_id,
                                                        $username, $staff_type_id, $time_begin,
                                                        $time_end, $company_id, $phone, $order_type)
    {
        $sql = self::getSql();
        $statistic = Db::table($sql . ' a')
            ->where(function ($query) use ($company_id, $canteen_id) {
                if (!empty($canteen_id)) {
                    $query->where('location_id', $canteen_id);
                } else {
                    if (strpos($company_id, ',') !== false) {
                        $query->whereIn('company_id', $company_id);
                    } else {
                        $query->where('company_id', $company_id);
                    }
                }
            })
            ->where(function ($query) use ($order_type) {
                if ($order_type !== 'all') {
                    $query->where('location', $order_type);
                }
            })
            ->where('consumption_date', '>=', $time_begin)
            ->where('consumption_date', '<=', $time_end)
            ->where(function ($query) use (
                $status, $department_id,
                $username, $staff_type_id, $phone
            ) {
                if (!empty($phone)) {
                    $query->where('phone', $phone);
                }
                if (!empty($status)) {
                    $query->where('status', $status);
                }
                if (!empty($department_id)) {
                    $query->where('department_id', $department_id);
                }
                if (!empty($username)) {
                    $query->where('username', 'like', '%' . $username . '%');
                }
                if (!empty($staff_type_id)) {
                    $query->where('staff_type_id', $staff_type_id);
                }

            })
            ->field('account_id,name,status as statistic_id,sum(0-money) as money')
            ->group('account_id,status')
            ->select();
        return $statistic;
    }

    public static function userDinnerStatistic($canteen_id, $status, $department_id,
                                               $username, $staff_type_id, $time_begin,
                                               $time_end, $company_id, $phone, $order_type)
    {
        $sql = self::getSql();
        $statistic = Db::table($sql . ' a')
            ->where(function ($query) use ($company_id, $canteen_id) {
                if (!empty($canteen_id)) {
                    $query->where('location_id', $canteen_id);
                } else {
                    if (strpos($company_id, ',') !== false) {
                        $query->whereIn('company_id', $company_id);
                    } else {
                        $query->where('company_id', $company_id);
                    }
                }
            })->where(function ($query) use ($order_type) {
                if ($order_type !== 'all') {
                    $query->where('location', $order_type);
                }
            })
            ->where(function ($query) use (
                $department_id,
                $username, $staff_type_id, $phone
            ) {
                if (!empty($department_id)) {
                    $query->where('department_id', $department_id);
                }
                if (!empty($phone)) {
                    $query->where('phone', $phone);
                }
                if (!empty($username)) {
                    $query->where('username', 'like', '%' . $username . '%');
                }
                if (!empty($status)) {
                    $query->where('staff_type_id', $staff_type_id);
                }

            })
            ->where('consumption_date', '>=', $time_begin)
            ->where('consumption_date', '<=', $time_end)
            ->where(function ($query2) use (
                $status
            ) {
                if (!empty($status)) {
                    $query2->where('status', $status);

                }
            })
            ->field('account_id,name,staff_id as statistic_id,sum(0-money) as money')
            ->group('account_id,staff_id')
            ->select()->toArray();
        return $statistic;
    }


    public static function transactionDetails($staffId, $page, $size, $personID, $nongHangID,$consumptionDate, $type)
    {
        $sql = self::getAccountDetailSql($staffId,$personID, $nongHangID,$consumptionDate);

    }
}