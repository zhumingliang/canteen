<?php
/**
 * Created by PhpStorm.
 * User: 明良
 * Date: 2019/9/18
 * Time: 1:05
 */

namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class NoticeUserV extends Model
{
    public function getTypeAttr($value)
    {
        $type = [1 => '公告', 2 => '消费信息'];
        return $type[$value];
    }

    public static function userNotices($s_id, $page, $size)
    {
        $notices = self::where('s_id', $s_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->hidden(['state'])
            ->paginate($size, false, ['page' => $page]);
        return $notices;
    }

}