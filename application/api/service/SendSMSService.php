<?php


namespace app\api\service;


use app\api\model\SendMessageT;
use app\lib\enum\CommonEnum;
use app\lib\exception\SaveException;
use think\Exception;
use think\facade\Request;
use zml\tp_aliyun\SendSms;
use zml\tp_tools\Redis;
use function GuzzleHttp\Promise\each_limit;

class SendSMSService
{
    public function sendCode($phone, $type)
    {
        $code = rand(10000, 99999);
        $params = ['code' => $code];
        $res = SendSms::instance()->send($phone, $params, $type);
        print_r($res);
        $token = Request::header('token');
        if (key_exists('Code', $res) && $res['Code'] == 'OK') {
            $redis = new Redis();
            $redis->set($token, $phone . '-' . $code, 120);
            return true;
        }
        $this->saveSend($phone, $params, $type, $token);
    }

    public function saveSend($phone, $params, $type, $token = '')
    {
        $data = [
            'phone' => $phone,
            'params' => $params,
            'type' => $type,
            'token' => $token,
            'failCount' => 0
        ];
        Redis::instance()->lPush('canteen_send_message', json_encode($data));
    }

    public function sendHandel()
    {
        try {
            $redis = new Redis();
            $lenth = $redis->llen('canteen_send_message');
            echo $lenth;
            if (!$lenth) {
                return true;
            }
            for ($i = 0; $i < 10; $i++) {
                $data = $redis->rPop('canteen_send_message');//从结尾处弹出一个值,超时时间为60s
                $data_arr = json_decode($data, true);
                if (empty($data_arr['phone'])) {
                    continue;
                }
                $res = SendSms::instance()->send($data_arr['phone'], $data_arr['params'], $data_arr['type']);
                print_r($res);
                $data = [
                    'phone' => $data_arr['phone'],
                    'params' => $data_arr['params'],
                    'type' => $data_arr['type'],
                    'failCount' => $data_arr['failCount'] + 1
                ];
                if (key_exists('Code', $res) && $res['Code'] == 'OK') {
                    $redis->lPush('canteen_send_message_success', json_encode($data));
                    if (!empty($data_arr['token'])) {
                        $redis = new Redis();
                        $redis->set($data_arr['token'], $data_arr['phone'] . '-' . $data_arr['params']['code'], 120);
                    }
                } else {
                    if ($data_arr['failCount'] > 2) {
                        $data['failMsg'] = json_encode($res);
                        $redis->lPush('canteen_send_message_fail', json_encode($data));

                    } else {
                        $redis->lPush('canteen_send_message', json_encode($data));
                    }
                }
                usleep(100000);//微秒，调用第三方接口，需要注意频率，
            }
        } catch (Exception $e) {
            LogService::save('sendHandel：' . $e->getMessage());
        }
    }

}