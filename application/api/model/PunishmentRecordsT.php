<?php


namespace app\api\model;
use think\Model;

class PunishmentRecordsT extends Model
{
    public function checkData($company_id,$meal,$canteen_id,$department_id,$staff_name)
    {
        $where=[];
        if ($canteen_id != 0) {
            $where['t1.canteen_id'] = $canteen_id;
        }
        if ($meal != 0) {
            $where['t1.dinner_id'] = $meal;
        }
        if ($department_id != 0) {
            $where['t1.department_id'] = $department_id;
        }
        if ($staff_name != '' ) {
            $staff_id=CompanyStaffT::where(['username'=>$staff_name,'company_id'=>$company_id])->field('id')->find();
            $where['t1.staff_id'] = $staff_id['id'];
        }
        if ($company_id!= 0){
            $where['t1.company_id']=$company_id;
        }
        return $where;
    }
    public  function punishStaff()
    {
        $punishStaff = self::alias('t1')
            ->leftJoin('canteen_canteen_t t3', 't3.id=t1.canteen_id')
            ->leftJoin('canteen_dinner_t t4', 't1.dinner_id =t4.id')
            ->leftJoin('canteen_company_department_t t5', 't5.id=t1.department_id')
            ->field([ 't1.staff_id','t1.day', 't3.name as canteen_name', 't5.name as department_name', 't4.name as meal_name', 't1.type', 't1.money']);

        return $punishStaff;
    }

}