<?php


namespace app\api\model;


use think\Model;

class OrderConsumptionV extends Model
{
    public static function consumptionStatisticByDepartment($page, $size, $canteen_id, $status, $department_id,
                                                            $username, $staff_type_id, $time_begin,
                                                            $time_end, $company_id)
    {
        $time_end = addDay(1, $time_end);
        $statistic = self::where(function ($query) use ($company_id, $canteen_id) {
            if (!empty($canteen_id)) {
                $query->where('canteen_id', $canteen_id);
            } else {
                if (strpos($company_id, ',') !== false) {
                    $query->whereIn('company_id', $company_id);
                } else {
                    $query->where('company_id', $company_id);
                }
            }
        })
            ->whereBetweenTime('ordering_date', $time_begin, $time_end)
            ->where(function ($query) use (
                $status, $department_id,
                $username, $staff_type_id
            ) {
                if (!empty($status)) {
                    $query->where('status', $status);
                }
                if (!empty($department_id)) {
                    $query->where('department_id', $department_id);
                }
                if (!empty($username)) {
                    $query->where('username', $username);
                }
                if (!empty($status)) {
                    $query->where('staff_type_id', $staff_type_id);
                }

            })
            ->field('')
            ->group('department_id')
            ->select()
            ->toArray();
        return $statistic;
    }

}