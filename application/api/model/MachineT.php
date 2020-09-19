<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class MachineT extends Model
{
    public static function companyMachines($company_id, $page, $size)
    {
        $machines = self::where('company_id', $company_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->field('id,machine_type,name,code,number,face_id')
            ->paginate($size, false, ['page' => $page])->toArray();
        return $machines;
    }

    public static function machines($page, $size, $belong_id, $machine_type)
    {
        $machines = self::where('belong_id', $belong_id)
            ->where('machine_type', $machine_type)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->field('id,machine_type,name,code,number,out,sort_code,face_id')
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
}