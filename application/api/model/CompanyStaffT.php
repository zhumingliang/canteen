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

    public static function departmentStaffs($d_ids)
    {
        $staffs = self::where('d_id', 'in', $d_ids)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->field('id')->select()->toArray();
        return $staffs;
    }

    public static function getStaffWithPhone($phone)
    {
        return self::where('phone', $phone)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->find();
    }
}