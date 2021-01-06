<?php


namespace app\api\model;


use think\Model;

class NextmonthPayT extends Model
{
    public function userList($time_begin, $time_end, $company_id, $department_id, $status,
                             $pay_method, $username, $phone, $page, $size)
    {
        $list = self::where(function ($query) use ($company_id) {
            if ($company_id != 0) {
                $query->where('company_id', $company_id);
            }
        })->where(function ($query) use ($department_id, $username, $phone, $pay_method) {
            if ($department_id != 0) {
                $query->where('department_id', $department_id);
            }
            if ($pay_method != 0) {
                $query->where('pay_method', $pay_method);
            }
            if (!empty($phone)) {
                $query->where('phone', $phone);
            }
            if (!empty($username)) {
                $query->where('username', 'like', '%' . $username . '%');
            }

        })->where(function ($query) use ($status) {
            if ($status != 0) {
                $query->where('state', $status);
            }
        })
            ->where('pay_date', '>=', $time_begin)
            ->where('pay_date', '<=', $time_end)
            ->field('company_id,pay_date,department,username,staff_id,phone,sum(order_money) as pay_money,state,pay_time,pay_method')
            ->group('staff_id,pay_date')
            ->paginate($size, false, ['page' => $page])->toArray();
        return $list;
    }

    public function dinnerStatistic($time_begin, $time_end, $company_id, $department_id, $status,
                                    $pay_method, $username, $phone)
    {
        $list = self::where(function ($query) use ($company_id) {
            if ($company_id != 0) {
                $query->where('company_id', $company_id);
            }
        })->where(function ($query) use ($department_id, $username, $phone, $pay_method) {
            if ($department_id != 0) {
                $query->where('department_id', $department_id);
            }
            if ($pay_method != 0) {
                $query->where('pay_method', $pay_method);
            }
            if (!empty($phone)) {
                $query->where('phone', $phone);
            }
            if (!empty($username)) {
                $query->where('username', 'like', '%' . $username . '%');
            }

        })->where(function ($query) use ($status) {
            if ($status != 0) {
                $query->where('state', $status);
            }
        })
            ->where('pay_date', '>=', $time_begin)
            ->where('pay_date', '<=', $time_end)
            ->field('staff_id,username,dinner_id,dinner,sum(order_count) as order_count,sum(order_money) as order_money,pay_date')
            ->group('staff_id,pay_date,dinner')
            ->select()->toArray();
        return $list;
    }

    public function getNoPayStaffs($c_id, $orderConsumptionDate)
    {
        $list = self::where('company_id', $c_id)
            ->where('state', 2)
            ->where('pay_date', $orderConsumptionDate)
            ->field('staff_id,phone,username,sum(order_money) as pay_money,pay_date')
            ->group('staff_id')
            ->select()->toArray();
        return $list;
    }

    //导出
    public function consumerList($time_begin, $time_end, $company_id, $department_id, $status,
                                 $pay_method, $username, $phone)
    {
        $list = self::where(function ($query) use ($company_id) {
            if ($company_id != 0) {
                $query->where('company_id', $company_id);
            }
        })->where(function ($query) use ($department_id, $username, $phone, $pay_method) {
            if ($department_id != 0) {
                $query->where('department_id', $department_id);
            }
            if ($pay_method != 0) {
                $query->where('pay_method', $pay_method);
            }
            if (!empty($phone)) {
                $query->where('phone', $phone);
            }
            if (!empty($username)) {
                $query->where('username', 'like', '%' . $username . '%');
            }

        })->where(function ($query) use ($status) {
            if ($status != 0) {
                $query->where('state', $status);
            }
        })
            ->where('pay_date', '>=', $time_begin)
            ->where('pay_date', '<=', $time_end)
            ->field('pay_date,department,username,staff_id,phone,sum(order_money) as pay_money, state,pay_time, pay_method,pay_remark')
            ->group('staff_id,pay_date')
            ->select();
        return $list;
    }
}