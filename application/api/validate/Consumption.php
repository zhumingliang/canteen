<?php


namespace app\api\validate;


class Consumption extends BaseValidate
{
    protected $rule = [
        'type' => 'require|isNotEmpty',
        'code' => 'require|isNotEmpty',
        'face_id' => 'require|isNotEmpty',
        'face_time' => 'require|isNotEmpty',
        'phone' => 'require|isMobile',
    ];

    protected $scene = [
        'face' => ['face_id', 'face_time','phone'],
        'staff' => ['type', 'code'],
        'managerCompanies' => ['name']
    ];

}