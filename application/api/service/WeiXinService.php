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
            $menus = [
                [
                    "name" => "云饭堂3.0",
                    "sub_button" => [
                        ["type" => "view",
                            "name" => "进入饭堂",
                            "url" => $url
                        ],
                        ["type" => "view",
                            "name" => "农行支付",
                            "url" => "https://enjoy.abchina.com/jf-openweb/wechat/shareEpayItem?code=JF-EPAY2019062401722"
                        ]
                    ]
                ]
            ];
        } else {
            $url = "https://cloudcanteen3.51canteen.com/canteen3/wxcms";
            $menus = [
                [
                    "name" => "进入饭堂",
                    "type" => "view",
                    "url" => $url
                ]
            ];
        }


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