<?php


namespace app\api\service;


use app\api\model\OfficialTemplateT;
use app\api\model\OrderT;
use app\lib\enum\CommonEnum;
use app\lib\enum\OrderEnum;
use app\lib\enum\PayEnum;
use app\lib\exception\ParameterException;
use app\lib\exception\UpdateException;
use app\lib\printer\Printer;
use app\lib\weixin\Template;

class TakeoutService
{
    const RECEIVE = 1;
    const RECEIVE_PRINTER = 2;
    const PRINTER = 3;
    const REFUND = 4;

    public function handelOrder($orderID, $type, $canteenID)
    {
        $orderIDArr = explode(',', $orderID);
        if (!count($orderIDArr)) {
            throw new ParameterException(['msg' => '订单id异常']);
        }
        switch ($type) {
            case self::RECEIVE :
                $this->receiveOrders($orderIDArr);
                break;
            case self::RECEIVE_PRINTER :
                $this->receiveAndPrint($orderIDArr, $canteenID);
                break;
            case self::PRINTER:
                $this->printOrders($orderIDArr, $canteenID);
                break;
            case self::REFUND:
                $this->refundOrder($orderIDArr);
                break;
        }


    }

    private function receiveOrders($orderIDArr)
    {
        $dataList = [];
        foreach ($orderIDArr as $k => $v) {
            array_push($dataList, [
                'id' => $v,
                'receive' => CommonEnum::STATE_IS_OK
            ]);
        }
        $res = (new OrderT())->saveAll($dataList);
        if (!$res) {
            throw new  UpdateException(['msg' => '更新订单失败']);
        }
    }

    private function receiveAndPrint($orderIDArr, $canteenID)
    {
        $this->receiveOrders($orderIDArr);
        $this->printOrders($orderIDArr, $canteenID);
    }

    private function printOrders($orderIDArr, $canteenId)
    {
        $sn = (new Printer())->checkPrinter($canteenId, 4);
        foreach ($orderIDArr as $k => $v) {
            (new Printer())->printOutsiderOrderDetail($v, $sn);
        }
    }

    public function refundOrder($orderIDArr)
    {

        foreach ($orderIDArr as $k => $v) {
            $order = OrderT::infoToRefund($v);
            $order->state = OrderEnum::REFUND;
            //检测是否需要微信退款
            if ($order->pay_way == PayEnum::PAY_WEIXIN) {
                (new OrderService())->refundWxOrder($v);
            }
            $res = $order->save();
            if (!$res) {
                throw new  UpdateException(['msg' => '更新订单失败']);
            }
            $this->sendTemplate($order['user']['openid'], $order['money']);
        }

    }

    private function sendTemplate($openid, $money)
    {
        $data = [
            'first' => "退款通知：您的外卖订单已被饭堂退回，订单金额会尽快退回。",
            'reason' => "饭堂操作退回（固定退款原因）",
            'refund' => "$money 元",
            'remark' => "如有疑问，请联系饭堂。"
        ];
        $templateConfig = OfficialTemplateT::template('refund');
        if ($templateConfig) {
            $res = (new Template())->send($openid, $templateConfig->template_id, $templateConfig->url, $data);
            if ($res['errcode'] != 0) {
                LogService::save(json_encode($res));
            }
        }

    }
}