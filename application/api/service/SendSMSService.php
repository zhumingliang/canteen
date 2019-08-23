<?php


namespace app\api\service;


use app\api\model\SendMessageT;
use app\lib\enum\CommonEnum;
use app\lib\exception\SaveException;
use think\facade\Request;
use zml\tp_aliyun\SendSms;
use zml\tp_tools\Redis;
use function GuzzleHttp\Promise\each_limit;

class SendSMSService
{
    public function sendCode($phone, $type, $num = 1)
    {
        $code = rand(100009, 999999);
        $res = SendSms::instance()->send($phone, ['code' => $code], $type);

        if (key_exists('Code', $res) && $res['Code'] == 'OK') {
            $token = Request::header('token');
            Redis::instance()->set($token, $phone . '-' . $code, 60);
            return true;
        }
    }
}