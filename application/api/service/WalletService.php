<?php


namespace app\api\service;


use app\api\model\CompanyStaffT;
use app\api\model\DinnerV;
use app\api\model\OrderT;
use app\api\model\PayT;
use app\api\model\RechargeCashT;
use app\api\model\RechargeSupplementT;
use app\api\model\RechargeV;
use app\api\model\UserBalanceV;
use app\lib\enum\CommonEnum;
use app\lib\enum\PayEnum;
use app\lib\exception\AuthException;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;
use think\Db;
use think\Model;
use think\Queue;
use think\Request;
use zml\tp_tools\Redis;

class WalletService
{
    public function rechargeCash($params)
    {
        $detail = json_decode($params['detail'], true);
        if (empty($detail)) {
            throw new ParameterException(['msg' => '充值用户信息格式错误']);
        }
        $company_id = Token::getCurrentTokenVar('company_id');
        $admin_id = Token::getCurrentUid();
        $data = $this->prefixDetail($company_id, $admin_id, $detail, $params['money'], $params['remark']);
        $cash = (new RechargeCashT())->saveAll($data);
        if (!$cash) {
            throw new SaveException();
        }
    }

    public function rechargeCashUpload($cash_excel)
    {
        $company_id = Token::getCurrentTokenVar('company_id');
        $admin_id = Token::getCurrentUid();
        $fileName = (new ExcelService())->saveExcelReturnName($cash_excel);
        $fail = $this->checkData($company_id, $fileName);
        if (count($fail)) {
            return [
                'res' => false,
                'fail' => $fail
            ];
        }
        $this->uploadExcelTask($company_id, $admin_id, $fileName, "rechargeCash");
        return [
            'res' => true
        ];
    }

    public function checkData($company_id, $fileName)
    {
        $data = (new ExcelService())->importExcel($fileName);
        $staffs = CompanyStaffT::staffs($company_id);
        $newStaffs = [];
        foreach ($staffs as $k => $v) {
            array_push($newStaffs, $v['username'] . '&' . $v['phone']);
        }
        $fail = [];
        foreach ($data as $k => $v) {
            if ($k < 2) {
                continue;
            }
            if (!in_array($v[0] . '&' . $v[1], $newStaffs)) {
                array_push($fail, '第' . $k . '行数据有问题');
            }
        }
        return $fail;

    }

    public function uploadExcelTask($company_id, $u_id, $fileName, $type)
    {
        //设置限制未上传完成不能继续上传
        if (!$this->checkUploading($company_id, $u_id, $type)) {
            throw new SaveException(["msg" => '有文件正在上传，请稍等']);
        }
        $jobHandlerClassName = 'app\api\job\UploadExcel';//负责处理队列任务的类
        $jobQueueName = "uploadQueue";//队列名称
        $jobData = [
            'type' => $type,
            'company_id' => $company_id,
            'u_id' => $u_id,
            'fileName' => $fileName,
        ];//当前任务的业务数据
        $isPushed = Queue::push($jobHandlerClassName, $jobData, $jobQueueName);
        //将该任务推送到消息队列
        if ($isPushed == false) {
            throw new SaveException(['msg' => '上传excel失败']);
        }
    }


    private function checkUploading($company_id, $u_id, $type)
    {
        $set = "uploadExcel";
        $code = "$company_id:$u_id:$type";
        $check = Redis::instance()->sIsMember($set, $code);
        if ($check) {
            return false;
        }
        Redis::instance()->sAdd($set, $code);
        return $code;
    }

    public function prefixUploadData($company_id, $admin_id, $data)
    {
        $dataList = [];
        $staffs = CompanyStaffT::staffs($company_id);
        $newStaffs = [];
        foreach ($staffs as $k => $v) {
            $newStaffs[$v['phone']] = $v['id'];
        }
        foreach ($data as $k => $v) {
            if ($k == 1 || empty($v[0])) {
                continue;
            }
            array_push($dataList, [
                'admin_id' => $admin_id,
                'company_id' => $company_id,
                'staff_id' => $newStaffs[$v[1]],
                'username' => $v[0],
                'phone' => $v[1],
                'card_num' => $v[2],
                'money' => $v[3],
                'remark' => $v[4]
            ]);
        }
        return $dataList;

    }

