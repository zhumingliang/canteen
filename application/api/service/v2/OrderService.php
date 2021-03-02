<?php


namespace app\api\service\v2;


use app\api\service\Token;
use app\lib\enum\OrderEnum;
use app\lib\exception\ParameterException;

class OrderService
{
    public function getOrderMoney($params)
    {
        $canteenId = 1;// Token::getCurrentTokenVar('current_canteen_id');
        $phone = "18956225230";//Token::getCurrentTokenVar('phone');
        $companyId = 1;//Token::getCurrentTokenVar('current_company_id');
        $staffId = Token::getCurrentTokenVar('staff_id');
        $orderType = $params['type'];
        if (!empty($params['orders'])) {
            $orders = json_decode($params['orders'], true);
        } else {
            throw new ParameterException(['msg' => "订单参数异常"]);
        }
        //解析订单信息
        //生成预订单id
        $prepareId = QRcodeNUmber();
        $prepareOderList = [];
        $prepareOderFoodList = [];
        foreach ($orders as $k => $v) {
            $orderingDate = $v['ordering_date'];
            $dayOrders = $v['order'];
            if (count($dayOrders)) {
                foreach ($dayOrders as $k2 => $v2) {
                    $foods = $v2['detail'];
                    if (count($foods)) {
                        array_push($prepareOderList, [
                            'prepare_id' => $prepareId,
                            'ordering_date' => $orderingDate,
                            'company_id' => $companyId,
                            'canteen_id' => $canteenId,
                            'staff_id' => $staffId,
                            'phone' => $phone,
                            'ordering_type' => OrderEnum::ORDERING_CHOICE,
                            'dinner_id' => $v2['dinner_id'],
                            'type' => $orderType
                        ]);
                        foreach ($foods as $k3 => $v3) {

                        }

                    }

                }
            }

        }


    }


}