<?php


namespace app\api\model;


use think\Model;

class OfflineReceiveT extends Model
{
    public static function records($company_id, $day, $machine_id, $status)
    {

        return self::where('day', $day)
            ->where(function ($query) use ($company_id) {
                if ($company_id) {
                    $query->where('company_id', $company_id);
                }

            })->where(function ($query) use ($machine_id) {
                if ($machine_id) {
                    $query->where('machine_id', $machine_id);
                }
            })->where(function ($query) use ($status) {
                 if ($status < 2) {
                     $query->where('status', $status);
                 }
            })
            ->order('status desc')
            ->field('company_id,company,machine_id,name,day,sum(state) as status')
            ->group('day,machine_id')
            ->select();
    }

    public static function detail($machineId, $day)
    {
        return self::where('machine_id', $machineId)
            ->where('day', $day)
            ->order('state desc')
            ->field('create_time,state')
            ->select();
    }

}