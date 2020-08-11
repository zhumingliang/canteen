<?php


namespace app\api\model;


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
    {
        $balance = self::where('phone', $phone)
            ->where('company_id', $company_id)
            ->field('sum(money) as money,effective')
            ->group('effective')
            ->select()->toArray();
        return $balance;
    }

}