<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;

class WeiXin extends BaseController
{
    public function server()
    {
        $app = app('wechat.official_account');
        $app->server->push(function($message){
            return 'hello,world';
        });
        $app->server->serve()->send();
    }

}