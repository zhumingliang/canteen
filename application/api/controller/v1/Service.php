<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\OrderService;

class Service extends BaseController
{
    //处理订餐未就餐改为订餐就餐
    public function orderStateHandel()
    {
        (new OrderService())->orderStateHandel();
    }

}