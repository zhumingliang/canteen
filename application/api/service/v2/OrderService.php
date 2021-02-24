<?php


namespace app\api\service\v2;


use app\api\service\Token;

class OrderService
{
    public function getOrderMoney($params)
    {
        $canteen_id = Token::getCurrentTokenVar('current_canteen_id');
        $phone = Token::getCurrentTokenVar('phone');
        $company_id = Token::getCurrentTokenVar('current_company_id');
        $orderType = $params['type'];

    }


}