<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use app\lib\enum\OrderEnum;
use app\lib\enum\PayEnum;
use think\Db;
use think\Model;

class UserBalanceV extends Model
{

    public static function getSql($staff_id)
    {
        $sql = Db::table('canteen_order_t')
            ->field('(0-money-sub_money-delivery_fee) as money,IF ((used=1),1,IF ((unused_handel=1),1,2)) AS effective')
            ->where('staff_id', $staff_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->where('pay', PayEnum::PAY_SUCCESS)
            ->unionAll(function ($query) use ($staff_id) {
                $query->table("canteen_order_parent_t")
                    ->field('(0-delivery_fee) as money,IF ((used=1),1,2) AS effective')
                    ->where('staff_id', $staff_id)
                    ->where('type', OrderEnum::EAT_OUTSIDER)
                    ->where('state', CommonEnum::STATE_IS_OK)
                    ->where('pay', PayEnum::PAY_SUCCESS);
            })
            ->unionAll(function ($query) use ($staff_id) {
                $query->table("canteen_order_sub_t")
                    ->alias('a')
                    ->leftJoin('canteen_order_parent_t b', 'a.order_id = b.id')
                    ->field('(0-a.money-a.sub_money) as money,IF ((a.used=1),1,IF ((a.unused_handel=1),1,2)) AS effective')
                    ->where('b.staff_id', $staff_id)
                    ->where('b.state', CommonEnum::STATE_IS_OK)
                    ->where('b.pay', PayEnum::PAY_SUCCESS);
            })
            ->unionAll(function ($query) use ($staff_id) {
                $query->table("canteen_shop_order_t")
                    ->field('(0-money) as money,used as effective')
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
                $query->table("canteen_recharge_cash_t")
                    ->field('money,1 as effective')
                    ->where('staff_id', $staff_id)
                    ->where('state', CommonEnum::STATE_IS_OK);
            })->buildSql();
        return $sql;
    }

    public static function getCompanySql($companyId)
    {
        $sql = Db::table('canteen_order_t')
            ->field('(0-money-sub_money-delivery_fee) as money,staff_id')
            ->where('company_id', $companyId)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->where('pay', PayEnum::PAY_SUCCESS)
            ->unionAll(function ($query) use ($companyId) {
                $query->table("canteen_order_parent_t")
                    ->field('(0-money-sub_money-delivery_fee) as money,staff_id')
                    ->where('company_id', $companyId)
                    ->where('state', CommonEnum::STATE_IS_OK)
                    ->where('pay', PayEnum::PAY_SUCCESS);
            })
            ->unionAll(function ($query) use ($companyId) {
                $query->table("canteen_shop_order_t")
                    ->field('(0-money) as money,staff_id')
                    ->where('company_id', $companyId)
                    ->where('state', CommonEnum::STATE_IS_OK)
                    ->where('pay', PayEnum::PAY_SUCCESS);
            })
            ->unionAll(function ($query) use ($companyId) {
                $query->table("canteen_recharge_supplement_t")
                    ->field('money,staff_id')
                    ->where('company_id', $companyId);

            })
            ->unionAll(function ($query) use ($companyId) {
                $query->table("canteen_pay_t")
                    ->field('money,staff_id')
                    ->where('company_id', $companyId)
                    ->where('status', PayEnum::PAY_SUCCESS)
                    ->where('refund', CommonEnum::STATE_IS_FAIL);

            })
            ->unionAll(function ($query) use ($companyId) {
                $query->table("canteen_recharge_cash_t")
                    ->field('money,staff_id')
                    ->where('company_id', $companyId)
                    ->where('state', CommonEnum::STATE_IS_OK);
            })->buildSql();
        return $sql;
    }

    public static function getSqlForStaffsBalance($companyId)
    {
        $sql = Db::table('canteen_company_staff_t')
            ->alias('b')
            ->field('0 as money,b.id as staff_id,b.username,b.code,c.card_code as card_num,b.phone,b.d_id as department_id,d.name as department')
            ->where('b.company_id', $companyId)
            ->leftJoin('canteen_staff_card_t c', "b.id=c.staff_id and c.state<3")
            ->leftJoin('canteen_company_department_t d', "b.d_id=d.id")
            ->unionAll(function ($query) use ($companyId) {
                $query->table('canteen_order_t')
                    ->alias('a')
                    ->field('(0-a.money-a.sub_money-a.delivery_fee) as money,a.staff_id,b.username,b.code,c.card_code as card_num,b.phone,b.d_id as department_id,d.name as department')
                    ->where('a.company_id', $companyId)
                    ->where('a.state', CommonEnum::STATE_IS_OK)
                    ->where('a.pay', PayEnum::PAY_SUCCESS)
                    ->leftJoin('canteen_company_staff_t b', "a.staff_id=b.id")
                    ->leftJoin('canteen_staff_card_t c', "b.id=c.staff_id and c.state<3")
                    ->leftJoin('canteen_company_department_t d', "b.d_id=d.id");
            })
            ->unionAll(function ($query) use ($companyId) {
                $query->table("canteen_order_parent_t")
                    ->alias('a')
                    ->field('(0-a.money-a.sub_money-a.delivery_fee) as money,a.staff_id,b.username,b.code,c.card_code as card_num,b.phone,b.d_id as department_id,d.name as department')
                    ->where('a.company_id', $companyId)
                    ->where('a.state', CommonEnum::STATE_IS_OK)
                    ->where('a.pay', PayEnum::PAY_SUCCESS)
                    ->leftJoin('canteen_company_staff_t b', "a.staff_id=b.id")
                    ->leftJoin('canteen_staff_card_t c', "b.id=c.staff_id and c.state<3")
                    ->leftJoin('canteen_company_department_t d', "b.d_id=d.id");
            })
            ->unionAll(function ($query) use ($companyId) {
                $query->table("canteen_shop_order_t")
                    ->alias('a')
                    ->field('(0-a.money) as money,a.staff_id,b.username,b.code,c.card_code as card_num,b.phone,b.d_id as department_id,d.name as department')
                    ->where('a.company_id', $companyId)
                    ->where('a.state', CommonEnum::STATE_IS_OK)
                    ->where('a.pay', PayEnum::PAY_SUCCESS)
                    ->leftJoin('canteen_company_staff_t b', "a.staff_id=b.id")
                    ->leftJoin('canteen_staff_card_t c', "b.id=c.staff_id and c.state<3")
                    ->leftJoin('canteen_company_department_t d', "b.d_id=d.id");
            })
            ->unionAll(function ($query) use ($companyId) {
                $query->table("canteen_recharge_supplement_t")
                    ->alias('a')
                    ->field('a.money,a.staff_id,b.username,b.code,c.card_code as card_num,b.phone,b.d_id as department_id,d.name as department')
                    ->where('a.company_id', $companyId)
                    ->leftJoin('canteen_company_staff_t b', "a.staff_id=b.id")
                    ->leftJoin('canteen_staff_card_t c', "b.id=c.staff_id and c.state<3")
                    ->leftJoin('canteen_company_department_t d', "b.d_id=d.id");

            })
            ->unionAll(function ($query) use ($companyId) {
                $query->table("canteen_pay_t")
                    ->alias('a')
                    ->field('a.money,a.staff_id,b.username,b.code,c.card_code as card_num,b.phone,b.d_id as department_id,d.name as department')
                    ->where('a.company_id', $companyId)
                    ->where('a.status', PayEnum::PAY_SUCCESS)
                    ->where('a.refund', CommonEnum::STATE_IS_FAIL)
                    ->leftJoin('canteen_company_staff_t b', "a.staff_id=b.id")
                    ->leftJoin('canteen_staff_card_t c', "b.id=c.staff_id and c.state<3")
                    ->leftJoin('canteen_company_department_t d', "b.d_id=d.id");

            })
            ->unionAll(function ($query) use ($companyId) {
                $query->table("canteen_recharge_cash_t")
                    ->alias('a')
                    ->field('a.money,a.staff_id,b.username,b.code,c.card_code as card_num,b.phone,b.d_id as department_id,d.name as department')
                    ->where('a.company_id', $companyId)
                    ->where('a.state', CommonEnum::STATE_IS_OK)
                    ->leftJoin('canteen_company_staff_t b', "a.staff_id=b.id")
                    ->leftJoin('canteen_staff_card_t c', "b.id=c.staff_id and c.state<3")
                    ->leftJoin('canteen_company_department_t d', "b.d_id=d.id");

            })->buildSql();
        return $sql;
    }


    public function getBalanceAttr($value)
    {
        return round($value, 2);
    }

    public static function usersBalance($page, $size, $department_id, $user, $phone, $company_id, $checkCard)
    {
        /* $orderings = self::where('company_id', $company_id)
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
             ->paginate($size, false, ['page' => $page]);*/

        // return $orderings;
        $sql = self::getSqlForStaffsBalance($company_id);
        if ($checkCard) {
            $fields = 'a.staff_id,a.username,a.code,a.card_num,a.phone,a.department,sum(a.money) as balance';
        } else {
            $fields = 'a.staff_id,a.username,a.code,a.phone,a.department,sum(a.money) as balance';
        }
        $orderings = Db::table($sql . 'a')
            ->where(function ($query) use ($department_id) {
                if (!empty($department_id)) {
                    $query->where('a.department_id', $department_id);
                }
            })
            ->where(function ($query) use ($phone) {
                if (!empty($phone)) {
                    $query->where('a.phone', $phone);
                }
            })
            ->where(function ($query) use ($user) {
                if (!empty($user)) {
                    $query->where('a.username|a.code|a.card_num', 'like', '%' . $user . '%');
                }
            })
            ->field($fields)
            ->group('a.staff_id')
            ->order('a.staff_id')
            ->paginate($size, false, ['page' => $page])->toArray();

        return $orderings;
    }


    public static function exportUsersBalance($department_id, $user, $phone, $company_id,$checkCard)
    {
        /* $orderings = self::where('company_id', $company_id)
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
             ->select()->toArray();*/


        $sql = self::getSqlForStaffsBalance($company_id);
        if ($checkCard) {
            $fields = 'a.username,a.code,a.card_num,a.phone,a.department,sum(a.money) as balance';
        } else {
            $fields = 'a.username,a.code,a.phone,a.department,sum(a.money) as balance';
        }
        $orderings = Db::table($sql . 'a')
            ->where(function ($query) use ($department_id) {
                if (!empty($department_id)) {
                    $query->where('a.department_id', $department_id);
                }
            })
            ->where(function ($query) use ($phone) {
                if (!empty($phone)) {
                    $query->where('a.phone', $phone);
                }
            })
            ->where(function ($query) use ($user) {
                if (!empty($user)) {
                    $query->where('a.username|a.code|a.card_num', 'like', '%' . $user . '%');
                }
            })
            ->field($fields)
            ->group('a.staff_id')
            ->order('a.staff_id')
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

    public static function userBalance2($staffId)
    {
        $sql = Db::table('canteen_order_t')
            ->field('sum(0-money-sub_money-delivery_fee) as money')
            ->where('staff_id', $staffId)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->where('pay', PayEnum::PAY_SUCCESS)
            ->unionAll(function ($query) use ($staffId) {
                $query->table("canteen_order_parent_t")
                    ->field('sum(0-money-delivery_fee) as money')
                    ->where('staff_id', $staffId)
                    ->where('state', CommonEnum::STATE_IS_OK)
                    ->where('pay', PayEnum::PAY_SUCCESS);
            })
            ->unionAll(function ($query) use ($staffId) {
                $query->table("canteen_shop_order_t")
                    ->field('sum(money) as money')
                    ->where('staff_id', $staffId)
                    ->where('state', CommonEnum::STATE_IS_OK)
                    ->where('pay', PayEnum::PAY_SUCCESS);
            })
            ->unionAll(function ($query) use ($staffId) {
                $query->table("canteen_recharge_supplement_t")
                    ->field('sum(money) as money')
                    ->where('staff_id', $staffId);

            })
            ->unionAll(function ($query) use ($staffId) {
                $query->table("canteen_pay_t")
                    ->field('sum(money) as money')
                    ->where('staff_id', $staffId)
                    ->where('status', PayEnum::PAY_SUCCESS)
                    ->where('refund', CommonEnum::STATE_IS_FAIL);

            })
            ->unionAll(function ($query) use ($staffId) {
                $query->table("canteen_recharge_cash_t")
                    ->field('sum(money) as money')
                    ->where('staff_id', $staffId)
                    ->where('state', CommonEnum::STATE_IS_OK);
            })
            ->buildSql();
        $balance = Db::table($sql . 'a')->sum('money');
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
        $sql = self::getSql($staff_id);
        $balance = Db::table($sql . 'a')
            ->select()
            ->toArray();
        return $balance;
    }

    public static function userFixedBalance($staff_id)
    {
        $sql = Db::table('canteen_order_t')
            ->field('sum(money+sub_money+delivery_fee) as money')
            ->where('staff_id', $staff_id)
            ->where('used', CommonEnum::STATE_IS_FAIL)
            ->where('unused_handel', CommonEnum::STATE_IS_FAIL)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->where('pay', PayEnum::PAY_SUCCESS)
            ->unionAll(function ($query) use ($staff_id) {
                $query->table("canteen_order_parent_t")
                    ->field('sum(delivery_fee) as money')
                    ->where('staff_id', $staff_id)
                    ->where('type', OrderEnum::EAT_OUTSIDER)
                    ->where('used', CommonEnum::STATE_IS_FAIL)
                    ->where('state', CommonEnum::STATE_IS_OK)
                    ->where('pay', PayEnum::PAY_SUCCESS);
            })
            ->unionAll(function ($query) use ($staff_id) {
                $query->table("canteen_order_sub_t")
                    ->alias('a')
                    ->leftJoin('canteen_order_parent_t b', 'a.order_id = b.id')
                    ->field('sum(a.money+a.sub_money) as money')
                    ->where('b.staff_id', $staff_id)
                    ->where('a.used', CommonEnum::STATE_IS_FAIL)
                    ->where('a.unused_handel', CommonEnum::STATE_IS_FAIL)
                    ->where('b.state', CommonEnum::STATE_IS_OK)
                    ->where('b.pay', PayEnum::PAY_SUCCESS);
            })->buildSql();
        $balance = Db::table($sql . 'a')->sum('money');
        return $balance;
    }

    public static function balanceForOffLine($companyId)
    {
        $sql = self::getCompanySql($companyId);
        return Db::table($sql . 'a')
            ->field('staff_id,sum(money) as balance')
            ->group('staff_id')
            ->order('staff_id')
            ->select();
    }


}