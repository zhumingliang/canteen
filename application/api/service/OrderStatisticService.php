<?php


namespace app\api\service;


use app\api\model\OrderStatisticV;

class OrderStatisticService
{
    public function statistic($time_begin, $time_end, $company_id, $canteen_id, $page, $size)
    {
        $list = OrderStatisticV::statistic($time_begin, $time_end, $company_id, $canteen_id, $page, $size);
        return $list;


    }

}