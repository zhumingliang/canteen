<?php


namespace app\api\job;


use app\api\service\GatewayService;
use app\api\service\LogService;
use think\Exception;
use think\queue\Job;
use zml\tp_tools\Redis;

class SendSort2
{
    /**
     * fire方法是消息队列默认调用的方法
     * @param Job $job 当前的任务对象
     * @param array|mixed $data 发布任务时自定义的数据
     */
    public function fire(Job $job, $data)
    {
        // 有些消息在到达消费者时,可能已经不再需要执行了
        $isJobStillNeedToBeDone = $this->checkDatabaseToSeeIfJobNeedToBeDone($data);
        if (!$isJobStillNeedToBeDone) {
            $job->delete();
            return;
        }
        //执行发送短信
        $isJobDone = $this->doJob($data);
        if ($isJobDone) {
            // 如果任务执行成功，删除任务
            LogService::saveJob("<warn>时间排队队列执行成功" . "</warn>\n");
            $job->delete();
        } else {
            if ($job->attempts() > 10) {
                //通过这个方法可以检查这个任务已经重试了几次了
                LogService::saveJob("<warn>时间排队队列已经重试超过10次，现在已经删除该任务" . "</warn>\n");
                $job->delete();
            } else {
                $job->release(30); //重发任务
            }
        }
    }

    /**
     * 该方法用于接收任务执行失败的通知
     * @param $data  string|array|... 发布任务时传递的数据
     */
    public function failed($data)
    {
        //可以发送邮件给相应的负责人员
        LogService::saveJob("发送排序失败:" . json_encode($data));
    }

    /**
     * 有些消息在到达消费者时,可能已经不再需要执行了
     * @param array|mixed $data 发布任务时自定义的数据
     * @return boolean                 任务执行的结果
     */
    private function checkDatabaseToSeeIfJobNeedToBeDone($data)
    {
        //return true;
        $set = "webSocketReceiveCode";
        $code = $data['websocketCode'];
        $check = Redis::instance()->sIsMember($set, $code);
        return $check;
    }

    /**
     * 根据消息中的数据进行实际的业务处理...
     */
    private function doJob($data)
    {
        try {
            if ($data['device']) {
                $sendData = [
                    'errorCode' => 0,
                    'msg' => 'success',
                    'type' => 'time',
                    'data' => [
                        'id' => $data['id'],
                        'on_time' => $data['on_time'],
                        'off_time' => $data['off_time'],
                        'repeat' => $data['repeat'],
                        'valid' => $data['valid'],
                        'websocketCode' => $data['websocketCode'],
                    ]
                ];
                GatewayService::sendToMachine($data['device'], json_encode($sendData));

            }
            return true;
        } catch (Exception $e) {
            LogService::saveJob("发送排序失败：error:" . $e->getMessage(), json_encode($data));
        }

    }


}