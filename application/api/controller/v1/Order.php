<?php
/**
 * Created by PhpStorm.
 * User: 明良
 * Date: 2019/9/4
 * Time: 15:52
 */

namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\OrderService;
use think\facade\Request;

class Order extends BaseController
{
    public function personChoice()
    {
        $params=Request::param();
        (new OrderService())->personChoice($params);

    }

}