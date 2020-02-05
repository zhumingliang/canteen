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
                        "url" => "http://canteen.tonglingok.com/api/v1/token/official"
                    ]
                ]
            ]
        ];
        $menus = [
            [
                "name" => "云饭堂3.0",
                "sub_button" => [
                    ["type" => "view",
                        "name" => "进入饭堂测试",
                        "url" => "http://yuncanteen3.51canteen.com/canteen3/wxcms"
                    ], ["type" => "view",
                        "name" => "进入饭堂",
                        "url" => "https://cloudcanteen3.51canteen.com/canteen3/wxcms/index.html"
                    ]
                ]
            ]
        ];
        $res = $this->app->menu->create($menus);
        var_dump($res);
        if (!$res) {
            throw new WeChatException(['msg' => '创建菜单失败']);
        }

    }

}