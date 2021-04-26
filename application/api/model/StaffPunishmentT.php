<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class StaffPunishmentT extends Model
{
    public static function getStaffWithPunishmentStatus($page, $size, $key, $company_id, $company_name, $status)
    {
        $list = CompanyStaffT::where('a.state', CommonEnum::STATE_IS_OK)
            ->alias('a')
            ->leftJoin('company_t b', 'a.company_id=b.id')
            ->leftJoin('canteen_canteen_t c', 'FIND_IN_SET(c.id,a.canteen_ids)')
            ->leftJoin('staff_type_t d', 'a.t_id=d.id')
            ->leftJoin('staff_punishment_t e', 'e.staff_id=a.id')
            ->where(function ($query) use ($key) {
                if ($key) {
                    $query->whereLike('username|phone', "%$key%");
                }
            })->where(function ($query) use ($company_id, $company_name) {
                if ($company_id != 0) {
                    $query->where('a.company_id', $company_id);
                }
                if (strlen($company_name)) {
                    $query->whereLike('b.name', "%$company_name%");
                }
            })->where(function ($query) use ($status) {
                if ($status != 0) {
                    if ($status == 1) {
                        $query->whereIn('a.status', "1,2");
                    } else {
                        $query->where('a.status', $status);
                    }
                }
            })
            ->field('b.id as company_id,b.name as company_name,a.canteen_ids,group_concat(c.name) as canteen_name,a.t_id,d.name as staff_type,a.id as staff_id,username,phone,status,e.no_meal,e.no_booking')
            ->group('a.id')
            ->paginate($size, false, ['page' => $page])->toArray();
        return $list;
    }

    public static function prefixExportPunishmentStaffInfo($key, $company_id, $company_name, $status){
        $list = CompanyStaffT::where('a.state', CommonEnum::STATE_IS_OK)
            ->alias('a')
            ->leftJoin('company_t b', 'a.company_id=b.id')
            ->leftJoin('canteen_canteen_t c', 'FIND_IN_SET(c.id,a.canteen_ids)')
            ->leftJoin('staff_type_t d', 'a.t_id=d.id')
            ->leftJoin('staff_punishment_t e', 'e.staff_id=a.id')
            ->where(function ($query) use ($key) {
                if ($key) {
                    $query->whereLike('username|phone', "%$key%");
                }
            })->where(function ($query) use ($company_id, $company_name) {
                if ($company_id != 0) {
                    $query->where('a.company_id', $company_id);
                }
                if (strlen($company_name)) {
                    $query->whereLike('b.name', "%$company_name%");
                }
            })->where(function ($query) use ($status) {
                if ($status != 0) {
                    if ($status == 1) {
                        $query->whereIn('a.status', "1,2");
                    } else {
                        $query->where('a.status', $status);
                    }
                }
            })
            ->field('b.name as company_name,group_concat(c.name) as canteen_name,d.name as staff_type,username,phone,status,e.no_meal,e.no_booking')
            ->group('a.id')
            ->select()->toArray();
        return $list;
    }

}