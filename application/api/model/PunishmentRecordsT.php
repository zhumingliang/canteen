<?php


namespace app\api\model;
use think\Model;

class PunishmentRecordsT extends Model
{
    public function checkData($mealTimes,$canteen_id,$department_id,$staff_id)
    {
        $where=[];
        if ($canteen_id != '') {
            $where['canteen_id'] = $canteen_id;
        }
        if ($mealTimes != '') {
            $where['mealTimes'] = $mealTimes;
        }
        if ($department_id != '') {
            $where['department_id'] = $department_id;
        }
        if ($staff_id != '' ) {
            $where['staff_id'] = $staff_id;
        }
        return $where;
    }
    public  function punishStaff($company_id)
    {
        $punishStaff = self::alias('t1')
            ->leftJoin('canteen_company_staff_t t2', 't1.staff_id=t2.id')
            ->leftJoin('canteen_canteen_t t3', 't3.id=t1.canteen_id')
            ->leftJoin('canteen_dinner_t t4', 't1.dinner_id =t4.id')
            ->leftJoin('canteen_company_department_t t5', 't5.id=t1.department_id')
            ->where('company_id', $company_id)
            ->field(['t1.day','t3.name as canteen_name','t5.name as department_name','t2.username','t4.name as meal_name','t1.type','t1.money']);

        return $punishStaff;
    }

}