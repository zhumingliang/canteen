<?php


namespace app\api\service\v2;


use app\api\model\CanteenT;
use app\api\model\CompanyAccountT;
use app\api\model\DinnerT;
use app\api\model\DinnerV;
use app\api\model\DownExcelT;
use app\api\model\OrderStatisticV;
use app\api\service\ExcelService;
use app\api\service\Token;
use app\lib\enum\DownEnum;
use app\lib\enum\OrderEnum;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;
use think\Queue;

class DownExcelService
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

    public function exportOrderSettlement(
        $name, $phone, $canteen_id, $department_id, $dinner_id,
        $consumption_type, $time_begin, $time_end, $company_ids, $type)
    {
        $jobData = [
            'excel_type' => 'orderSettlement',
            'canteen_id' => $canteen_id,
            'name' => $name,
            'type' => $type,
            'department_id' => $department_id,
            'dinner_id' => $dinner_id,
            'company_ids' => $company_ids,
            'time_begin' => $time_begin,
            'time_end' => $time_end,
            'phone' => $phone,
            'consumption_type' => $consumption_type,
            'version' => \think\facade\Request::param('version')
        ];
        $this->saveDownExcelJob($jobData);
    }


    public function exportOrderSettlementWithAccount(
        $name, $phone, $canteen_id, $department_id, $dinner_id,
        $consumption_type, $time_begin, $time_end, $company_ids, $type)
    {
        $jobData = [
            'excel_type' => 'orderSettlementWithAccount',
            'canteen_id' => $canteen_id,
            'name' => $name,
            'type' => $type,
            'department_id' => $department_id,
            'dinner_id' => $dinner_id,
            'company_ids' => $company_ids,
            'time_begin' => $time_begin,
            'time_end' => $time_end,
            'phone' => $phone,
            'consumption_type' => $consumption_type,
            'version' => \think\facade\Request::param('version')
        ];
        $this->saveDownExcelJob($jobData);
    }

    public function exportStatistic($time_begin, $time_end, $company_ids, $canteen_id)
    {

        $jobData = [
            'excel_type' => 'orderStatistic',
            'canteen_id' => $canteen_id,
            'company_ids' => $company_ids,
            'time_begin' => $time_begin,
            'time_end' => $time_end,
        ];
        $this->saveDownExcelJob($jobData);
    }


    public function exportTakeoutStatistic($ordering_date, $company_ids,
                                           $canteen_id, $dinner_id,
                                           $status, $department_id,
                                           $user_type)
    {
        $jobData = [
            'excel_type' => 'takeoutStatistic',
            'canteen_id' => $canteen_id,
            'ordering_date' => $ordering_date,
            'status' => $status,
            'department_id' => $department_id,
            'dinner_id' => $dinner_id,
            'company_ids' => $company_ids,
            'user_type' => $user_type,
            'version' => \think\facade\Request::param('version')
        ];
        $this->saveDownExcelJob($jobData);
    }

    public function exportFace($name, $phone, $canteen_id, $department_id, $dinner_id, $time_begin, $time_end, $company_id, $state)
    {
        $jobData = [
            'excel_type' => 'face',
            'canteen_id' => $canteen_id,
            'state' => $state,
            'name' => $name,
            'phone' => $phone,
            'department_id' => $department_id,
            'dinner_id' => $dinner_id,
            'company_id' => $company_id,
            'time_begin' => $time_begin,
            'time_end' => $time_end,
            'version' => \think\facade\Request::param('version')
        ];
        $this->saveDownExcelJob($jobData);
    }


    public function exportNextMonthPayStatistic($time_begin, $time_end,
                                                $company_id, $department_id,
                                                $status, $pay_method,
                                                $username, $phone)
    {
        $jobData = [
            'excel_type' => 'nextMonth',
            'pay_method' => $pay_method,
            'status' => $status,
            'name' => $username,
            'phone' => $phone,
            'department_id' => $department_id,
            'company_id' => $company_id,
            'time_begin' => $time_begin,
            'time_end' => $time_end,
            'version' => \think\facade\Request::param('version')
        ];
        $this->saveDownExcelJob($jobData);

    }

    public function receptionsForCMSOutput($apply_name, $canteen_id, $department_id,
                                           $dinner_id, $ordering_date, $reception_code,
                                           $company_id,
                                           $reception_state)
    {
        $jobData = [
            'excel_type' => 'reception',
            'apply_name' => $apply_name,
            'canteen_id' => $canteen_id,
            'dinner_id' => $dinner_id,
            'ordering_date' => $ordering_date,
            'department_id' => $department_id,
            'company_id' => $company_id,
            'reception_code' => $reception_code,
            'reception_state' => $reception_state,
            'version' => \think\facade\Request::param('version')
        ];
        $this->saveDownExcelJob($jobData);
    }

    public function receptionsForApplyOutput($apply_name, $canteen_id, $department_id,
                                             $dinner_id, $ordering_date, $apply_code, $company_id,
                                             $apply_state)
    {
        $jobData = [
            'excel_type' => 'receptionForApply',
            'apply_name' => $apply_name,
            'canteen_id' => $canteen_id,
            'dinner_id' => $dinner_id,
            'ordering_date' => $ordering_date,
            'department_id' => $department_id,
            'company_id' => $company_id,
            'apply_code' => $apply_code,
            'apply_state' => $apply_state,
            'version' => \think\facade\Request::param('version')
        ];
        $this->saveDownExcelJob($jobData);
    }

    public function exportRechargeRecords($time_begin, $time_end, $type, $admin_id, $username, $department_id, $excel_type)
    {
        $jobData = [
            'excel_type' => $excel_type,
            'type' => $type,
            'admin_id' => $admin_id,
            'company_id' => Token::getCurrentTokenVar('company_id'),
            'name' => $username,
            'department_id' => $department_id,
            'time_begin' => $time_begin,
            'time_end' => $time_end,
            'version' => \think\facade\Request::param('version')
        ];
        $this->saveDownExcelJob($jobData);
    }

    public function exportUsersBalance($department_id, $user, $phone,$excel_type)
    {
        $jobData = [
            'excel_type' => $excel_type,
            'user' => $user,
            'phone' => $phone,
            'company_id' => Token::getCurrentTokenVar('company_id'),
            'department_id' => $department_id,
            'version' => \think\facade\Request::param('version')
        ];
        $this->saveDownExcelJob($jobData);
    }

    private
    function saveDownExcelJob($jobData)
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