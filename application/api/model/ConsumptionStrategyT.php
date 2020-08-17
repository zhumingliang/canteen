<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class ConsumptionStrategyT extends Model
{
    public function getDetailAttr($value)
    {
        if (strlen($value)) {
            return \GuzzleHttp\json_decode($value, true);

        }
    }

    public function dinner()
    {
        return $this->belongsTo('DinnerT', 'd_id', 'id');

    }

    public function role()
    {
        return $this->belongsTo('StaffTypeT', 't_id', 'id');

    }

    public function canteen()
    {
        return $this->belongsTo('CanteenT', 'c_id', 'id');

    }

    public static function info($c_id)
    {
        $info = self::where('c_id', $c_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->with([
                'dinner' => function ($query) {
                    $query->field('id,name,fixed');
                },
                'role' => function ($query) {
                    $query->field('id,name');
                },
                'canteen' => function ($query) {
                    $query->field('id,name');
                }
            ])
            ->hidden(['create_time', 'update_time', 'state', 'd_id', 't_id', 'c_id'])
            ->order('create_time desc')
            ->select();
        return $info;

    }

    public static function getStaffConsumptionStrategy($c_id, $d_id, $t_id)
    {
        $info = self::where('c_id', $c_id)
            ->where('d_id', $d_id)
            ->where('t_id', $t_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->hidden(['create_time', 'update_time', 'state', 'd_id', 't_id', 'c_id'])
            ->order('create_time desc')
            ->find();
        return $info;

    }

    public static function getDinnerConsumptionStrategy($c_id, $d_id)
    {
        $info = self::where('c_id', $c_id)
            ->where('d_id', $d_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->hidden(['create_time', 'update_time', 'state', 'd_id', 't_id', 'c_id'])
            ->order('create_time desc')
            ->select();
        return $info;

    }

    public static function staffStrategy($c_id, $t_id)
    {
        $info = self::where('c_id', $c_id)
            ->where('t_id', $t_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->field('id,d_id,ordered_count')
            ->select();
        return $info;
    }

    public static function staffStrategies($canteen_id)
    {
        $strategies = self::where('c_id', $canteen_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->field('t_id')
            ->group('t_id')
            ->select();
        return $strategies;


    }

}