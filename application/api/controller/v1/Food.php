<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\FoodService;
use app\lib\exception\SuccessMessage;
use think\facade\Request;

class Food extends BaseController
{

    public function save()
    {
        $params=Request::param();
        (new FoodService())->save($params);
        return json(new SuccessMessage());

    }

}