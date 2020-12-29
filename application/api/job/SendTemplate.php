<?php


namespace app\api\job;


use app\api\model\CompanyAccountT;
use app\api\model\CompanyStaffT;
use app\api\model\MachineReminderT;
use app\api\model\MachineT;
use app\api\model\OfficialTemplateT;
use app\api\service\CanteenService;
use app\api\service\LogService;
use app\lib\enum\CommonEnum;
use app\lib\weixin\Template;
use think\Exception;
use think\queue\Job;

class SendTemplate
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
            LogService::saveJob("<warn>账户清零微信通知队列任务执行成功！" . "</warn>\n", json_encode($data));
            $job->delete();
        } else {
            LogService::saveJob("<warn>账户清零微信通知队列任务执行失败！" . "</warn>\n", json_encode($data));
            if ($job->attempts() > 3) {
                //通过这个方法可以检查这个任务已经重试了几次了
                LogService::saveJob("<warn>账户清零微信通知队列已经重试超过3次，现在已经删除该任务" . "</warn>\n");
                $job->delete();
            } else {
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
        LogService::save("账户清零微信通知失败:" . json_encode($data));
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
            $type = $data['type'];
            $id = $data['id'];
            $ids = explode(',', $id);
            if ($type == "clearAccount") {
                //账户清零通知
                if (count($ids)) {
                    foreach ($ids as $k => $v) {
                        $this->sendClearAccountTemplate($v);
                    }
                }
            } else if ($type == "machine") {
                //设备异常通知
                if (count($ids)) {
                    foreach ($ids as $k => $v) {
                        $this->sendMachineOffLineTemplate($v);
                    }
                }

            }
            return true;

        } catch (Exception $e) {
            return false;
            LogService::saveJob('微信通知失败类型（' . $type . '）:' . $e->getMessage(), json_encode($data));
        }

    }

    public function sendMachineOffLineTemplate($machineId)
    {
        try {
            //检测是否在线
            $check = (new CanteenService())->checkMachineState($machineId);
            if ($check == CommonEnum::STATE_IS_FAIL) {
                $reminder = MachineReminderT::reminders($machineId);
                if (count($reminder)) {
                    $templateConfig = OfficialTemplateT::template('machine');
                    $template_id = $templateConfig->template_id;
                    $url = $templateConfig->url;
                    //发送模板
                    $machine = MachineT::get($machineId);
                    $fail = [];
                    foreach ($reminder as $k => $v) {
                        $data = [
                            'first' => "消费机处于异常状态，请及时处理！",
                            'keyword1' => "异常报警：网络异常",
                            'keyword2' => "机器名：" . $machine->name,
                            'keyword3' => "异常时间：" . date('Y-m-d H:i'),
                            'remark' => "建议现场查看消费机的异常显示。"
                        ];
                        if ($templateConfig) {
                            $res = (new Template())->send($v['openid'], $template_id, $url, $data);
                            if ($res['errorcode'] != 0) {
                                $data['res'] = $res;
                                array_push($fail, $data);
                            }
                        }
                    }
                    if (count($fail)) {
                        LogService::saveJob($machineId,json_encode($fail));
                    }

                }


            }
        } catch (Exception $e) {
            LogService::saveJob($e->getMessage());
        }


    }

    private function sendClearAccountTemplate($accountId)
    {
        //获取账户信息
        $account = CompanyAccountT::accountWithBalance($accountId);
        $accountName = $account['name'];
        $departmentAll = $account['department_all'];
        if ($departmentAll == CommonEnum::STATE_IS_OK) {
            //企业员工都需要发送
            $departmentId = 0;
        } else {
            $departments = $account['departments'];
            $departmentIdArr = [];
            foreach ($departments as $k => $v) {
                array_push($departmentIdArr, $v['id']);
            }
            $departmentId = implode(',', $departmentIdArr);
        }
        $staff = CompanyStaffT::getStaffWithUId($accountId, $account->company_id, $departmentId);
        //发送模板
        $fail = [];
        foreach ($staff as $k => $v) {
            if (empty($v['user']['openid'])) {
                continue;
            }
            if (empty($v['account']) || $v['account']['money'] <= 0) {
                continue;
            }
            $data = [
                'first' => "您的" . $accountName . "余额将在3天后清零！",
                'keyword1' => $v['account']['money'] . "元",
                'keyword2' => date('Y-m-d H:i', strtotime($account['next_time'])),
                'remark' => "建议您及时消费。"
            ];
            $templateConfig = OfficialTemplateT::template('clearAccount');
            if ($templateConfig) {
                $res = (new Template())->send($v['user']['openid'], $templateConfig->template_id, $templateConfig->url, $data);
                $data['res'] = $res;
                array_push($fail, $data);
            }
        }
        if (count($fail)) {
            LogService::saveJob('账户清零微信通知失败:', json_encode($data));
        }
    }


}