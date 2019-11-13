<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class StaffCanteenV extends Model
{
    public function dinnerStatistic()
    {
        return $this->hasMany('OrderConsumptionV', 'staff_id', 'staff_id');
    }

    public static function getStaffsForStatistic($company_id, $canteen_id, $page, $size, $status, $department_id,
                                                 $username, $staff_type_id, $time_begin,
                                                 $time_end)
    {
        return self::where(function ($query) use ($company_id, $canteen_id) {
            if (!empty($canteen_id)) {
                $query->where('canteen_id', $canteen_id);
            } else {
                if (strpos($company_id, ',') !== false) {
                    $query->whereIn('company_id', $company_id);
                } else {
                    $query->where('company_id', $company_id);
                }
            }
        })->where(function ($query) use ($department_id,
            $username, $staff_type_id
        ) {
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
            ->with([
                'dinnerStatistic' => function ($query) use (
                    $status, $department_id,
                    $username, $staff_type_id, $time_begin,
                    $time_end
                ) {
                    $query->whereBetweenTime('consumption_date', $time_begin, $time_end)
                        ->where(function ($query2) use (
                            $status, $department_id,
                            $username, $staff_type_id
                        ) {
                            if (!empty($status)) {
                                $query2->where('status', $status);
                            }
                            if (!empty($department_id)) {
                                $query2->where('department_id', $department_id);
                            }
                            if (!empty($username)) {
                                $query2->where('username', $username);
                            }
                            if (!empty($status)) {
                                $query2->where('staff_type_id', $staff_type_id);
                            }

                        })
                        ->field('staff_id,dinner_id,dinner,sum(order_count) as order_count,sum(order_money) as order_money')
                        ->group('status,dinner');
                }
            ])
            ->field('staff_id,username as statistic,username,department')
            ->where('state', CommonEnum::STATE_IS_OK)
            ->paginate($size, false, ['page' => $page]);

    }

}