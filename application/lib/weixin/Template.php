<?php


namespace app\lib\weixin;


use app\api\service\LogService;
use EasyWeChat\Factory;

class Template extends Base
{

    public function send($openid, $template_id, $url, $data)
    {
        /*    $config = [
                'app_id' => 'wx2e09822f479f1870',
                'secret' => '53a7e229d559c314c5c19221d4745e77',
                'token' => 'canteen',
                'aes_key' => 'wQs4Ltd93z92pf69xybU7f26HQEAFAy44eMo713KLmX',
                // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
                'response_type' => 'array',
            ];*/
        LogService::saveJob(json_encode(config('wechat')));

        $config = [
            'app_id' => config('wechat.official_account.default.app_id'),
            'secret' => config('wechat.official_account.default.secret'),
            'token' => 'canteen',
            'aes_key' => config('wechat.official_account.default.aes_key'),
            // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
            'response_type' => 'array',
        ];

        $app = Factory::officialAccount($config);
        $res = $app->template_message->send([
            'touser' => $openid,
            'template_id' => $template_id,
            'data' => $data,
        ]);

        return $res;
    }

}