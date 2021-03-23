<?php


namespace app\api\job;


use app\api\controller\v2\Order;
use app\api\model\CompanyAccountT;
use app\api\model\DinnerT;
use app\api\model\DinnerV;
use app\api\model\DownExcelT;
use app\api\model\OrderStatisticV;
use app\api\service\ExcelService;
use app\api\service\LogService;
use app\lib\enum\DownEnum;
use app\lib\enum\OrderEnum;
use app\lib\exception\ParameterException;
use think\Exception;
use think\queue\Job;
use app\api\service\OrderStatisticService as OrderStatisticServiceV1;

class DownExcel
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
            $this->clearUploading($data['company_id'], $data['u_id'], $data['type']);
            $job->delete();
            return;
        }
        //执行excel导入
        $isJobDone = $this->doJob($data);
        if ($isJobDone) {
            // 如果任务执行成功，删除任务
            $code = $data['company_id'] . ":" . $data['u_id'] . ":" . $data['type'];
            $this->clearUploading($data['company_id'], $data['u_id'], $data['type']);
            LogService::saveJob("<warn>导入Excel任务执行成功！编号：$code" . "</warn>\n");
            $job->delete();
        } else {
            if ($job->attempts() > 3) {
                //通过这个方法可以检查这个任务已经重试了几次了
                $code = $data['company_id'] . ":" . $data['u_id'] . ":" . $data['type'];
                LogService::saveJob("<warn>导入excel已经重试超过3次，现在已经删除该任务编号：$code" . "</warn>\n");
                $this->clearUploading($data['company_id'], $data['u_id'], $data['type']);
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

            $excelType = $data['excel_type'];
            switch ($excelType) {
                case 'consumptionStatistic';
                    $this->exportConsumptionStatistic($data);
                    break;
                case 'consumptionStatisticWithAccount';
                    $this->exportConsumptionStatisticWithAccount($data);
                    break;
                case 'orderStatisticDetail';
                    $this->exportOrderStatisticDetail($data);
                    break;

            }

            return true;
        } catch (Exception $e) {
            LogService::saveJob("下载excel失败：error:" . $e->getMessage(), json_encode($data));
            return false;
        }

    }


    private function exportConsumptionStatistic($data)
    {
        $canteen_id = $data['canteen_id'];
        $status = $data['status'];
        $type = $data['type'];
        $department_id = $data['department_id'];
        $username = $data['username'];
        $staff_type_id = $data['staff_type_id'];
        $time_begin = $data['time_begin'];
        $time_end = $data['time_end'];
        $company_id = $data['company_id'];
        $phone = $data['phone'];
        $order_type = $data['order_type'];
        $version = $data['version'];
        $downId = $data['down_id'];
        $locationName = (new  OrderStatisticServiceV1())->getLocationName($order_type, $canteen_id);
        $fileNameArr = [
            0 => $locationName . "消费总报表",
            1 => $locationName . "订餐就餐消费总报表",
            2 => $locationName . "订餐未就餐消费总报表",
            3 => $locationName . "未订餐就餐消费总报表",
            4 => $locationName . "系统补充总报表",
            5 => $locationName . "系统补扣总报表",
            6 => $locationName . "小卖部消费总报表",
            7 => $locationName . "小卖部退款总报表"
        ];

        switch ($data['type']) {
            case OrderEnum::STATISTIC_BY_DEPARTMENT:
                $info = (new  OrderStatisticServiceV1())->consumptionStatisticByDepartment($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id, $phone, $order_type, $version);
                break;
            case OrderEnum::STATISTIC_BY_USERNAME:
                $info = (new  OrderStatisticServiceV1())->consumptionStatisticByUsername($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id, $phone, $order_type, 1, 10000, $version);
                break;
            case OrderEnum::STATISTIC_BY_STAFF_TYPE:
                $info = (new  OrderStatisticServiceV1())->consumptionStatisticByStaff($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id, $phone, $order_type, $version);
                break;
            case OrderEnum::STATISTIC_BY_CANTEEN:
                $info = (new  OrderStatisticServiceV1())->consumptionStatisticByCanteen($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id, $phone, $order_type, $version);
                break;
            case OrderEnum::STATISTIC_BY_STATUS:
                $info = (new  OrderStatisticServiceV1())->consumptionStatisticByStatus($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id, $phone, $order_type, $version);
                break;
            default:
                throw new ParameterException();
        }
        if ($type == OrderEnum::STATISTIC_BY_USERNAME) {
            $statistic = $info['statistic']['data'];
        } else {
            $statistic = $info['statistic'];
        }

        $header = ['序号', '统计变量', '开始时间', '结束时间', '姓名', '部门'];
        //获取饭堂对应的餐次设置
        if (!$canteen_id) {
            $dinner = DinnerV::companyDinners2($company_id);
        } else {
            $dinner = DinnerT::dinnerNames($canteen_id);
        }
        if ($order_type != "canteen") {
            array_push($dinner, [
                'id' => 0,
                'name' => "小卖部消费"
            ]);
            array_push($dinner, [
                'id' => 0,
                'name' => "小卖部退款"
            ]);
        }
        $header = (new  OrderStatisticServiceV1())->addDinnerAndAccountToHeader($header, $dinner);
        $reports = (new  OrderStatisticServiceV1())->prefixConsumptionStatistic($statistic, $dinner, $time_begin, $time_end);
        $reportName = $fileNameArr[$status];
        $file_name = $reportName . "(" . $time_begin . "-" . $time_end . ")";
        $url = (new ExcelService())->makeExcel($header, $reports, $file_name);
        $url = config('setting.domain') . $url;
        DownExcelT::update([
            'id' => $downId,
            'status' => DownEnum::DOWN_SUCCESS,
            'url' => $url,
            'name' => $file_name,
        ]);
    }

    private function exportConsumptionStatisticWithAccount($data)
    {
        $canteen_id = $data['canteen_id'];
        $status = $data['status'];
        $type = $data['type'];
        $department_id = $data['department_id'];
        $username = $data['username'];
        $staff_type_id = $data['staff_type_id'];
        $time_begin = $data['time_begin'];
        $time_end = $data['time_end'];
        $company_id = $data['company_id'];
        $phone = $data['phone'];
        $order_type = $data['order_type'];
        $downId = $data['down_id'];
        $locationName = (new  OrderStatisticServiceV1())->getLocationName($order_type, $canteen_id);
        $fileNameArr = [
            0 => $locationName . "消费总报表",
            1 => $locationName . "订餐就餐消费总报表",
            2 => $locationName . "订餐未就餐消费总报表",
            3 => $locationName . "未订餐就餐消费总报表",
            4 => $locationName . "系统补充总报表",
            5 => $locationName . "系统补扣总报表",
            6 => $locationName . "小卖部消费总报表",
            7 => $locationName . "小卖部退款总报表"
        ];
        $version = \think\facade\Request::param('version');
        switch ($data['type']) {
            case OrderEnum::STATISTIC_BY_DEPARTMENT:
                $info = (new  OrderStatisticServiceV1())->consumptionStatisticByDepartment($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id, $phone, $order_type, $version);
                break;
            case OrderEnum::STATISTIC_BY_USERNAME:
                $info = (new  OrderStatisticServiceV1())->consumptionStatisticByUsername($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id, $phone, $order_type, 1, 10000, $version);
                break;
            case OrderEnum::STATISTIC_BY_STAFF_TYPE:
                $info = (new  OrderStatisticServiceV1())->consumptionStatisticByStaff($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id, $phone, $order_type, $version);
                break;
            case OrderEnum::STATISTIC_BY_CANTEEN:
                $info = (new  OrderStatisticServiceV1())->consumptionStatisticByCanteen($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id, $phone, $order_type, $version);
                break;
            case OrderEnum::STATISTIC_BY_STATUS:
                $info = (new  OrderStatisticServiceV1())->consumptionStatisticByStatus($canteen_id, $status, $department_id, $username, $staff_type_id, $time_begin, $time_end, $company_id, $phone, $order_type, $version);
                break;
            default:
                throw new ParameterException();
        }
        if ($type == OrderEnum::STATISTIC_BY_USERNAME) {
            $statistic = $info['consumptionRecords']['data'];
        } else {
            $statistic = $info['consumptionRecords']['statistic'];
        }
        $accountRecords = $info['accountRecords'];


        $header = ['序号', '统计变量', '开始时间', '结束时间', '姓名', '部门'];
        //获取饭堂对应的餐次设置
        $dinner = DinnerT::dinnerNames($canteen_id);
        $accounts = CompanyAccountT:: accountsWithSorts($company_id);
        if ($order_type != "canteen") {
            array_push($dinner, [
                'id' => 0,
                'name' => "小卖部消费"
            ]);
            array_push($dinner, [
                'id' => 0,
                'name' => "小卖部退款"
            ]);
        }


        $header = (new  OrderStatisticServiceV1())->addDinnerAndAccountToHeader($header, $dinner, $accounts);
        $reports = (new  OrderStatisticServiceV1())->prefixConsumptionStatisticWithAccount($statistic, $accountRecords, $accounts, $dinner, $time_begin, $time_end);
        $reportName = $fileNameArr[$status];
        $file_name = $reportName . "(" . $time_begin . "-" . $time_end . ")";
        $url = (new ExcelService())->makeExcel($header, $reports, $file_name);
        $url = config('setting.domain') . $url;
        DownExcelT::update([
            'id' => $downId,
            'status' => DownEnum::DOWN_SUCCESS,
            'url' => $url,
            'name' => $file_name,
        ]);
    }

    private function exportOrderStatisticDetail($data)
    {
        $canteen_id = $data['canteen_id'];
        $dinner_id = $data['dinner_id'];
        $type = $data['type'];
        $department_id = $data['department_id'];
        $name = $data['name'];
        $time_begin = $data['time_begin'];
        $time_end = $data['time_end'];
        $company_ids = $data['company_id'];
        $phone = $data['phone'];
        $downId = $data['down_id'];
        $list = OrderStatisticV::exportDetail($company_ids, $time_begin,
            $time_end, $name,
            $phone, $canteen_id, $department_id,
            $dinner_id, $type);
        $list = (new OrderStatisticServiceV1())->prefixOrderStatisticDetail($list);
        $header = ['订单ID', '订餐日期', '消费地点', '部门', '姓名', '号码', '餐次', '订餐类型', '份数', '金额', '订餐状态', '明细', '合计'];
        $file_name = "订餐明细报表(" . $time_begin . "-" . $time_end . ")";
        $url = (new ExcelService())->makeExcel($header, $list, $file_name);
        $url = config('setting.domain') . $url;
        DownExcelT::update([
            'id' => $downId,
            'status' => DownEnum::DOWN_SUCCESS,
            'url' => $url,
            'name' => $file_name,
        ]);
    }


}