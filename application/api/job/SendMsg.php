<?php


namespace app\api\job;


use app\api\service\LogService;
use think\Exception;
use think\queue\Job;

class SendMsg
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
        //执行发送邮件
        $isJobDone = $this->doJob($data);
        if ($isJobDone) {
            // 如果任务执行成功，删除任务
            LogService::save("<warn>邮件队列已执行完成并且已删除！" . "</warn>\n");
            $job->delete();
        } else {
            LogService::save("<warn>任务执行失败！" . "</warn>\n");
            if ($job->attempts() > 3) {
                //通过这个方法可以检查这个任务已经重试了几次了
                LogService::save("<warn>邮件队列已经重试超过3次，现在已经删除该任务" . "</warn>\n");
                $job->delete();
            } else {
                LogService::save("<info>重新执行该任务!第" . $job->attempts() . "次</info>\n");
                $job->release(); //重发任务
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
        LogService::save("失败:".json_encode($data));

//        print("Warning: Job failed after max retries. job data is :".var_export($data,true)."\n");
    }

    /**
     * 有些消息在到达消费者时,可能已经不再需要执行了
     * @param array|mixed $data 发布任务时自定义的数据
     * @return boolean                 任务执行的结果
     */
    private function checkDatabaseToSeeIfJobNeedToBeDone($data)
    {
        return true;
    }

    /**
     * 根据消息中的数据进行实际的业务处理...
     */
    private function doJob($data)
    {
        try {
            LogService::save(json_encode($data));
            return true;
        } catch (Exception $e) {
            return false;
        }

    }


}