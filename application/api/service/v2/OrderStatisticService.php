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
                                        $phone, $order_type)
    {

        $jobData = [
            'excel_type' => 'consumptionStatistic',
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
            'version' => \think\facade\Request::param('version')];
        //将消息写入
        $down = DownExcelT::create([
            'admin_id' => Token::getCurrentUid(),
            'status' => DownEnum::DOWN_ING,
            'type' => 'consumptionStatistic',
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
            throw new SaveException(['msg' => '上传excel失败']);
        }

    }

    public
    function exportConsumptionStatisticWithAccount($canteen_id, $status, $type,
                                                   $department_id, $username, $staff_type_id,
                                                   $time_begin, $time_end, $company_id,
                                                   $phone, $order_type)
    {

        $jobData = [
            'excel_type' => 'consumptionStatisticWithAccount',
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
            'version' => \think\facade\Request::param('version')];


    }



}