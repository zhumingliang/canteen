<?php
/**
 * Created by PhpStorm.
 * User: 明良
 * Date: 2019/9/17
 * Time: 9:12
 */

namespace app\api\model;


use app\lib\enum\CommonEnum;
use app\lib\enum\NoticeEnum;
use think\Model;

class NoticeT extends Model
{
    public static function adminNotices($u_id, $page, $size)
    {
        $notices = self::where('u_id', $u_id)
            ->where('type', NoticeEnum::NOTICE)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->field('id,title,content,author,create_time')
            ->paginate($size, false, ['page' => $page]);
        return $notices;
    }
}