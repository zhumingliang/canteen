<?php


namespace app\api\job;


use app\api\model\CompanyAccountT;
use app\api\model\CompanyStaffT;
use app\api\model\MachineReminderT;
use app\api\model\MachineT;
use app\api\model\OfficialTemplateT;
use app\api\service\CanteenService;
use app\api\service\LogService;
use app\api\service\NextMonthPayService;
use app\lib\enum\CommonEnum;
use app\lib\exception\ParameterException;
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
            LogService::saveJob("<warn>微信通知队列任务执行成功！" . "</warn>\n", json_encode($data));
            $job->delete();
        } else {
            LogService::saveJob("<warn>微信通知队列任务执行失败！" . "</warn>\n", json_encode($data));
            if ($job->attempts() > 3) {
                //通过这个方法可以检查这个任务已经重试了几次了
                LogService::saveJob("<warn>微信通知队列已经重试超过3次，现在已经删除该任务" . "</warn>\n");
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
            $templateConfig = OfficialTemplateT::template($type);
            if (empty($templateConfig)) {
                throw new ParameterException(['msg' => $type . "模板未配置"]);

            }
            $templateId = $templateConfig->template_id;
            $url = $templateConfig->url;
            if ($type == "clearAccount") {
                //账户清零通知
                if (count($ids)) {
                    foreach ($ids as $k => $v) {
                        $this->sendClearAccountTemplate($v, $templateId, $url);
                    }
                }
            } else if ($type == "machine") {
                //设备异常通知
                if (count($ids)) {
                    foreach ($ids as $k => $v) {
                        $this->sendMachineOffLineTemplate($v, $templateId, $url);
                    }
                }

            } else if ($type == "payment") {
                if (count($ids)) {
                    foreach ($ids as $k => $v) {
                        $this->sendPaymentTemplate($v, $templateId, $url);
                    }
                }
            }
            return true;

        } catch (Exception $e) {
            LogService::saveJob('微信通知失败类型（' . $type . '）:' . $e->getMessage(), json_encode($data));
            return false;
        }

    }

    public function sendPaymentTemplate($companyId, $templateId, $url)
    {

        $info = (new NextMonthPayService())->getPayRemindInfo($companyId);
        if (count($info)) {
            $fail = [];

            foreach ($info as $k => $v) {
                LogService::saveJob(1);

                LogService::saveJob(json_encode($v));
                $data = [
                    'first' => "您好，" . $v['pay_date'] . "月份缴费账单已经生成",
                    'keyword1' => abs($v['pay_money']) . "元",
                    'keyword2' => date('Y') . '年' . date('m') . '月' . $v['pay_begin_date'] . '日' . '到' . date('Y') . '年' . date('m') . '月' . $v['pay_end_date'] . '日',
                    'remark' => "请您及时缴费"
                ];
                LogService::saveJob(2);

                if (!empty($v['openid'])) {
                    $res = (new Template())->send($v['openid'], $templateId, $url, $data);
                    if (empty($res['errcode']) || $res['errcode'] != 0) {
                        $data['res'] = $res;
                        array_push($fail, $data);
                    }
                }
                LogService::saveJob(3);

            }
            if (count($fail)) {
                LogService::saveJob('次月缴费微信通知失败:', json_encode($fail));
            }
        }


    }

    public function sendMachineOffLineTemplate($machineId, $templateId, $url)
    {
        try {
            //检测是否在线
            $check = (new CanteenService())->checkMachineState($machineId);
            if ($check == CommonEnum::STATE_IS_FAIL) {
                $reminder = MachineReminderT::reminders($machineId);
                if (count($reminder)) {
                    //发送模板
                    $machine = MachineT::get($machineId);
                    $fail = [];
                    foreach ($reminder as $k => $v) {
                        $data = [
                            'first' => "消费机处于异常状态，请及时处理！",
                            'keyword1' => "网络异常",
                            'keyword2' => $machine->name,
                            'keyword3' => date('Y-m-d H:i'),
                            'remark' => "建议现场查看消费机的异常提示。"
                        ];
                        $res = (new Template())->send($v['openid'], $templateId, $url, $data);
                        if ($res['errcode'] != 0) {
                            $data['res'] = $res;
                            array_push($fail, $data);
                        }
                    }
                    if (count($fail)) {
                        LogService::saveJob('消费机状态异常微信通知失败:', json_encode($fail));
                    }

                }


            }
        } catch (Exception $e) {
            LogService::saveJob($e->getMessage());
        }


    }

    public function sendClearAccountTemplate($accountId, $templateId, $url)
    {
        //获取账户信息
        $account = CompanyAccountT::accountWithDepartment($accountId);
        $accountName = $account['name'];
        $departmentAll = $account['department_all'];
        if ($departmentAll == CommonEnum::STATE_IS_OK) {
            //企业员工都需要发送
            $departmentId = 0;
        } else {
            $departments = $account['departments'];
            $departmentIdArr = [];
            foreach ($departments as $k => $v) {
                array_push($departmentIdArr, $v['department_id']);
            }
            $departmentId = implode(',', $departmentIdArr);
        }
        $staff = CompanyStaffT::getStaffWithUId($accountId, $account['company_id'], $departmentId);
        //发送模板
        $fail = [];
        foreach ($staff as $k => $v) {

            if (empty($v['user']['openid'])) {
                continue;
            }
            if (empty($v['account']) || $v['account'][0]['money'] <= 0) {
                continue;
            }
            $data = [
                'first' => "您的" . $accountName . "余额将在3天后清零！",
                'keyword1' => $v['account'][0]['money'] . "元",
                'keyword2' => date('Y-m-d H:i:s', strtotime($account['next_time'])),
                'remark' => "建议您及时消费。"
            ];
            $openid = $v['user']['openid'];
            $res = (new Template())->send($openid, $templateId, $url, $data);
            if ($res['errcode'] !== 0) {
                $data['res'] = $res;
                array_push($fail, $data);
            }
        }
        if (count($fail)) {
            LogService::saveJob('账户清零微信通知失败:', json_encode($fail));
        }
    }


}