<?php


namespace app\api\job;


use app\api\model\NoticeUserT;
use app\api\service\DepartmentService;
use app\api\service\LogService;
use app\lib\enum\CommonEnum;
use think\Db;
use think\Exception;
use think\queue\Job;

class SendNotice
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
            $job->delete();
        } else {
            LogService::save("<warn>任务执行失败！" . "</warn>\n");
            if ($job->attempts() > 3) {
                //通过这个方法可以检查这个任务已经重试了几次了
                LogService::save("<warn>公告队列已经重试超过3次，现在已经删除该任务" . "</warn>\n");
                $job->delete();
            } else {
                LogService::save("<info>公告执行该任务!第" . $job->attempts() . "次</info>\n");
                $job->release(3); //重发任务
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
        LogService::save("失败:" . json_encode($data));
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
            Db::startTrans();
            $n_id = $data['notice_id'];
            $d_ids = $data['department_ids'];
            $s_ids = $data['staff_ids'];
            $data_list = [];
            if (!empty($d_ids)) {
                $staffs = (new DepartmentService())->departmentStaffs($d_ids);
                if (empty($staffs)) {
                    return true;
                }
                foreach ($staffs as $k => $v) {
                    $data = [
                        's_id' => $v['id'],
                        'n_id' => $n_id,
                        'read' => CommonEnum::STATE_IS_FAIL,
                        'state' => CommonEnum::STATE_IS_OK
                    ];
                    array_push($data_list, $data);
                }
            }
            if (strlen($s_ids)) {
                $ids = explode(',', $s_ids);
                foreach ($ids as $k => $v) {
                    $data = [
                        's_id' => $v,
                        'n_id' => $n_id,
                        'read' => CommonEnum::STATE_IS_FAIL,
                        'state' => CommonEnum::STATE_IS_OK
                    ];
                    array_push($data_list, $data);
                }
            }
            if (count($data_list)) {
                $res = (new NoticeUserT())->saveAll($data_list);
                if (!$res) {
                    LogService::save('sendNotice:失败');
                }
            }

            Db::commit();
            return true;
        } catch (Exception $e) {
            Db::rollback();
            return false;
        }

    }


}