<?php


namespace app\api\model;


use think\Model;

class OrderUnusedV extends Model
{
    public function orders($consumption_time)
    {

        $statistic = $this->where('ordering_date', '<', $consumption_time)
            ->select();
        return $statistic;
    }
}