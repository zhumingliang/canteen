<?php


namespace app\api\service;


use app\api\model\NextmonthPaySettingT;
use app\api\model\NextmonthPayT;
use app\api\model\OfficialTemplateT;
use app\api\model\OrderConsumptionV;
use app\api\model\UserT;
use app\lib\enum\CommonEnum;
use app\lib\exception\SaveException;
use app\lib\weixin\Template;
use think\Exception;

class NextMonthPayService
{
    public function handle()
    {
        try {
            //查询开启次月缴费功能的企业
            $isNextMonthPay = NextmonthPaySettingT::where('state', CommonEnum::STATE_IS_OK)->field('c_id')->select();

            if (!empty($isNextMonthPay)) {
                $orderConsumptionDate = date("Y-m", strtotime("-1 month"));

                $orderConsumptionList = [];
                foreach ($isNextMonthPay as $k => $v) {
                    //查询已开启次月缴费企业的上一个月消费数据
                    $lastMonthOrderConsumption = (new OrderConsumptionV())->getOrderConsumption($v['c_id'], $orderConsumptionDate);
                    if (!empty($lastMonthOrderConsumption)) {
                        foreach ($lastMonthOrderConsumption as $k2 => $v2) {
                            array_push($orderConsumptionList, [
                                'dinner_id' => $v2['dinner_id'],
                                'dinner' => $v2['dinner'],
                                'canteen_id' => $v2['canteen_id'],
                                'canteen' => $v2['canteen'],
                                'company_id' => $v2['company_id'],
                                'consumption_date' => $v2['consumption_date'],
                                'department_id' => $v2['department_id'],
                                'department' => $v2['department'],
                                'username' => $v2['username'],
                                'phone' => $v2['phone'],
                                'status' => $v2['status'],
                                'order_money' => $v2['order_money'],
                                'order_count' => $v2['order_count'],
                                'staff_id' => $v2['staff_id'],
                                'pay_date' => date('Y-m', strtotime($v2['consumption_date']))
                            ]);
                        }
                        $save = (new NextmonthPayT())->saveAll($orderConsumptionList);
                        if (!$save) {
                            throw new SaveException(['msg' => "同步次月缴费数据失败"]);
                        }
                    }
                }
            }

        } catch (Exception $e) {
            throw $e;
        }
    }

    public function remind()
    {
        $companys = NextmonthPaySettingT::where('state', CommonEnum::STATE_IS_OK)
            ->field('group_concat(c_id) as c_ids')
            ->select()->toArray();
        if (!empty($companys)) {
            $template = OfficialTemplateT::where('type', 'payment')->find();
            $type = $template->template_id;
            $c_ids = $companys[0]['c_ids'];
        }

    }

    public function paymentStatistic($time_begin, $time_end, $company_id, $department_id, $status,
                                     $pay_method, $username, $phone, $page, $size)
    {
        $userList = (new NextmonthPayT())->userList($time_begin, $time_end, $company_id, $department_id, $status,
            $pay_method, $username, $phone, $page, $size);
        $statistic = (new NextmonthPayT())->dinnerStatistic($time_begin, $time_end, $company_id, $department_id, $status,
            $pay_method, $username, $phone);
        $data = $userList['data'];
        foreach ($data as $k => $v) {
            $dinnerStatistic = [];
            foreach ($statistic as $k2 => $v2) {
                if ($v['staff_id'] == $v2['staff_id'] && $v['pay_date'] == $v2['pay_date']) {
                    array_push($dinnerStatistic, $statistic[$k2]);
                    unset($statistic[$k2]);
                }
                $data[$k]['dinnerStatistic'] = $dinnerStatistic;
            }
        }
        $userList['data'] = $data;
        return [
            'statistic' => $userList
        ];
    }

    public function setting()
    {
        $c_id = Token::getCurrentTokenVar('company_id');
        $payInfo = NextmonthPaySettingT::where('c_id', $c_id)->where('state', CommonEnum::STATE_IS_OK)->find();
        if (!empty($payInfo)) {
            $isNextMonthPay = 1;
        } else {
            $isNextMonthPay = 2;
        }
        return [
            'isOpen' => $isNextMonthPay
        ];
    }

    public function getNextMonthPayInfo()
    {
        $company_id = Token::getCurrentTokenVar('current_company_id');
        $phone = Token::getCurrentTokenVar('phone');
        //查询缴费配置信息
        $payInfo = NextmonthPaySettingT::where('c_id', $company_id)->where('state', CommonEnum::STATE_IS_OK)->find();
        if (empty($payInfo)) {
            $isNextMonthPay = 2;
            $is_pay_day = '';
            $is_order = '';
            $isPay = 2;
        } else {
            $isNextMonthPay = 1;
            $is_pay_day = $payInfo->is_pay_day;
            $is_order = $payInfo->is_order;
            //查询是否欠费
            $orderConsumptionDate = date("Y-m", strtotime("-1 month"));
            $orderConsumption = NextmonthPayT::where('company_id', $company_id)
                ->where('phone', $phone)
                ->where('pay_date', $orderConsumptionDate)
                ->where('state', 2)
                ->select()
                ->toArray();
            if (empty($orderConsumption)) {
                $isPay = 2;
            } else {
                $isPay = 1;
            }
        }
        return [
            'isOpen' => $isNextMonthPay,
            'payDate' => $is_pay_day,
            'isOrder' => $is_order,
            'isPay' => $isPay
        ];
    }

    public function getPayRemindInfo($company_id)
    {
        $data = [];
        //查找当前企业的次月缴费配置信息
        $paySetting = NextmonthPaySettingT::where('c_id', $company_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->field('is_pay_day')
            ->find()->toArray();
        if (empty($paySetting['is_pay_day'])){
            return  [];
        }
        $payDayArr = explode('-', $paySetting['is_pay_day']);
        $payBeginDay = $payDayArr[0];
        $payEndDay = $payDayArr[1];
        //判断是否在可缴费时间
        if (intval(date('d')) >= intval($payBeginDay) && intval(date('d')) <= intval($payEndDay)) {
            $payDate = date("Y-m", strtotime("-1 month"));
            //查询未缴费的员工
            $staffs = (new NextmonthPayT())->getNoPayStaffs($company_id, $payDate);
            if (empty($staffs)) {
                return $data;
            } else {
                foreach ($staffs as $k => $v) {
                    //查询员工openid
                    $openID = UserT::where('phone', $v['phone'])
                        ->where('current_company_id', $company_id)
                        ->field('openid')
                        ->find();
                    if (empty($openID)) {
                        $openid = '';
                    } else {
                        $openid = $openID->openid;
                    }
                    $infoArr = [
                        'staff_id' => $v['staff_id'],
                        'username' => $v['username'],
                        'openid' => $openid,
                        'pay_money' => $v['pay_money'],
                        'pay_date' => $v['pay_date'],
                        'pay_begin_date' => $payBeginDay,
                        'pay_end_date' => $payEndDay
                    ];
                    array_push($data, $infoArr);
                }
                return $data;
            }
        } else {
            return $data;
        }
    }
}