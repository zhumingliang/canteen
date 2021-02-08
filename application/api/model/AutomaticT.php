<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;
use think\Request;

class AutomaticT extends Model
{

    public function foods()
    {
        return $this->hasMany('AutomaticFoodT', 'auto_id', 'id');
    }

    public static function checkExits($dinnerId, $repeatWeek)
    {
        $auto = self::where('dinner_id', $dinnerId)
            ->where('repeat_week', $repeatWeek)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->find();
        return $auto;

    }

    public static function info($canteenId)
    {
        $info = self::where('canteen_id', $canteenId)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->with([
                'foods' => function ($query) {
                    $query->where('state', CommonEnum::STATE_IS_OK);
                }
            ])
            ->hidden(['create_time', 'update_time'])
            ->select();
        return $info;

    }


    public static function infoToDinner($canteenId, $dinnerId, $dayWeek)
    {
        $info = self::where('canteen_id', $canteenId)
            ->where('dinner_id', $dinnerId)
            ->where('repeat_week', $dayWeek)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->with([
                'foods' => function ($query) {
                    $query->where('state', CommonEnum::STATE_IS_OK);
                }
            ])
            //->hidden(['create_time', 'update_time'])
            ->find();
        return $info;

    }


    public static function infoToDinner2($canteenId, $dinnerId, $dayWeek)
    {
        $info = self::where('canteen_id', $canteenId)
            ->where('dinner_id', $dinnerId)
            ->where('repeat_week', $dayWeek)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->with([
                'foods' => function ($query) {
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->with([
                            'food' => function ($query2) {
                                $query2->field('id,name,img_url');
                            }
                        ]);
                }
            ])
            //->hidden(['create_time', 'update_time'])
            ->find();
        return $info;

    }


}