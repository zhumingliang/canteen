<?php


namespace app\api\service;


use app\lib\exception\ParameterException;

class BaseService
{
    public function prefixSelectFiled($params)
    {
        if (!empty($params['menu_ids'])) {
            return [
                'field' => 'menu_id',
                'value' => $params['menu_ids']
            ];
        } else if (!empty($params['dinner_ids'])) {
            return [
                'field' => 'dinner_id',
                'value' => $params['dinner_ids']
            ];
        } else if (!empty($params['canteen_ids'])) {
            return [
                'field' => 'canteen_id',
                'value' => $params['canteen_ids']
            ];
        } else if (!empty($params['company_ids'])) {
            return [
                'field' => 'company_id',
                'value' => $params['company_ids']
            ];
        }
        throw  new ParameterException();
    }


    public function prefixExpiryDate($expiry_date, $params, $symbol = '+')
    {
        $type = ['minute', 'hour', 'day', 'week', 'month', 'year'];
        $exit = 0;
        foreach ($type as $k => $v) {
            if (key_exists($v, $params)) {
                $exit = 1;
                $expiry_date = date('Y-m-d H:i:s', strtotime($symbol . $params[$v] . "$v", strtotime($expiry_date)));
                break;
            }

        }
        if (!$exit) {
            $expiry_date = date('Y-m-d H:i:s', strtotime($symbol . config("setting.qrcode_expire_in") . "minute", strtotime($expiry_date)));

        }
        return $expiry_date;
    }

    public function prefixExpiryDateForOrder($expiry_date, $count, $symbol = '+')
    {
        $expiry_date = date('Y-m-d', strtotime($symbol . $count . "day", strtotime($expiry_date)));
        return $expiry_date;
    }


}