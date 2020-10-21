<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\AccountService;
use app\lib\exception\SuccessMessage;
use think\facade\Request;

class Account extends BaseController
{
    public function save()
    {
        $params = Request::param();
        (new AccountService())->save($params);
        return json(new SuccessMessage());

    }

}