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
        })->where(function ($query) use (
            $department_id,
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
            ->field('staff_id,username as statistic,username,department')
            ->where('state', CommonEnum::STATE_IS_OK)
            ->group('staff_id')
            ->paginate($size, false, ['page' => $page])->toArray();

    }

}