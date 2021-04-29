<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use app\lib\enum\PayEnum;
use think\Model;

class RechargeCashT extends Model
{
    //充值总金额
    public static function monthRechargeMoney($timeBegin, $timeEnd, $staffId)
    {
        $timeEnd = addDay(1, $timeEnd);
        $statistic = self::field("money")
            ->where('staff_id', $staffId)
            ->where('create_time', '>=', $timeBegin)
            ->where('create_time', '<=', $timeEnd)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->unionAll(function ($query) use ($timeBegin, $timeEnd, $staffId) {
                $query->table('canteen_pay_t')
                    ->field("money")
                    ->where('staff_id', $staffId)
                    ->where('create_time', '>=', $timeBegin)
                    ->where('create_time', '<=', $timeEnd)
                    ->where('state', CommonEnum::STATE_IS_OK)
                    ->where('refund', CommonEnum::STATE_IS_FAIL)
                    ->where('status', PayEnum::PAY_SUCCESS);
            })->select()->toArray();
        $monthRechargeMoney = array_sum(array_column($statistic, 'money'));
        return $monthRechargeMoney;
    }


    public
    static function outsiderMonthRechargeMoney($timeBegin, $timeEnd, $companyId, $phone)
    {
        $timeEnd = addDay(1, $timeEnd);
        $statistic = self::field("money")
            ->where('company_id', $companyId)
            ->where('phone', $phone)
            ->where('create_time', '>=', $timeBegin)
            ->where('create_time', '<=', $timeEnd)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->unionAll(function ($query) use ($timeBegin, $timeEnd, $companyId, $phone) {
                $query->table('canteen_pay_t')
                    ->field("money")
                    ->where('company_id', $companyId)
                    ->where('phone', $phone)
                    ->where('create_time', '>=', $timeBegin)
                    ->where('create_time', '<=', $timeEnd)
                    ->where('state', CommonEnum::STATE_IS_OK)
                    ->where('refund', CommonEnum::STATE_IS_FAIL)
                    ->where('status', PayEnum::PAY_SUCCESS);
            })->select()->toArray();
        $monthRechargeMoney = array_sum(array_column($statistic, 'money'));
        return $monthRechargeMoney;
    }


    //PC端充值记录统计
    public function rechargeTotal($begin_time,$end_time,$username,$department,$phone,$company_id,$time){
        $statistic=self::alias('a')->field('c.name as department,b.username,a.phone,a.money')
            ->leftJoin('canteen_company_staff_t b','a.staff_id = b.id')
            ->leftJoin('canteen_company_department_t c','b.d_id = c.id')
            ->where('a.state',CommonEnum::STATE_IS_OK)
            ->where('a.type',1)
            ->where('a.company_id',$company_id)
            ->where(function ($query) use ($department,$phone,$username){
                if(!empty($department)){
                    $query->where('c.name','like','%'. $department. '%');
                }
                if(!empty($phone)){
                    $query->where('a.phone',$phone);
                }
                if(!empty($username)){
                    $query->where('b.username','like','%' . $username . '%');
                }
            })
            ->whereTime('a.create_time','between',[$begin_time,date("Y-m-d",strtotime("$end_time +1 day"))])
            ->unionAll(function ($query) use ($begin_time,$end_time,$username,$department,$phone,$company_id){
                $query->table('canteen_pay_t')
                    ->alias('a')
                    ->field('c.name as department,b.username,a.phone,a.money')
                    ->leftJoin('canteen_company_staff_t b','a.staff_id = b.id')
                    ->leftJoin('canteen_company_department_t c','b.d_id = c.id')
                    ->where('a.state',CommonEnum::STATE_IS_OK)
                    ->where('a.type','recharge')
                    ->where('a.refund',2)
                    ->where('a.company_id',$company_id)
                    ->where(function ($query) use ($department,$phone,$username){
                        if(!empty($department)){
                            $query->where('c.name','like','%'. $department .'%');
                        }
                        if(!empty($phone)){
                            $query->where('a.phone',$phone);
                        }
                        if(!empty($username)){
                            $query->where('b.username','like','%' . $username . '%');
                        }
                    })
                    ->whereTime('a.create_time','between',[$begin_time,date("Y-m-d",strtotime("$end_time +1 day"))]);
            })->select()->toArray();
        return $statistic;
    }

}