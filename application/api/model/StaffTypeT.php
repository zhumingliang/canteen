<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class StaffTypeT extends Model
{
    public static function roleTypes($page, $size, $key)
    {
        $types = self::where('state', CommonEnum::STATE_IS_OK)
            ->where(function ($query) use ($key) {
                if (strlen($key)) {
                    $query->where('name', 'like', '%' . $key . '%');
                }
            })
            ->hidden(['update_time','state'])
            ->paginate($size, false, ['page' => $page]);
        return $types;
    }

}