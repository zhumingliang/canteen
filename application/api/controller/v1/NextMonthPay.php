<?php


namespace app\api\controller\v1;

use app\api\controller\BaseController;
use app\api\model\NextmonthPaySettingT;
use app\api\model\NextmonthPayT;
use app\api\model\PayT;
use app\api\service\NextMonthPayService;
use app\lib\exception\AuthException;
use app\lib\exception\SaveException;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use app\lib\exception\UpdateException;
use think\Db;
use think\facade\Request;

class NextMonthPay extends BaseController
{
    //用于测试--begin
    public function getOrderConsumption()
    {
        (new NextMonthPayService())->handle();
        return json(new SuccessMessage());
    }

    public function remind()
    {
        $data = (new NextMonthPayService())->getPayRemindInfo(87);
        return json(new SuccessMessage());
    }
    //--end

    /**
     * CMS管理端-缴费管理-缴费查询
     */
    public function paymentStatistic($company_id = 0, $department_id = 0, $status = 0, $pay_method = 0,
                                     $username = '', $phone = '', $page = 1, $size = 10)
    {
        $time_begin = Request::param('time_begin');
        $time_end = Request::param('time_end');
        $statistic = (new NextMonthPayService())->paymentStatistic($time_begin, $time_end, $company_id, $department_id, $status, $pay_method,
            $username, $phone, $page, $size);
        return json(new SuccessMessageWithData(['data' => $statistic]));
    }

    /**
     * CMS管理端-企业管理-编辑饭堂-判断是否开启次月缴费
     */
    public function isNextMonthPay()
    {
        $data = (new NextMonthPayService())->setting();
        return json(new SuccessMessageWithData(['data' => $data]));
    }

    /**
     * 微信端-预定餐/选菜订餐-返回次月缴费配置信息
     */
    public function getNextMonthPayInfo()
    {
        $data = (new NextMonthPayService())->getNextMonthPayInfo();
        return json(new SuccessMessageWithData(['data' => $data]));
    }

    /**
     * 设置缴费策略（Route::post('api/:version/nextmonthpay/paySetting', 'api/:version.NextMonthPay/paySetting');）
     */
    public function paySetting()
    {
        //接收企业id
        $c_id = Request::param('c_id');
        //接收最大可透支金额
        $max_over_money = Request::param('max_over_money');
        //可缴费时间
        $is_pay_day = Request::param('is_pay_day');
        //未缴费是否允许订餐
        $is_order = Request::param('is_order');
        //提醒时间
        $remind_time = Request::param('remind_time');
        //提醒频率
        $remind_rate = Request::param('remind_rate');
        //创建时间
        $create_time = date('Y-m-d H:i:s');
        //更新时间
        $update_time = date('Y-m-d H:i:s');
        //判断当前企业是否已经存在在使用的策略
        $dt = NextmonthPaySettingT::where(['c_id' => $c_id, 'state' => 1])->find();
        if (!empty($dt)) {
            throw new SaveException(['msg' => '当前企业已设置缴费策略']);
        }
        $data = [
            'c_id' => $c_id,
            'max_over_money' => $max_over_money,
            'is_pay_day' => $is_pay_day,
            'is_order' => $is_order,
            'remind_time' => $remind_time,
            'remind_rate' => $remind_rate,
            'create_time' => $create_time,
            'update_time' => $update_time,
            'state' => 1
        ];

        $id = NextmonthPaySettingT::create($data)->id;

        if ($id) {
            return json(new SuccessMessageWithData(['data' => ['id' => $id]]));
        } else {
            throw new AuthException(['msg' => '设置失败']);
        }

    }

    /**
     * 设置缴费策略状态开关（Route::post('api/:version/nextmonthpay/stateSetting', 'api/:version.NextMonthPay/stateSetting');）
     */
    public function stateSetting(){
        //缴费策略id
        $id=Request::param('id');
        //次月缴费开关状态
        $state=Request::param('state');
        //更新时间
        $update_time=date('Y-m-d H:i:s');
        //接收最大可透支金额
        $max_over_money=Request::param('max_over_money');
        //可缴费时间
        $is_pay_day=Request::param('is_pay_day');
        //未缴费是否允许订餐
        $is_order=Request::param('is_order');
        //提醒时间
        $remind_time=Request::param('remind_time');
        //提醒频率
        $remind_rate=Request::param('remind_rate');
        $data=[
            'state'=>$state,
            'update_time'=>$update_time,
            'max_over_money'=>$max_over_money,
            'is_pay_day'=>$is_pay_day,
            'is_order'=>$is_order,
            'remind_time'=>$remind_time,
            'remind_rate'=>$remind_rate
        ];
        $data=Db::table('canteen_nextmonth_pay_setting_t')->where('id',$id)->update($data);
        if($data > 0){
            return json( new SuccessMessage());
        }else{
            throw new UpdateException();
        }
    }

