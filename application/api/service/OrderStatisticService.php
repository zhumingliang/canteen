<?php


namespace app\api\service;


use app\api\model\OrderSettlementV;
use app\api\model\OrderStatisticV;
use app\api\model\OrderTakeoutStatisticV;
use app\lib\enum\CommonEnum;

class OrderStatisticService
{
    public function statistic($time_begin, $time_end, $company_ids, $canteen_id, $page, $size)
    {
        $list = OrderStatisticV::statistic($time_begin, $time_end, $company_ids, $canteen_id, $page, $size);
        return $list;

    }

    public function orderStatisticDetail($company_ids, $time_begin,
                                         $time_end, $page, $size, $name,
                                         $phone, $canteen_id, $department_id,
                                         $dinner_id)
    {
        $list = OrderStatisticV::detail($company_ids, $time_begin,
            $time_end, $page, $size, $name,
            $phone, $canteen_id, $department_id,
            $dinner_id);
        return $list;
    }

    public function orderSettlement($page, $size,
                                    $name, $phone, $canteen_id, $department_id, $dinner_id,
                                    $consumption_type, $time_begin, $time_end, $company_ids)
    {
        $records = OrderSettlementV::orderSettlement($page, $size,
            $name, $phone, $canteen_id, $department_id, $dinner_id,
            $consumption_type, $time_begin, $time_end, $company_ids);
        $records['data'] = $this->prefixSettlementConsumptionType($records['data']);
        return $records;
    }

    private function prefixSettlementConsumptionType($data)
    {
        if (count($data)) {
            foreach ($data as $k => $v) {
                if ($v['booking'] == CommonEnum::STATE_IS_OK) {
                    $data[$k]['consumption_type'] = $v['used'] == CommonEnum::STATE_IS_OK ? 1 : 2;
                } else {
                    $data[$k]['consumption_type'] = 3;
                }
            }

        }
        return $data;
    }

    public function takeoutStatistic($page, $size,
                                     $ordering_date, $company_ids,
                                     $canteen_id, $dinner_id)
    {
        $records = OrderTakeoutStatisticV::statistic($page, $size,
            $ordering_date, $company_ids, $canteen_id, $dinner_id);
        return $records;
    }

}