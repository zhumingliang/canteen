<?php
/**
 * Created by PhpStorm.
 * User: 明良
 * Date: 2019/9/10
 * Time: 1:51
 */

namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class UserAddressT extends Model
{
    public static function userAddress($u_id){
       return  self::where('u_id', $u_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->hidden(['create_time', 'update_time', 'state'])
            ->select();
    }

}