    /**
     * 缴费
     */
    public function payMoney()
    {
        //员工id
        $staff_id = Request::param('staff_id');
        //公司id
        $company_id = Request::param('company_id');
        //员工手机号
        $phone = Request::param('phone');
        //欠费时间
        $pay_date = Request::param('pay_date');
        //备注
        $pay_remark = Request::param('pay_remark');
        //缴费时间
        $pay_time = date('Y-m-d H:i:s');
        //更新时间
        $update_time = date('Y-m-d H:i:s');
        //欠费总金额
        $pay_money = Request::param('pay_money');
        //管理员id
        $admin_id = Request::param('admin_id');
        //员工姓名
        $username = Request::param('username');

        $data = NextmonthPayT::where(['staff_id' => $staff_id, 'company_id' => $company_id, 'phone' => $phone, 'pay_date' => $pay_date, 'state' => 2])
            ->update(['pay_method' => 2, 'pay_time' => $pay_time, 'update_time' => $update_time, 'pay_remark' => $pay_remark, 'state' => 1]);
        //生成订单编号
        $order_num = makeOrderNo();
        //平衡支付表
        $dt = [
            'company_id' => $company_id,
            'u_id' => $admin_id,
            'money' => $pay_money,
            'order_num' => $order_num,
            'method_id' => 2,
            'status' => 'paid',
            'create_time' => date('Y-m-d H:i:s'),
            'state' => 1,
            'paid_at' => time(),
            'staff_id' => $staff_id,
            'update_time' => $update_time,
            'type' => 'recharge',
            'order_id' => 0,
            'refund' => 2,
            'outsider' => 2,
            'username' => $username,
            'phone' => $phone,
            'times' => 'one'
        ];
        $pay_data = PayT::create($dt);

        if ($pay_data) {
            return json(new SuccessMessage());

        } else {
            throw new SaveException();
        }

    }

    /**
     * 批量缴费
     */
    public function payMoneyAll()
    {
        //批量传入员工id
        $staff_ids = Request::param('staff_ids');
        //公司id
        $company_ids = Request::param('company_id');
        //员工手机号
        $phones = Request::param('phones');
        //欠费时间
        $pay_dates = Request::param('pay_dates');
        //备注
        $pay_remark = Request::param('pay_remark');
        //缴费时间
        $pay_time = date('Y-m-d H:i:s');
        //更新时间
        $update_time = date('Y-m-d H:i:s');
        //欠费总金额
        $pay_moneys = Request::param('pay_moneys');
        //管理员id
        $admin_id = Request::param('admin_id');
        //员工姓名
        $usernames = Request::param('usernames');

        $staff_id = explode(',', $staff_ids);
        $phone = explode(',', $phones);
        $pay_date = explode(',', $pay_dates);
        $pay_money = explode(',', $pay_moneys);
        $username = explode(',', $usernames);
        $company_id = explode(',', $company_ids);
        foreach ($staff_id as $k1 => $v1) {
            $data = NextmonthPayT::where(['state' => 2, 'staff_id' => $staff_id[$k1], 'company_id' => $company_id[$k1], 'phone' => $phone[$k1], 'pay_date' => $pay_date[$k1]])
                ->update(['state' => 1, 'pay_method' => 2, 'pay_time' => $pay_time, 'update_time' => $update_time, 'pay_remark' => $pay_remark]);
            //生成订单编号
            $order_num = makeOrderNo();
            //平衡支付
            $dt = [
                'company_id' => $company_id[$k1],
                'u_id' => $admin_id,
                'money' => $pay_money[$k1],
                'order_num' => $order_num,
                'method_id' => 2,
                'status' => 'paid',
                'create_time' => date('Y-m-d H:i:s'),
                'state' => 1,
                'paid_at' => time(),
                'staff_id' => $staff_id[$k1],
                'update_time' => $update_time,
                'type' => 'recharge',
                'order_id' => 0,
                'refund' => 2,
                'outsider' => 2,
                'username' => $username[$k1],
                'phone' => $phone[$k1],
                'times' => 'one'
            ];
            $pay_data = PayT::create($dt);

            if (!($pay_data)) {
                throw new SaveException();
            }
        }
        return json(new SuccessMessage());

    }

    /**
     * 导出后台查询列表(Route::post('api/:version/nextmonthpay/nextMonthOutput', 'api/:version.NextMonthPay/nextMonthOutput');)
     */
    public function nextMonthOutput($department_id = 0, $status = 0, $pay_method = 0,
                                    $username = '', $phone = '')
    {
        $company_id = Request::param('company_id');
        if (empty($company_id)) {
            throw new AuthException(['msg' => '请选择企业']);
        }
        $time_begin = Request::param('time_begin');
        if (empty($time_begin)) {
            throw new AuthException(['msg' => '请选择欠费时间的开始时间']);
        }
        $time_end = Request::param('time_end');
        if (empty($time_end)) {
            throw new AuthException(['msg' => '请选择欠费时间的结束时间']);
        }
        $statistic = (new NextMonthPayService())->exportNextMonthPayStatistic($time_begin, $time_end, $company_id, $department_id, $status, $pay_method, $username, $phone);
        return json(new SuccessMessageWithData(['data' => $statistic]));
    }

    /**
     * 查询缴费策略(Route::post('api/:version/nextmonthpay/selectPaySetting', 'api/:version.NextMonthPay/selectPaySetting');)
     */
    public function selectPaySetting()
    {
        //接收企业id
        $company_id = Request::param('company_id');
        $data = NextmonthPaySettingT::where(['c_id' => $company_id])->find();
        return json(new SuccessMessageWithData(['data' => $data]));
    }
}