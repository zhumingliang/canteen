<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class RechargeV extends Model
{
    public function getTypeAttr($value, $data)
    {
        $types = ['cash' => '现金', '1' => '微信', '2' => '农行'];
        if (key_exists($data['type'], $types)) {
            return $types[$data['type']];
        }
        return '其他';
    }

    /*public function getMoneyAttr($value, $data)
    {
        return abs($value);
    }*/

    public static function rechargeRecords($time_begin, $time_end,
                                           $page, $size, $type, $admin_id, $username, $company_id, $department_id, $money_type)
    {
        $time_end = addDay(1, $time_end);
        $orderings = self::where('company_id', $company_id)
            ->where('create_time', '>=', $time_begin)
            ->where('create_time', '<=', $time_end)
            ->where(function ($query) use ($department_id) {
                if ($department_id) {
                    $query->where('department_id', $department_id);
                }
            })->where(function ($query) use ($type) {
                if ($type != "all") {
                    $query->where('type', $type);
                }
            })
            ->where(function ($query) use ($money_type) {
                if ($money_type) {
                    $query->where('money_type', $money_type);
                }
            })
            ->where(function ($query) use ($admin_id) {
                if (!empty($admin_id)) {
                    $query->where('admin_id', $admin_id);
                }
            })
            ->where(function ($query) use ($username) {
                if (!empty($username)) {
                    $query->where('username', $username);
                }
            })
            ->where('state', CommonEnum::STATE_IS_OK)
            ->hidden(['admin_id', 'company_id', 'state'])
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page])->toArray();
        return $orderings;
    }

    public static function rechargeStatistic($time_begin, $time_end, $type, $admin_id, $username, $company_id, $department_id, $money_type)
    {
        $time_end = addDay(1, $time_end);
        $money = self::where('company_id', $company_id)
            ->where('create_time', '>=', $time_begin)
            ->where('create_time', '<=', $time_end)
            ->where(function ($query) use ($department_id) {
                if ($department_id) {
                    $query->where('department_id', $department_id);
                }
            })->where(function ($query) use ($type) {
                if ($type != "all") {
                    $query->where('type', $type);
                }
            })
            ->where(function ($query) use ($money_type) {
                if ($money_type) {
                    $query->where('money_type', $money_type);
                }
            })
            ->where(function ($query) use ($admin_id) {
                if (!empty($admin_id)) {
                    $query->where('admin_id', $admin_id);
                }
            })
            ->where(function ($query) use ($username) {
                if (!empty($username)) {
                    $query->where('username', $username);
                }
            })
            ->sum('money');
        return $money;
    }

    public static function exportRechargeRecords($time_begin, $time_end, $type, $admin_id, $username, $company_id, $department_id, $money_type)
    {
        $time_end = addDay(1, $time_end);
        $orderings = self::where('company_id', $company_id)
            ->whereBetweenTime('create_time', $time_begin, $time_end)
            ->where(function ($query) use ($type) {
                if ($type != "all") {
                    $query->where('type', $type);
                }
            })->where(function ($query) use ($department_id) {
                if ($department_id) {
                    $query->where('department_id', $department_id);
                }
            })->where(function ($query) use ($money_type) {
                if ($money_type) {
                    $query->where('money_type', $money_type);
                }
            })
            ->where(function ($query) use ($admin_id) {
                if (!empty($admin_id)) {
                    $query->where('admin_id', $admin_id);
                }
            })
            ->where(function ($query) use ($username) {
                if (!empty($username)) {
                    $query->where('username', $username);
                }
            })
            ->where('state', CommonEnum::STATE_IS_OK)
            ->field('create_time,department,username,phone,money,if(money_type=1,"充值","退款") as money_type,type,admin,remark')
            ->order('create_time desc')
            ->select()->toArray();
        return $orderings;
    }

    public static function exportRechargeRecordsWithAccount($time_begin, $time_end, $type, $admin_id, $username, $company_id, $department_id, $money_type)
    {
        $time_end = addDay(1, $time_end);
        $orderings = self::where('company_id', $company_id)
            ->whereBetweenTime('create_time', $time_begin, $time_end)
            ->where(function ($query) use ($type) {
                if ($type != "all") {
                    $query->where('type', $type);
                }
            })->where(function ($query) use ($department_id) {
                if ($department_id) {
                    $query->where('department_id', $department_id);
                }
            })
            ->where(function ($query) use ($money_type) {
                if ($money_type) {
                    $query->where('money_type', $money_type);
                }
            })
            ->where(function ($query) use ($admin_id) {
                if (!empty($admin_id)) {
                    $query->where('admin_id', $admin_id);
                }
            })
            ->where(function ($query) use ($username) {
                if (!empty($username)) {
                    $query->where('username', $username);
                }
            })
            ->where('state', CommonEnum::STATE_IS_OK)
            ->field('create_time,department,username,phone,account,money,if(money_type=1,"充值","退款") as money_type,type,admin,remark')
            ->order('create_time desc')
            ->select()->toArray();
        return $orderings;
    }


}