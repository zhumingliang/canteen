<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use app\lib\enum\PayEnum;
use think\Db;
use think\Model;

class UserBalanceV extends Model
{

    public function getBalanceAttr($value)
    {
        return round($value, 2);
    }

    public static function usersBalance($page, $size, $department_id, $user, $phone, $company_id)
    {
        $orderings = self::where('company_id', $company_id)
            ->where(function ($query) use ($department_id) {
                if (!empty($department_id)) {
                    $query->where('department_id', $department_id);
                }
            })
            ->where(function ($query) use ($phone) {
                if (!empty($phone)) {
                    $query->where('phone', $phone);
                }
            })
            ->where(function ($query) use ($user) {
                if (!empty($user)) {
                    $query->where('username|code|card_num', 'like', '%' . $user . '%');
                }
            })
            ->field('username,code,card_num,phone,department,sum(money) as balance')
            ->group('phone,company_id')
            ->paginate($size, false, ['page' => $page]);
        return $orderings;
    }


    public static function exportUsersBalance($department_id, $user, $phone, $company_id)
    {
        $orderings = self::where('company_id', $company_id)
            ->where(function ($query) use ($department_id) {
                if (!empty($department_id)) {
                    $query->where('department_id', $department_id);
                }
            })
            ->where(function ($query) use ($phone) {
                if (!empty($phone)) {
                    $query->where('phone', $phone);
                }
            })
            ->where(function ($query) use ($user) {
                if (!empty($user)) {
                    $query->where('username|code|card_num', 'like', '%' . $user . '%');
                }
            })
            ->field('username,code,card_num,phone,department,sum(money) as balance')
            ->group('phone,company_id')
            ->select()->toArray();
        return $orderings;
    }

    public static function userBalance($company_id, $phone)
    {
        $balance = self::where('phone', $phone)
            ->where('company_id', $company_id)
            ->sum('money');
        return $balance;
    }

