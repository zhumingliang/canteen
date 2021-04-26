<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class PunishmentUpdateT extends Model
{
    public static function getPunishmentEditDetails($page, $size, $key, $company_id, $company_name,
                                                    $canteen_id, $time_begin, $time_end)
    {
        $time_end = addDay(1, $time_end);
        $list = CompanyStaffT::where('a.state', CommonEnum::STATE_IS_OK)
            ->alias('a')
            ->leftJoin('company_t b', 'a.company_id=b.id')
            ->leftJoin('canteen_canteen_t c', 'FIND_IN_SET(c.id,a.canteen_ids)')
            ->leftJoin('staff_type_t d', 'a.t_id=d.id')
            ->leftJoin('punishment_update_t e', 'e.staff_id=a.id')
            ->where('e.create_time', '>=', $time_begin)
            ->where('e.create_time', '<=', $time_end)
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
            })->where(function ($query) use ($canteen_id) {
                if ($canteen_id != 0) {
                    $query->where('c.id', $canteen_id);
                }
            })
            ->field('date_format(e.create_time ,\'%Y-%m-%d\' ) as date,b.id as company_id,b.name as company_name,a.canteen_ids,group_concat(c.name) as canteen_name,a.t_id,d.name as staff_type,a.id as staff_id,username,phone,e.old_state,e.new_state')
            ->group('a.id')
            ->paginate($size, false, ['page' => $page])->toArray();
        return $list;
    }

    public static function prefixExportPunishmentEditDetails($key, $company_id, $company_name,
                                                    $canteen_id, $time_begin, $time_end)
    {
        $time_end = addDay(1, $time_end);
        $list = CompanyStaffT::where('a.state', CommonEnum::STATE_IS_OK)
            ->alias('a')
            ->leftJoin('company_t b', 'a.company_id=b.id')
            ->leftJoin('canteen_canteen_t c', 'FIND_IN_SET(c.id,a.canteen_ids)')
            ->leftJoin('staff_type_t d', 'a.t_id=d.id')
            ->leftJoin('punishment_update_t e', 'e.staff_id=a.id')
            ->where('e.create_time', '>=', $time_begin)
            ->where('e.create_time', '<=', $time_end)
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
            })->where(function ($query) use ($canteen_id) {
                if ($canteen_id != 0) {
                    $query->where('c.id', $canteen_id);
                }
            })
            ->field('date_format(e.create_time ,\'%Y-%m-%d\' ) as date,b.name as company_name,group_concat(c.name) as canteen_name,d.name as staff_type,username,phone,e.old_state,e.new_state')
            ->group('a.id')
            ->select()->toArray();
        return $list;
    }
}