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
            ->leftJoin('canteen_staff_canteen_t d', 'd.staff_id=b.id')
            ->leftJoin('canteen_canteen_t e', 'd.canteen_id=e.id')
            ->leftJoin('staff_type_t f', 'b.t_id=f.id')
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
                    $query->where('e.id', $canteen_id);
                }
            })
            ->where('d.state',CommonEnum::STATE_IS_OK)
            ->field('a.create_time as date,c.id as company_id,c.name as company_name,group_concat(e.id order by e.id) as canteen_ids,group_concat(e.name order by e.name) as canteen_name,b.t_id,f.name as staff_type,b.id as staff_id,username,phone,a.old_state,a.new_state')
            ->order('a.create_time DESC')
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
            ->leftJoin('canteen_staff_canteen_t d', 'd.staff_id=b.id')
            ->leftJoin('canteen_canteen_t e', 'd.canteen_id=e.id')
            ->leftJoin('staff_type_t f', 'b.t_id=f.id')
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
                    $query->where('e.id', $canteen_id);
                }
            })
            ->where('d.state',CommonEnum::STATE_IS_OK)
            ->field('a.create_time as date,c.id as company_id,c.name as company_name,group_concat(e.id order by e.id) as canteen_ids,group_concat(e.name order by e.name) as canteen_name,b.t_id,f.name as staff_type,b.id as staff_id,username,phone,a.old_state,a.new_state')
            ->order('a.create_time DESC')
            ->group('a.id')
            ->select()->toArray();
        return $list;
    }
}