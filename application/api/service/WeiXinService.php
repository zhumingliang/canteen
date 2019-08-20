<?php


namespace app\api\service;


use app\lib\exception\WeChatException;
use Naixiaoxin\ThinkWechat\Facade;

class WeiXinService
{
    public $app = null;

    public function __construct()
    {
        $this->app = Facade::officialAccount();
    }

    public function createMenu()
    {
        $menus = [
            [
                "name" => "Author",
                "sub_button" => [
                    ["type" => "view",
                        "name" => "获取Info",
                        "url" => "http://canteen.tonglingok/api/v1/token/official"
                    ]
                ]
            ]
        ];
        $res = $this->app->menu->create($menus);
        LogService::save(json_encode($res));
        if (!$res) {
            throw new WeChatException(['msg' => '创建菜单失败']);
        }

    }

}