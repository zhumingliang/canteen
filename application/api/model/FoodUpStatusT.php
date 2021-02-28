<?php


namespace app\api\model;


use think\Model;

class FoodUpStatusT extends Model
{
    public static function saveInfo($dinnerId, $day, $status)
    {
        $data = [
            'dinner_id' => $dinnerId,
            'day' => $day,
            $status
        ];
        return self::create($data);

    }

    public static function info($dinnerId, $day)
    {
        return self::where('dinner_id', $dinnerId)
            ->where('day', $day)
            ->find();
    }

}