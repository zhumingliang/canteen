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
    public static function getRecordForDayOrdering($u_id, $ordering_date, $dinner)
    {
        $record = self::where('u_id', $u_id)
            ->where('ordering_date', $ordering_date)
            ->where('dinner', $dinner)
            ->find();
        return $record;
    }

    public static function userOrdering($u_id)
    {
        $orderings = self::where('u_id', $u_id)
            ->whereTime('ordering_date', '>=', date('Y-m-d'))
            ->select();
        return $orderings;
    }

}