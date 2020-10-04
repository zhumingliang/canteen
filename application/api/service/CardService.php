<?php


namespace app\api\service;


use app\api\model\StaffCardV;

class CardService
{
    public function cardManager($name, $cardCode, $status, $page, $size)
    {
        $companyId = 74;//Token::getCurrentTokenVar('company_id');
        $staffs = StaffCardV::staffs($companyId, $name, $cardCode, $status, $page, $size);
        return $staffs;

    }

}