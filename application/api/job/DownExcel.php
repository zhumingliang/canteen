<?php


namespace app\api\job;


use app\api\controller\v2\Order;
use app\api\model\CompanyAccountT;
use app\api\model\DinnerT;
use app\api\model\DinnerV;
use app\api\model\DownExcelT;
use app\api\model\OrderSettlementV;
use app\api\model\OrderStatisticV;
use app\api\model\OrderTakeoutStatisticV;
use app\api\service\ExcelService;
use app\api\service\LogService;
use app\api\service\NextMonthPayService;
use app\lib\enum\DownEnum;
use app\lib\enum\OrderEnum;
use app\lib\exception\AuthException;
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
                case 'orderSettlement';
                    $this->exportOrderSettlement($data);
                    break;
                case 'orderSettlementWithAccount';
                    $this->exportOrderSettlementWithAccount($data);
                    break;
                case 'orderStatistic';
                    $this->exportOrderStatistic($data);
                    break;
                case 'takeoutStatistic';
                    $this->exportTakeoutStatistic($data);
                    break;
                case 'face';
                    $this->exportFace($data);
                    break;
                case 'nextMonth';
                    $this->exportNextMonthPayStatistic($data);
                    break;
            }
            return true;
        } catch (Exception $e) {
            LogService::saveJob("下载excel失败：error:" . $e->getMessage(), json_encode($data));
            return false;
        }

    }

    private function exportNextMonthPayStatistic($data)
    {
        $pay_method = $data['pay_method'];
        $status = $data['status'];
        $username = $data['username'];
        $department_id = $data['department_id'];
        $time_begin = $data['time_begin'];
        $time_end = $data['time_end'];
        $company_id = $data['company_id'];
        $phone = $data['phone'];
        $downId = $data['down_id'];
        $statistic = (new NextMonthPayService())->nextMonthOutput($time_begin, $time_end,
            $company_id, $department_id, $status, $pay_method,
            $username, $phone);
        $header = ['序号', '时间', '部门', '姓名', '手机号码', '应缴费用', '缴费状态', '缴费时间', '缴费途径', '合计数量', '合计金额（元）', '备注'];
        $reports = (new NextMonthPayService())->prefixConsumptionStatistic($statistic);
        $file_name = "缴费查询报表";
        $url = (new ExcelService())->makeExcel($header, $reports, $file_name);
        $url = config('setting.domain') . $url;
        DownExcelT::update([
            'id' => $downId,
            'status' => DownEnum::DOWN_SUCCESS,
            'url' => $url,
            'name' => $file_name,
        ]);
    }

    private function exportFace($data)
    {
        $canteen_id = $data['canteen_id'];
        $dinner_id = $data['dinner_id'];
        $state = $data['state'];
        $name = $data['name'];
        $department_id = $data['department_id'];
        $time_begin = $data['time_begin'];
        $time_end = $data['time_end'];
        $company_id = $data['company_id'];
        $phone = $data['phone'];
        $downId = $data['down_id'];
        $list = db('face_t')
            ->alias('t1')
            ->leftJoin('canteen_company_t t2', 't1.company_id = t2.id')
            ->leftJoin('canteen_canteen_t t3', 't1.canteen_id = t3.id')
            ->leftJoin('canteen_company_staff_t t4', 't1.staff_id = t4.id')
            ->leftJoin('canteen_company_department_t t5', 't4.d_id = t5.id')
            ->whereBetweenTime('t1.passDate', $time_begin, $time_end)
            ->where(function ($query) use ($name, $phone, $department_id, $state) {
                if (strlen($name)) {
                    $query->where('t4.username', 'like', '%' . $name . '%');
                }
                if (strlen($phone)) {
                    $query->where('t4.phone', 'like', '%' . $phone . '%');
                }
                if ($department_id != 0) {
                    $query->where('t5.id', $department_id);
                }
                if ($state != 0) {
                    $query->where('t1.temperatureResult', $state);
                }
            })
            ->where(function ($query) use ($company_id, $canteen_id, $dinner_id) {
                if ($dinner_id != 0) {
                    $query->where('t1.meal_id', $dinner_id);
                } else {
                    if ($canteen_id != 0) {
                        $query->where('t1.canteen_id', $canteen_id);
                    } else {
                        if ($company_id != 0) {
                            $query->where('t1.company_id', $company_id);
                        }
                    }
                }
            })
            ->field('t1.id,t1.passTime,t3.name as canteen_name,t1.meal_name,t5.name as department_name,t4.username,t4.phone,t1.temperature,(case when t1.temperatureResult = 1 then \'正常\' when t1.temperatureResult=2 then \'异常\' end) state')
            ->order('id asc')
            ->select();
        $dataList = [];
        if (count($list)) {
            foreach ($list as $k => $v) {
                array_push($dataList, [
                    'id' => $k + 1,
                    'passTime' => $v['passTime'],
                    'canteen_name' => $v['canteen_name'],
                    'meal_name' => $v['meal_name'],
                    'department_name' => $v['department_name'],
                    'username' => $v['username'],
                    'phone' => $v['phone'],
                    'temperature' => $v['temperature'],
                    'state' => $v['state']
                ]);
            }
        }
        $header = ['序号', '检测时间', '检测地点', '餐次', '部门', '姓名', '手机号码', '体温', '状态'];
        $file_name = "体温检测报表";
        $url = (new ExcelService())->makeExcel($header, $data, $file_name);
        $url = config('setting.domain') . $url;
        DownExcelT::update([
            'id' => $downId,
            'status' => DownEnum::DOWN_SUCCESS,
            'url' => $url,
            'name' => $file_name,
        ]);
    }

    public function exportTakeoutStatistic($data)
    {
        $canteen_id = $data['canteen_id'];
        $dinner_id = $data['dinner_id'];
        $ordering_date = $data['ordering_date'];
        $department_id = $data['department_id'];
        $status = $data['status'];
        $user_type = $data['user_type'];
        $company_ids = $data['company_id'];
        $downId = $data['down_id'];
        $records = OrderTakeoutStatisticV::exportStatistic($ordering_date,
            $company_ids, $canteen_id, $dinner_id, $status, $department_id,
            $user_type);
        $records = (new OrderStatisticServiceV1())->prefixExportTakeoutStatistic($records);
        $header = ['订餐号', '日期', '消费地点', '姓名', '手机号', '餐次', '金额（元）', '送货地点', '状态'];
        $file_name = $ordering_date . "-外卖管理报表";
        $url = (new ExcelService())->makeExcel($header, $records, $file_name);
        $url = config('setting.domain') . $url;
        DownExcelT::update([
            'id' => $downId,
            'status' => DownEnum::DOWN_SUCCESS,
            'url' => $url,
            'name' => $file_name,
        ]);
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

    private function exportOrderSettlement($data)
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
        $consumption_type = $data['consumption_type'];
        $downId = $data['down_id'];
        $records = OrderSettlementV::exportOrderSettlement(
            $name, $phone, $canteen_id, $department_id, $dinner_id,
            $consumption_type, $time_begin, $time_end, $company_ids, $type);
        $records = (new OrderStatisticServiceV1())->prefixExportOrderSettlement($records);
        $header = ['序号', '消费日期', '消费时间', '部门', '姓名', '手机号', '消费地点', '消费类型', '餐次', '金额', '备注'];
        $file_name = "消费明细报表（" . $time_begin . "-" . $time_end . "）";
        $url = (new ExcelService())->makeExcel($header, $records, $file_name);
        $url = config('setting.domain') . $url;
        DownExcelT::update([
            'id' => $downId,
            'status' => DownEnum::DOWN_SUCCESS,
            'url' => $url,
            'name' => $file_name,
        ]);
    }

    private function exportOrderSettlementWithAccount($data)
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
        $consumption_type = $data['consumption_type'];
        $downId = $data['down_id'];
        $records = OrderSettlementV::exportOrderSettlementWithAccount(
            $name, $phone, $canteen_id, $department_id, $dinner_id,
            $consumption_type, $time_begin, $time_end, $company_ids, $type);
        $records = (new OrderStatisticServiceV1())->prefixExportOrderSettlementWithAccount($records);
        $header = ['序号', '消费日期', '消费时间', '部门', '姓名', '手机号', '消费地点', '账户名称', '消费类型', '餐次', '金额', '备注'];
        $file_name = "消费明细报表（" . $time_begin . "-" . $time_end . "）";
        $url = (new ExcelService())->makeExcel($header, $records, $file_name);
        $url = config('setting.domain') . $url;
        DownExcelT::update([
            'id' => $downId,
            'status' => DownEnum::DOWN_SUCCESS,
            'url' => $url,
            'name' => $file_name,
        ]);
    }

    public function exportOrderStatistic($data)
    {

        $canteen_id = $data['canteen_id'];
        $company_ids = $data['company_id'];
        $time_begin = $data['time_begin'];
        $time_end = $data['time_end'];
        $downId = $data['down_id'];
        $list = OrderStatisticV::exportStatistic($time_begin, $time_end, $company_ids, $canteen_id);
        $header = ['日期', '公司', '消费地点', '餐次', '订餐份数'];
        $file_name = "订餐统计报表(" . $time_begin . "-" . $time_end . ")";
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