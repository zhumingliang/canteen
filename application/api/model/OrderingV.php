<?php
/**
 * Created by PhpStorm.
 * User: 明良
 * Date: 2019/9/5
 * Time: 23:19
 */

namespace app\api\model;


use think\Model;

class OrderingV extends Model
{
    public static function getRecordForDayOrdering($u_id, $ordering_date, $dinner_id)
    {
        $record = self::where('u_id', $u_id)
            ->where('ordering_date', $ordering_date)
            ->where('d_id', $dinner_id)
            ->find();
        return $record;

    }

}