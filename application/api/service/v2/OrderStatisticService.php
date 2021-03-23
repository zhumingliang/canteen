<?php


namespace app\api\service\v2;


use app\api\model\CanteenT;
use app\api\model\CompanyAccountT;
use app\api\model\DinnerT;
use app\api\model\DinnerV;
use app\api\model\DownExcelT;
use app\api\service\ExcelService;
use app\api\service\Token;
use app\lib\enum\DownEnum;
use app\lib\enum\OrderEnum;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;
use think\Queue;

class OrderStatisticService
{

    public
    function exportConsumptionStatistic($canteen_id, $status, $type,
                                        $department_id, $username, $staff_type_id,
                                        $time_begin, $time_end, $company_id,
                                        $phone, $order_type, $excel_type)
    {

        $jobData = [
            'excel_type' => $excel_type,
            'canteen_id' => $canteen_id,
            'status' => $status,
            'type' => $type,
            'department_id' => $department_id,
            'username' => $username,
            'staff_type_id' => $staff_type_id,
            'time_begin' => $time_begin,
            'time_end' => $time_end,
            'company_id' => $company_id,
            'phone' => $phone,
            'order_type' => $order_type,
            'version' => \think\facade\Request::param('version')
        ];
        $this->saveDownExcelJob($jobData);

    }

    public function exportOrderStatisticDetail($company_ids, $time_begin,
                                               $time_end, $name,
                                               $phone, $canteen_id, $department_id,
                                               $dinner_id, $type)
    {
        $jobData = [
            'excel_type' => 'orderStatisticDetail',
            'canteen_id' => $canteen_id,
            'company_ids' => $company_ids,
            'name' => $name,
            'type' => $type,
            'department_id' => $department_id,
            'dinner_id' => $dinner_id,
            'time_begin' => $time_begin,
            'time_end' => $time_end,
            'phone' => $phone
        ];
        $this->saveDownExcelJob($jobData);


    }

    private function saveDownExcelJob($jobData)
    {
        //将消息写入
        $down = DownExcelT::create([
            'admin_id' => Token::getCurrentUid(),
            'status' => DownEnum::DOWN_ING,
            'type' => $jobData['excel_type'],
            'data' => json_encode($jobData)
        ]);
        if (!$down) {
            throw new SaveException(['msg' => '上传excel失败']);
        }
        $jobData['down_id'] = $down->id;
        $jobHandlerClassName = 'app\api\job\DownExcel';//负责处理队列任务的类
        $jobQueueName = "downExcelQueue";//队列名称
        $isPushed = Queue::push($jobHandlerClassName, $jobData, $jobQueueName);
        //将该任务推送到消息队列
        if ($isPushed == false) {
            $down->status = DownEnum::DOWN_FAIL;
            $down->save();
            throw new SaveException(['msg' => '下载 excel失败']);
        }
    }
}