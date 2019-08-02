<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use think\facade\Request;

class Consumption extends BaseController
{
    public function staff()
    {
        $code = Request::param('code');
        echo $code;
    }

}