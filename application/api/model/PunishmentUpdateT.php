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
        $list = self::alias('a')
            ->where('a.create_time', '>=', $time_begin)
            ->where('a.create_time', '<=', $time_end)
            ->leftJoin('company_staff_t b', 'a.staff_id=b.id')
            ->leftJoin('company_t c', 'b.company_id=c.id')
            ->leftJoin('canteen_canteen_t d', 'FIND_IN_SET(d.id,b.canteen_ids)')
            ->leftJoin('staff_type_t e', 'b.t_id=e.id')
            ->where(function ($query) use ($key) {
                if ($key) {
                    $query->whereLike('username|phone', "%$key%");
                }
            })->where(function ($query) use ($company_id, $company_name) {
                if ($company_id != 0) {
                    $query->where('c.id', $company_id);
                }
                if (strlen($company_name)) {
                    $query->whereLike('c.name', "%$company_name%");
                }
            })->where(function ($query) use ($canteen_id) {
                if ($canteen_id != 0) {
                    $query->where('d.id', $canteen_id);
                }
            })
            ->field('date_format(a.create_time ,\'%Y-%m-%d\' ) as date,c.id as company_id,c.name as company_name,b.canteen_ids,group_concat(d.name) as canteen_name,b.t_id,e.name as staff_type,b.id as staff_id,username,phone,a.old_state,a.new_state')
            ->group('a.id')
            ->paginate($size, false, ['page' => $page])->toArray();
        return $list;
    }

    public static function prefixExportPunishmentEditDetails($key, $company_id, $company_name,
                                                             $canteen_id, $time_begin, $time_end)
    {
        $time_end = addDay(1, $time_end);
        $list = self::alias('a')
            ->where('a.create_time', '>=', $time_begin)
            ->where('a.create_time', '<=', $time_end)
            ->leftJoin('company_staff_t b', 'a.staff_id=b.id')
            ->leftJoin('company_t c', 'b.company_id=c.id')
            ->leftJoin('canteen_canteen_t d', 'FIND_IN_SET(d.id,b.canteen_ids)')
            ->leftJoin('staff_type_t e', 'b.t_id=e.id')
            ->where(function ($query) use ($key) {
                if ($key) {
                    $query->whereLike('username|phone', "%$key%");
                }
            })->where(function ($query) use ($company_id, $company_name) {
                if ($company_id != 0) {
                    $query->where('c.id', $company_id);
                }
                if (strlen($company_name)) {
                    $query->whereLike('c.name', "%$company_name%");
                }
            })->where(function ($query) use ($canteen_id) {
                if ($canteen_id != 0) {
                    $query->where('d.id', $canteen_id);
                }
            })
            ->field('date_format(a.create_time ,\'%Y-%m-%d\' ) as date,c.id as company_id,c.name as company_name,b.canteen_ids,group_concat(d.name) as canteen_name,b.t_id,e.name as staff_type,b.id as staff_id,username,phone,a.old_state,a.new_state')
            ->group('a.id')
            ->select()->toArray();
        return $list;
    }
}