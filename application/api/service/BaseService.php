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

}