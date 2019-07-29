<?php


namespace app\api\service;


use app\api\model\AdminT;
use app\lib\enum\CommonEnum;
use app\lib\exception\SaveException;

class AdminService
{
    public function save($account, $passwd, $role, $grade, $c_id)
    {
        $data = [
            'account' => $account,
            'passwd' => sha1($passwd),
            'role' => $role,
            'grade' => $grade,
            'state' => CommonEnum::STATE_IS_OK,
            'c_id' => $c_id
        ];

        $admin = AdminT::create($data);
        if (!$admin) {
            throw new SaveException();
        }

    }




}