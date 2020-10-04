<?php


namespace app\api\model;


use think\Model;

class StaffCardV extends Model
{

    public static function staffs($company_id, $name, $card_code, $status, $page, $size)
    {
        $staffs = self::where('company_id', $company_id)
            ->where(function ($query) use ($name) {
                if (strlen($name)) {
                    $query->where('name', $name);
                }
            })
            ->where(function ($query) use ($card_code) {
                if (strlen($card_code)) {
                    $query->where('card_code', $card_code);
                }
            })
            ->where(function ($query) use ($status) {
                if ($status) {
                    if ($status == 4) {
                        //未绑定
                        $query->where('card_code', "=", 0);
                    } else {
                        $query->where('card_code', "=", $status);
                    }
                }

            })
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page]);
        return $staffs;

    }
}