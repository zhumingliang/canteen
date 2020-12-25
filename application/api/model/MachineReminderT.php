<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class MachineReminderT extends Model
{
    public static function reminders($machineId)
    {
        return self::where('machine_id', $machineId)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->select()->toArray();
    }

}