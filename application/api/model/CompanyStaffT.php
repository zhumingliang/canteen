<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class CompanyStaffT extends Model
{
    public function qrcode()
    {
        return $this->hasOne('StaffQrcodeT', 's_id', 'id');
    }

    public static function staff($c_id, $phone)
    {
        return self::where('phone', $phone)->where('c_id', $c_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->with('qrcode')
            ->find();
    }
}