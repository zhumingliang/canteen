<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\LogService;
use app\api\service\WeiXinService;

class WeiXin extends BaseController
{
    public function server()
    {
        $app = app('wechat.official_account');
        $app->server->push(function ($message) {
            if ($message['MsgType'] == 'event') {
                LogService::save(\GuzzleHttp\json_encode($message));
            }
            return '欢迎来到云饭堂！';
        });
        $app->server->serve()->send();
    }

    public function createMenu()
    {
        (new WeiXinService())->createMenu();

    }

}