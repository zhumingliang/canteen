<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Db;
use think\Model;

class MachineT extends Model
{
    public function reminder()
    {
        return $this->hasMany('MachineReminderT', 'machine_id', 'id');
    }

    public static function companyMachines($company_id, $page, $size)
    {
        $machines = self::where('company_id', $company_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->field('id,machine_type,name,code,number,face_id,remind')
            ->paginate($size, false, ['page' => $page])->toArray();
        return $machines;
    }

    public static function machines($page, $size, $belong_id, $machine_type)
    {
        $machines = self::where('belong_id', $belong_id)
            ->where('machine_type', $machine_type)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->with([
                'reminder' => function ($query) {
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->field('id,staff_id,machine_id,openid,username');
                }
            ])
            ->field('id,machine_type,name,code,number,out,sort_code,face_id,remind')
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page])->toArray();
        return $machines;
    }

    public static function getSortMachine($canteenID, $outsider)
    {
        $machine = self::where('belong_id', $canteenID)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->where('sort_code', CommonEnum::STATE_IS_OK)
            ->where('out', $outsider)
            ->find();
        return $machine;

    }


    public static function companyMachinesForOffline($company_id)
    {
        $machines = self::where(function ($query) use ($company_id) {
            if ($company_id) {
                $query->where('company_id', $company_id);
            }
        })
            ->where('machine_type','canteen')
            ->where('state', CommonEnum::STATE_IS_OK)
            ->field('id,machine_type,name')
            ->select();
        return $machines;
    }


}