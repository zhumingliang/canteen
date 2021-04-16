<?php


namespace app\api\service;


use app\api\model\CompanyStaffT;
use app\api\model\SendMessageT;
use app\lib\enum\CommonEnum;
use app\lib\enum\UserEnum;
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
    public function sendCode($outsider, $phone, $type)
    {
        //检查用户是否存在
        if ($outsider == UserEnum::INSIDE) {
            $exist = CompanyStaffT::where('phone', $phone)
                ->where('state', '<', CommonEnum::STATE_IS_DELETE)->count();
            if (!$exist) {
                throw new SaveException(['msg' => "抱歉，您不在系统内，无法进入"]);

            }

        }
        $code = rand(10000, 99999);
        $params = ['code' => $code];
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
            LogService::save(json_encode($res));
            throw new SaveException(['msg' => '发送验证码失败']);
        }

    }
}