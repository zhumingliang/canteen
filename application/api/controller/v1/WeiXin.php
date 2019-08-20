<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\WeiXinService;

class WeiXin extends BaseController
{
    public function server()
    {
        $app = app('wechat.official_account');
        $app->server->push(function ($message) {
            return 'hello,world';
        });
        $app->server->serve()->send();
    }

    public function createMenu()
    {
        (new WeiXinService())->createMenu();

    }

}