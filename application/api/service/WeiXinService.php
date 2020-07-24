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

    public function createMenu($type)
    {

        if ($type == "dev") {
            $url = "http://test-www.51canteen.cn/wxcms";
        } else {
            $url = "https://cloudcanteen3.51canteen.com/canteen3/wxcms";
        }

        $menus = [
            [
                "name" => "云饭堂3.0",
                "sub_button" => [
                    ["type" => "view",
                        "name" => "进入饭堂",
                        "url" => $url
                    ]
                ]
            ]
        ];
        print_r($menus);
        $res = $this->app->menu->create($menus);
        if (!$res) {
            throw new WeChatException(['msg' => '创建菜单失败']);
        }
        return $res;
    }

    public function qRCode($company_id)
    {
        $result = $this->app->qrcode->forever($company_id);
        $url = $this->app->qrcode->url($result['ticket']);
        return (new ImageService())->saveCompanyQRCode($url);


    }

}