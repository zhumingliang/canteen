<?php


namespace app\api\service;


use app\api\model\FoodT;
use app\lib\enum\CommonEnum;
use app\lib\exception\SaveException;

class FoodService
{
    public function save($params)
    {
        $params['state'] = CommonEnum::STATE_IS_OK;
        $res = FoodT::create($params);
        if (!$res) {
            throw new SaveException();
        }
    }

}