<?php


namespace app\api\service;


use app\api\model\SendMessageT;
use app\lib\enum\CommonEnum;
use app\lib\exception\SaveException;
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
        $code = rand(10000, 99999);
        $params = ['code' => $code];
        $res = SendSms::instance()->send($phone, $params, $type);
        $token = Request::header('token');
        $key = "code:" . $token;
        if (key_exists('Code', $res) && $res['Code'] == 'OK') {
            Redis::instance()->set($key, $phone . '-' . $code, 120);
            return true;
        }
        LogService::save(json_encode($res));
        $this->msgTask($phone, $params, $type, $key);
    }

    //短信队列
    private function msgTask($phone, $params, $type, $key)
    {
        //php think queue:work --queue sendMsgQueue
        $jobHandlerClassName = 'app\api\job\SendMsg';//负责处理队列任务的类
        $jobQueueName = "sendMsgQueue";//队列名称
        $jobData = [
            'phone' => $phone,
            'params' => $params,
            'type' => $type,
            'token' => $key
        ];;//当前任务的业务数据
        $isPushed = Queue::push($jobHandlerClassName, $jobData, $jobQueueName);
        //将该任务推送到消息队列
        if ($isPushed == false) {
            throw new SaveException(['msg' => '发送短信失败']);
        }

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
            $redis = Redis::instance();
            for ($i = 0; $i < 10; $i++) {
                $data = $redis->rPop('canteen_send_message');//从结尾处弹出一个值,超时时间为60s
                $data_arr = json_decode($data, true);
                if (empty($data_arr['phone'])) {
                    continue;
                }
                $res = SendSms::instance()->send($data_arr['phone'], $data_arr['params'], $data_arr['type']);
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