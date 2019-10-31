<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class RechargeV extends Model
{
    public static function rechargeRecords($time_begin, $time_end,
                                           $page, $size, $type, $admin_id, $username, $company_id)
    {
        $time_end = addDay(1, $time_end);
        $orderings = self::where('company_id', $company_id)
            ->whereBetweenTime('create_time', $time_begin, $time_end)
            ->where(function ($query) use ($type) {
                if ($type != "all") {
                    $query->where('type', $type);
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
            ->hidden(['admin_id', 'company_id','state'])
            ->paginate($size, false, ['page' => $page]);
        return $orderings;
    }

}