    private function prefixDetail($company_id, $admin_id, $detail, $money, $remark)
    {
        $dataList = [];
        foreach ($detail as $k => $v) {
            $data = [];
            $data['company_id'] = $company_id;
            $data['money'] = $money;
            $data['staff_id'] = $v['staff_id'];
            // $data['card_num'] = $v['card_num'];
            $data['state'] = CommonEnum::STATE_IS_OK;
            $data['admin_id'] = $admin_id;
            $data['remark'] = $remark;
            array_push($dataList, $data);
        }
        return $dataList;
    }

    public function rechargeRecords($time_begin, $time_end,
                                    $page, $size, $type, $admin_id, $username)
    {
        $company_id = Token::getCurrentTokenVar('company_id');
        $records = RechargeV::rechargeRecords($time_begin, $time_end,
            $page, $size, $type, $admin_id, $username, $company_id);
        return $records;

    }

    public function exportRechargeRecords($time_begin, $time_end, $type, $admin_id, $username)
    {
        $company_id = Token::getCurrentTokenVar('company_id');
        $records = RechargeV::exportRechargeRecords($time_begin, $time_end, $type, $admin_id, $username, $company_id);
        $header = ['创建时间', '姓名', '充值金额', '充值途径', '充值人员', '备注'];
        $file_name = $time_begin . "-" . $time_end . "-充值记录明细";
        $url = (new ExcelService())->makeExcel($header, $records, $file_name);
        return [
            'url' => config('setting.domain') . $url
        ];

    }

    public function usersBalance($page, $size, $department_id, $user, $phone)
    {
        $company_id = Token::getCurrentTokenVar('company_id');
        $users = UserBalanceV::usersBalance($page, $size, $department_id, $user, $phone, $company_id);
        return $users;
    }

    public function exportUsersBalance($department_id, $user, $phone)
    {
        $company_id = Token::getCurrentTokenVar('company_id');
        $users = UserBalanceV::exportUsersBalance($department_id, $user, $phone, $company_id);
        $header = ['姓名', '员工编号', '卡号', '手机号码', '部门', '余额（元）'];
        $file_name = "饭卡余额报表";
        $url = (new ExcelService())->makeExcel($header, $users, $file_name);
        return [
            'url' => config('setting.domain') . $url
        ];
        return $users;
    }

    public function getUserBalance($company_id, $phone)
    {
        $balance = UserBalanceV::userBalance($company_id, $phone);
        return $balance;

    }

    public function clearBalance()
    {
        $grade = Token::getCurrentTokenVar('grade');
        if ($grade != 2) {
            throw new AuthException();
        }
        $company_id = Token::getCurrentTokenVar('company_id');
        if (empty($company_id)) {
            throw  new AuthException(['msg' => '账户异常']);
        }
        $admin_id = Token::getCurrentUid();
        //调用存储过程，将账户清0
        $resultSet = Db::query('call clear_money(:in_companyId,:in_adminID)', [
            'in_companyId' => $company_id,
            'in_adminID' => $admin_id
        ]);
    }

    public function rechargeSupplement($params)
    {
        $admin_id = Token::getCurrentUid();
        $company_id = Token::getCurrentTokenVar('company_id');
        $staffs = explode(',', $params['staff_ids']);
        $dataList = array();
        foreach ($staffs as $k => $v) {
            $data = [
                'source' => 'save',
                'admin_id' => $admin_id,
                'company_id' => $company_id,
                'canteen_id' => $params['canteen_id'],
                'money' => $params['type'] == 1 ? $params['money'] : 0 - $params['money'],
                'type' => $params['type'],
                'staff_id' => $v,
                'consumption_date' => $params['consumption_date'],
                'remark' => empty($params['remark']) ? '' : $params['remark'],
                'dinner_id' => $params['dinner_id']
            ];
            array_push($dataList, $data);
        }
        $supplement = (new RechargeSupplementT())->saveAll($dataList);
        if (!$supplement) {
            throw new SaveException();
        }
    }

