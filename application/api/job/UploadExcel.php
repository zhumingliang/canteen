<?php


namespace app\api\job;


use app\api\model\NoticeUserT;
use app\api\model\RechargeCashT;
use app\api\service\DepartmentService;
use app\api\service\ExcelService;
use app\api\service\LogService;
use app\api\service\Token;
use app\api\service\WalletService;
use app\lib\enum\CommonEnum;
use app\lib\exception\SaveException;
use think\Db;
use think\Exception;
use think\queue\Job;
use zml\tp_tools\Redis;

class UploadExcel
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
        //执行excel导入
        $isJobDone = $this->doJob($data);
        if ($isJobDone) {
            // 如果任务执行成功，删除任务
            LogService::save("<warn>导入Excel任务执行成功！" . "</warn>\n");
            $job->delete();
        } else {
            if ($job->attempts() > 3) {
                //通过这个方法可以检查这个任务已经重试了几次了
                LogService::save("<warn>导入excel已经重试超过3次，现在已经删除该任务" . "</warn>\n");
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
            $type = $data['type'];
            if ($type == "rechargeCash") {
                return $this->uploadRechargeCash($data);
            }
            return false;
        } catch (Exception $e) {
            return false;
        }

    }

    public function uploadStaff()
    {

    }

    public function uploadRechargeCash($data)
    {
        $company_id = $data['company_id'];
        $admin_id = $data['u_id'];
        $fileName = $data['fileName'];
        $data = (new ExcelService())->importExcel($fileName);
        $dataList = (new WalletService())->prefixUploadData($company_id, $admin_id, $data);
        $cash = (new RechargeCashT())->saveAll($dataList);
        $this->clearUploading($company_id, $admin_id, $data['type']);
        if (!$cash) {
            return false;
        }
        return true;
    }
    private
    function clearUploading($company_id, $u_id, $type){

        $set = "uploadExcel";
        $code = "$company_id:$u_id:$type";
        Redis::instance()->sRem($set, $code);
        LogService::save('clear:' . $code);

    }

}