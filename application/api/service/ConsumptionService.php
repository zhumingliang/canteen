<?php


namespace app\api\service;


use app\api\model\DinnerT;
use app\api\model\OrderT;
use app\api\model\StaffQrcodeT;
use app\lib\enum\CommonEnum;
use app\lib\exception\ParameterException;
use function GuzzleHttp\Psr7\str;

class ConsumptionService
{
    public function staff($type, $code, $staff_id)
    {
        $company_id = Token::getCurrentTokenVar('company_id');
        $belong_id = Token::getCurrentTokenVar('belong_id');
        if ($type == 'canteen') {

        } else if ($type == 'shop') {

        } else {
            throw  new ParameterException(['msg' => '类别异常']);
        }
    }

    private function handelCanteen($code, $company_id, $staff_id, $staff_type_id, $canteen_id)
    {
        //获取当前餐次
        $dinner = $this->getNowDinner($canteen_id);
        //检测用户二维码是否过期
        $this->checkCanteenQRCode($staff_id, $code);
        //检测该用户是否有订单
        $order = $this->checkCanteenOrder($canteen_id, $staff_id, $dinner['id'], $dinner['fixed']);


    }

    private function checkCanteenQRCode($staff_id, $code)
    {
        $QRCode = StaffQrcodeT::where('staff_id', $staff_id)
            ->where('code', $code)
            ->find();
        if (empty($QRCode)) {
            throw new ParameterException(['msg' => '二维码不存在']);
        }
        if (strtotime($QRCode->expiry_date) < time()) {
            throw new ParameterException(['msg' => '二维码过期']);
        }
    }

    private function checkCanteenOrder($canteen_id, $staff_id, $dinner_id, $dinner_fixed)
    {
        $order = OrderT::infoToMachine($canteen_id, $staff_id, $dinner_id);
        if ($order) {
            //修改订单状态：改为已经就餐
            $order->used = CommonEnum::STATE_IS_OK;
            $order->used_time = date('Y-m-d H:i:s');
            //检测订餐扣费方式是否未订餐未就餐
            //订餐未就餐则改为订餐就餐，并将相应的金额修正
            if ($order->consumption_type == 'no_meals_ordered') {
                //获取消费策略
                $staff_type_id = $order->staff_type_id;
                $consumption_num = $order->consumption_num;
                $strategies = (new CanteenService())->getStaffConsumptionStrategy($canteen_id, $dinner_id, $staff_type_id);
                $detail = $strategies->detail;
                if (empty($detail)) {
                    throw new ParameterException(['msg' => "消费策略设置异常"]);
                }
                foreach ($detail as $k => $v) {
                    if ($consumption_num == $v['number']) {
                        $strategy = $v['strategy'];
                        foreach ($strategy as $k2 => $v2) {
                            if ($v2['status'] == "ordering_meals") {
                                $meal_money = $v2['money'];
                                $meal_sub_money = $v2['sub_money'];
                            }
                        }
                    }


                }
                $order->save();
                return $order;
            }
            //没有订单检测是否可以未订餐就餐

        }}

        private
        function getNowDinner($canteen_id)
        {
            $dinners = DinnerT::dinners($canteen_id);
            if ($dinners->isEmpty()) {
                throw new ParameterException(['msg' => '饭堂未设置餐次信息']);
            }
            $dinner = array();
            foreach ($dinners as $k => $v) {
                if (strtotime($v->meal_time_begin) <= time() &&
                    time() <= strtotime($v->meal_time_end)) {
                    $dinner['id'] = $v->id;
                    $dinner['name'] = $v->name;
                    $dinner['fixed'] = $v->fixed;
                    break;
                }
            }
            if (empty($dinner)) {
                throw new ParameterException(['msg' => '当前时间不在就餐时间内']);
            }
            return $dinner;
        }

    }