    public function rechargeSupplementUpload($supplement_excel)
    {
        $company_id = Token::getCurrentTokenVar('company_id');
        $admin_id = Token::getCurrentUid();
        $fileName = (new ExcelService())->saveExcelReturnName($supplement_excel);
        // $fileName = dirname($_SERVER['SCRIPT_FILENAME']) . '/static/excel/upload/test.xlsx';
        $fail = $this->checkSupplementData($company_id, $fileName);
        if (count($fail)) {
            return [
                'res' => false,
                'fail' => $fail
            ];
        }
        $this->uploadExcelTask($company_id, $admin_id, $fileName, "supplement");
        return [
            'res' => true
        ];
    }

    private function checkSupplementData($company_id, $fileName)
    {
        $newCanteen = [];
        $newDinner = [];
        $canteens = (new CanteenService())->companyCanteens($company_id);
        $dinners = DinnerV::companyDinners($company_id);
        $staffs = CompanyStaffT::staffs($company_id);
        foreach ($canteens as $k => $v) {
            array_push($newCanteen, $v['name']);
        }
        foreach ($dinners as $k => $v) {
            array_push($newDinner, $v['dinner']);
        }
        if (!count($newCanteen) || !count($newDinner)) {
            throw  new  SaveException(['msg' => '企业饭堂或者餐次设置异常']);
        }
        $newStaffs = [];
        foreach ($staffs as $k => $v) {
            array_push($newStaffs, $v['code'] . '&' . $v['username'] . '&' . $v['card_num'] . '&' . $v['phone']);
        }
        $fail = [];
        $data = (new ExcelService())->importExcel($fileName);
        foreach ($data as $k => $v) {
            if ($k < 2) {
                continue;
            }
            $checkData = $v[0] . '&' . $v[1] . '&' . $v[2] . '&' . $v[3];
            if (!in_array($checkData, $newStaffs) ||
                !in_array($v[4], $newCanteen) || !in_array($v[6], $newDinner)) {
                array_push($fail, '第' . $k . '行数据有问题');
            }
        }
        return $fail;
    }

    public function prefixSupplementUploadData($company_id, $admin_id, $data)
    {
        $dataList = [];
        $canteens = (new CanteenService())->companyCanteens($company_id);
        $dinners = DinnerV::companyDinners($company_id);
        $staffs = CompanyStaffT::staffs($company_id);
        $newStaffs = [];
        $newCanteen = [];
        $newDinner = [];
        foreach ($staffs as $k => $v) {
            $newStaffs[$v['phone']] = $v['id'];
        }
        foreach ($dinners as $k => $v) {
            $newDinner[$v['dinner']] = $v['dinner_id'];
        }
        foreach ($canteens as $k => $v) {
            $newCanteen[$v['name']] = $v['id'];
        }
        foreach ($data as $k => $v) {
            if ($k == 1) {
                continue;
            }
            array_push($dataList, [
                'admin_id' => $admin_id,
                'company_id' => $company_id,
                'staff_id' => $newStaffs[$v[3]],
                'source' => 'upload',
                'code' => $v[0],
                'username' => $v[1],
                'card_num' => $v[2],
                'phone' => $v[3],
                'canteen' => $v[4],
                'canteen_id' => $newCanteen[$v[4]],
                'consumption_date' => $v[5],
                'dinner_id' => $newDinner[$v[7]],
                'dinner' => $v[7],
                'type' => $v[7] == "补扣" ? 2 : 1,
                'money' => $v[8]
            ]);
        }

        return $dataList;
    }

    private function getCanteenID($canteens, $canteen)
    {
        $canteenId = '';
        if (!count($canteens)) {
            return $canteenId;
        }
        foreach ($canteens as $k => $v) {
            if ($v['name'] == $canteen) {
                $canteenId = $v['id'];
                break;
            }

        }
        return $canteenId;
    }

    private function getDinnerID($dinners, $canteen_id, $dinner)
    {
        $dinnerID = '';
        if (!count($dinners)) {
            return $dinnerID;
        }
        foreach ($dinners as $k => $v) {
            if ($v['canteen_id'] == $canteen_id && $v['dinner'] == $dinner) {
                $dinnerID = $v['dinner_id'];
                break;
            }
        }
        return $dinnerID;

    }

