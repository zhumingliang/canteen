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


    public function config()
    {
        return $this->hasMany('MachineConfigT','machine_id','id');
    }

    public function face()
    {
        return $this->hasMany('FaceConfigT','machine_id','id');
    }


    public static function machineConfig($company_id,$page,$size,$belong_id,$type,$name,$code)
    {
        $list = self::where(function ($query) use ($company_id) {
            if (!empty($company_id)) {
                $query->where('company_id', $company_id);
            }
        })->where(function ($query) use ($belong_id, $type, $name, $code) {
            if ($belong_id != 0) {
                $query->where('belong_id', $belong_id);
            }
            if ($type != 0) {
                $query->where('type', $type);
            }
            if (!empty($name)) {
                $query->where('name', $name);
            }
            if (!empty($code)) {
                $query->where('code',$code);
            }

        })
            ->field('id,company_id,belong_id,type,name,code')
            ->with(['config' => function ($query) {
                $query->field('id,machine_id,deduction_success_type,deduction_success_msg,deduction_fail_type,deduction_fail_msg,face_fail_type,face_fail_msg,face_fail_content,deduction_success_sub,deduction_ail_sub');
            },
                'face' => function ($query) {
                    $query->field('id,machine_id,distance,living,accuracy,times');
                }
            ])
            ->paginate($size, false, ['page' => $page]);
        return $list;
    }



}