<?php


namespace app\api\service;


use app\api\model\SendMessageT;
use app\lib\enum\CommonEnum;
use app\lib\exception\SaveException;
use app\lib\Http;
use think\Exception;
use think\facade\Request;
use think\Queue;
use zml\tp_aliyun\SendSms;
use zml\tp_tools\Redis;
use function GuzzleHttp\Promise\each_limit;

class SendSMSService
{
    public function sendCode($phone, $type)
    {
        /*   $code = rand(10000, 99999);
           $params = ['code' => $code];
           $res = SendSms::instance()->send($phone, $params, $type);
           $token = Request::header('token');
           $key = "code:" . $token;
           if (key_exists('Code', $res) && $res['Code'] == 'OK') {
               Redis::instance()->set($key, $phone . '-' . $code, 120);
               return true;
           }*/
        // $this->msgTask($phone, $params, $type, $key);

        $code = rand(10000, 99999);
        $params = ['code' => $code];
        //$res = SendSms::instance()->send($phone, $params, $type);
        $this->sendSms($phone, 'canteen_' . $type, $params);
        $token = Request::header('token');
        $key = "code:" . $token;
        Redis::instance()->set($key, $phone . '-' . $code, 120);
        return true;
    }

    public function sendSms($phone_number, $type, $params)
    {
        $url = 'http://service.tonglingok.com/sms/template';
        $data = [
            'phone_number' => $phone_number,
            "type" => $type,
            "sign" => "canteen",
            "params" => empty($params) ? ['create_time' => date('Y-m-d H:i:s')] : $params
        ];
        $res = Http::sendRequest($url, $data);
        if ($res['ret'] !== true || $res['info']['errorCode'] !== 0) {
            throw new SaveException(['msg' => '发送验证码失败']);
        }

    }
}