    public static function userBalanceGroupByEffective($company_id, $phone)
        //public static function userBalanceGroupByEffective($staff_id)
    {
        $balance = self::where('phone', $phone)
            ->where('company_id', $company_id)
            ->field('sum(money) as money,effective')
            ->group('effective')
            ->select()->toArray();
        return $balance;
        $balance = Db::table('canteen_order_t')
            ->field('(0-money-sub_money-delivery_fee) as money,IF ((used=1),1,IF ((unused_handel=1),1,2)) AS effective')
            ->where('staff_id', $staff_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->where('pay', PayEnum::PAY_SUCCESS)
            ->unionAll(function ($query) use ($staff_id) {
                $query->table("canteen_order_parent_t")
                    ->field('(0-delivery_fee) as money,IF ((used=1),1,2) AS effective')
                    ->where('staff_id', $staff_id)
                    ->where('outsider', CommonEnum::STATE_IS_FAIL)
                    ->where('state', CommonEnum::STATE_IS_OK)
                    ->where('pay', PayEnum::PAY_SUCCESS);
            })
            ->unionAll(function ($query) use ($staff_id) {
                $query->table("canteen_order_sub_t")
                    ->alias('a')
                    ->leftJoin('canteen_order_parent_t b', 'a.order_id = b.id')
                    ->field('(0-delivery_fee) as money,IF ((a.used=1),1,IF ((a.unused_handel=1),1,2)) AS effective')
                    ->where('b.staff_id', $staff_id)
                    ->where('b.state', CommonEnum::STATE_IS_OK)
                    ->where('b.outsider', CommonEnum::STATE_IS_FAIL)
                    ->where('b.pay', PayEnum::PAY_SUCCESS);
            })
            ->unionAll(function ($query) use ($staff_id) {
                $query->table("canteen_shop_order_t")
                    ->field('money,used as effective')
                    ->where('staff_id', $staff_id)
                    ->where('state', CommonEnum::STATE_IS_OK)
                    ->where('pay', PayEnum::PAY_SUCCESS);
            })
            ->unionAll(function ($query) use ($staff_id) {
                $query->table("canteen_recharge_supplement_t")
                    ->field('money,1 as effective')
                    ->where('staff_id', $staff_id);

            })
            ->unionAll(function ($query) use ($staff_id) {
                $query->table("canteen_pay_t")
                    ->field('money,1 as effective')
                    ->where('staff_id', $staff_id)
                    ->where('status', PayEnum::PAY_SUCCESS)
                    ->where('refund', CommonEnum::STATE_IS_FAIL);

            })
            ->unionAll(function ($query) use ($staff_id) {
                $query->table("canteen_clear_money_t")
                    ->field('money,1 as effective')
                    ->where('staff_id', $staff_id)
                    ->where('state', CommonEnum::STATE_IS_OK);

            })
            ->unionAll(function ($query) use ($staff_id) {
                $query->table("canteen_recharge_cash_t")
                    ->field('money,1 as effective')
                    ->where('staff_id', $staff_id)
                    ->where('state', CommonEnum::STATE_IS_OK);
            })
            ->select()
            ->toArray();
        return $balance;
    }

    public static function userBalanceGroupByEffective2($staff_id)
    {
        $balance = Db::table('canteen_order_t')
            ->field('(0-money-sub_money-delivery_fee) as money,IF ((used=1),1,IF ((unused_handel=1),1,2)) AS effective')
            ->where('staff_id', $staff_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->where('pay', PayEnum::PAY_SUCCESS)
            ->unionAll(function ($query) use ($staff_id) {
                $query->table("canteen_order_parent_t")
                    ->field('(0-delivery_fee) as money,IF ((used=1),1,2) AS effective')
                    ->where('staff_id', $staff_id)
                    ->where('state', CommonEnum::STATE_IS_OK)
                    ->where('pay', PayEnum::PAY_SUCCESS);
            })
            ->unionAll(function ($query) use ($staff_id) {
                $query->table("canteen_order_sub_t")
                    ->alias('a')
                    ->leftJoin('canteen_order_parent_t b', 'a.order_id = b.id')
                    ->field('(0-delivery_fee) as money,IF ((a.used=1),1,IF ((a.unused_handel=1),1,2)) AS effective')
                    ->where('b.staff_id', $staff_id)
                    ->where('b.state', CommonEnum::STATE_IS_OK)
                    ->where('b.pay', PayEnum::PAY_SUCCESS);
            })
            ->unionAll(function ($query) use ($staff_id) {
                $query->table("canteen_shop_order_t")
                    ->field('money,used as effective')
                    ->where('staff_id', $staff_id)
                    ->where('state', CommonEnum::STATE_IS_OK)
                    ->where('pay', PayEnum::PAY_SUCCESS);
            })
            ->unionAll(function ($query) use ($staff_id) {
                $query->table("canteen_recharge_supplement_t")
                    ->field('money,1 as effective')
                    ->where('staff_id', $staff_id);

            })
            ->unionAll(function ($query) use ($staff_id) {
                $query->table("canteen_pay_t")
                    ->field('money,1 as effective')
                    ->where('staff_id', $staff_id)
                    ->where('status', PayEnum::PAY_SUCCESS)
                    ->where('refund', CommonEnum::STATE_IS_FAIL);

            })
            ->unionAll(function ($query) use ($staff_id) {
                $query->table("canteen_clear_money_t")
                    ->field('money,1 as effective')
                    ->where('staff_id', $staff_id)
                    ->where('state', CommonEnum::STATE_IS_OK);

            })
            ->unionAll(function ($query) use ($staff_id) {
                $query->table("canteen_recharge_cash_t")
                    ->field('money,1 as effective')
                    ->where('staff_id', $staff_id)
                    ->where('state', CommonEnum::STATE_IS_OK);
            })
            ->select()
            ->toArray();
        return $balance;
    }

}