    public function saveOrder($params)
    {
        $company_id = Token::getCurrentTokenVar('current_company_id');
        $openid = Token::getCurrentOpenid();
        $u_id = Token::getCurrentUid();
        $phone = Token::getCurrentTokenVar('phone');
        $staff = (new UserService())->getUserCompanyInfo($phone, $company_id);
        $data = [
            'openid' => $openid,
            'company_id' => $company_id,
            'u_id' => $u_id,
            'order_num' => time(),
            'money' => $params['money'],
            'method_id' => $params['method_id'],
            'staff_id' => $staff->id,
            'type' => 'recharge',
            'username' => $staff->username,
            'phone' => $phone
        ];
        $order = PayT::create($data);
        if (!$order) {
            throw new SaveException();
        }
        return [
            'id' => $order->id
        ];
    }

    public function getPreOrder($order_id)
    {
        //$openid = "oSi030qTHU0p3vD4um68F4z2rdHU";//Token::getCurrentOpenid();
        $openid = Token::getCurrentOpenid();
        $status = $this->checkOrderValid($order_id, $openid);
        $method_id = $status['methodID'];
        $company_id = $status['companyID'];
        switch ($method_id) {
            case PayEnum::PAY_METHOD_WX:
                return $this->getPreOrderForWX($status['orderNumber'], $status['orderPrice'], $openid, $company_id);
                break;
            default:
                throw new ParameterException();
        }
    }

    public function getPreOrderForOrder($order_id)
    {
        // $openid = "oSi030oELLvP4suMSvOxTAF8HrLE";//Token::getCurrentOpenid();
        $openid = Token::getCurrentOpenid();
        $status = $this->checkOrderValid($order_id, $openid);
        $method_id = $status['method_id'];
        $company_id = $status['companyID'];
        switch ($method_id) {
            case PayEnum::PAY_METHOD_WX:
                return $this->getPreOrderForWX($status['orderNumber'], $status['orderPrice'], $openid, $company_id);
                break;
            default:
                throw new ParameterException();
        }
    }


    private function getPreOrderForWX($orderNumber, $orderPrice, $openid, $company_id)
    {

        $data = [
            'company_id' => $company_id,
            'openid' => $openid,
            'total_fee' => $orderPrice * 100,//转换单位为分
            'body' => '云饭堂充值中心-点餐充值',
            'out_trade_no' => $orderNumber
        ];
        $wxOrder = (new WeiXinPayService())->getPayInfo($data);
        if (empty($wxOrder['result_code']) || $wxOrder['result_code'] != 'SUCCESS' || $wxOrder['return_code'] != 'SUCCESS') {
            LogService::save(json_encode($wxOrder));
            throw new ParameterException(['msg' => '获取微信支付信息失败']);
        }
        return $wxOrder;


    }

    private
    function checkOrderValid($order_id, $openid)
    {
        $order = PayT::get($order_id);

        if (!$order) {
            throw new ParameterException(['msg' => '订单不存在']);
        }
        if ($order->state == CommonEnum::STATE_IS_FAIL) {
            throw new ParameterException(['msg' => '订单已经取消，不能支付']);
        }
        if (!empty($order->pay_id)) {
            throw new ParameterException(['msg' => '订单已经支付，不能重复支付']);
        }
        if ($openid != $order->openid) {
            throw new ParameterException(['msg' => '用户与订单不匹配']);
        }
        $status = [
            'methodID' => $order->method_id,
            'orderNumber' => $order->order_num,
            'orderPrice' => $order->money,
            'companyID' => $order->company_id
        ];

        return $status;
    }

    private
    function checkOrderValidToOutsider($order_id, $openid)
    {
        $order = PayT::get($order_id);

        if (!$order) {
            throw new ParameterException(['msg' => '订单不存在']);
        }
        if ($order->state == CommonEnum::STATE_IS_FAIL) {
            throw new ParameterException(['msg' => '订单已经取消，不能支付']);
        }
        if (!empty($order->pay_id)) {
            throw new ParameterException(['msg' => '订单已经支付，不能重复支付']);
        }
        if ($openid != $order->openid) {
            throw new ParameterException(['msg' => '用户与订单不匹配']);
        }
        $status = [
            'method_id' => $order->method_id,
            'orderNumber' => $order->order_num,
            'orderPrice' => $order->money,
            'companyID' => $order->company_id
        ];

        return $status;
    }

    public function paySuccess($order_id, $order_type)
    {
        if ($order_type == "canteen") {
            OrderT::update([
                'pay' => 'paid'
            ], ['id' => $order_id]);
        }
    }
}