<?php


namespace app\api\service;


use app\api\model\OrderT;
use app\lib\enum\CommonEnum;

class TakeoutService
{

    public function receiveOrder($order_id)
    {
        $order_ids = explode(',', $order_id);
        $dataList = [];
        foreach ($order_ids as $k => $v) {
            array_push($dataList, [
                'id' => $v,
                'receive' => CommonEnum::STATE_IS_OK
            ]);
        }
        (new OrderT())->saveAll($dataList);
    }
}