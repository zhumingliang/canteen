<?php
/**
 * Created by PhpStorm.
 * User: 明良
 * Date: 2019/9/9
 * Time: 10:50
 */

namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class OrderDetailT extends Model
{
    public static function orderDetail($o_id, $menu_id)
    {
        $detail = self::where('o_id', $o_id)
            ->where('m_id', $menu_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->select()->toArray();
        return $detail;
    }

    public static function detail($order_id)
    {
        $detail = self::where('o_id', $order_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->select()->toArray();
        return $detail;
    }

}