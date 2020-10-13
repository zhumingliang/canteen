<?php


namespace app\api\service;


use app\api\model\OfficialTemplateT;
use app\api\model\OrderParentT;
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

    public function handelOrder($oneId, $moreId, $type, $canteenID)
    {
        $oneIDArr = explode(',', $oneId);
        $moreIDArr = explode(',', $moreId);
        if (!count($oneIDArr) && !count($moreIDArr)) {
            throw new ParameterException(['msg' => '订单id异常']);
        }
        switch ($type) {
            case self::RECEIVE :
                $this->receiveOrders($oneIDArr, $moreIDArr);
                break;
            case self::RECEIVE_PRINTER :
                $this->receiveAndPrint($oneIDArr, $moreIDArr, $canteenID);
                break;
            case self::PRINTER:
                $this->printOrders($oneIDArr, $moreIDArr, $canteenID);
                break;
            case self::REFUND:
                $this->refundOrder($oneIDArr, $moreIDArr);
                break;
        }


    }

    private function receiveOrders($oneIDArr, $moreIDArr)
    {
        $oneList = [];
        $moreList = [];
        if (count($oneIDArr)) {
            foreach ($oneIDArr as $k => $v) {
                if (!$v) {
                    continue;
                }
                array_push($oneList, [
                    'id' => $v,
                    'receive' => CommonEnum::STATE_IS_OK
                ]);
            }
            $res = (new OrderT())->saveAll($oneList);
            if (!$res) {
                throw new  UpdateException(['msg' => '更新订单失败']);
            }
        }
        if (count($moreIDArr)) {
            foreach ($moreIDArr as $k => $v) {
                if (!$v) {
                    continue;
                }
                array_push($moreList, [
                    'id' => $v,
                    'receive' => CommonEnum::STATE_IS_OK
                ]);
            }
            $res = (new OrderParentT())->saveAll($moreList);
            if (!$res) {
                throw new  UpdateException(['msg' => '更新订单失败']);
            }
        }
        //批量发送模板
        foreach ($oneIDArr as $k => $v) {
            $order = OrderT::infoToReceive($v);
            if ($order) {
                $this->sendReceiveTemplate($order['user']['openid'], $order['ordering_date'], $order['dinner']['name'], $order['canteen']['name']);
            }
        }
        foreach ($moreIDArr as $k => $v) {
            $order = OrderParentT::infoToReceive($v);
            if ($order) {
                $this->sendReceiveTemplate($order['user']['openid'], $order['ordering_date'], $order['dinner']['name'], $order['canteen']['name']);
            }
        }
    }

    private function receiveAndPrint($oneIDArr, $moreIDArr, $canteenID)
    {
        $this->receiveOrders($oneIDArr, $moreIDArr);
        $this->printOrders($oneIDArr, $moreIDArr, $canteenID);
    }

    private function printOrders($oneIDArr, $moreIDArr, $canteenId)
    {
        $sn = (new Printer())->checkPrinter($canteenId, 4);
        if (!empty($oneIDArr)) {
            foreach ($oneIDArr as $k => $v) {
                if (!$v) {
                    continue;
                }
                (new Printer())->printOutsiderOrderDetail($v, $sn, 'one');
            }
        }
        if (!empty($moreIDArr)) {
            foreach ($moreIDArr as $k => $v) {
                if (!$v) {
                    continue;
                }
                (new Printer())->printOutsiderOrderDetail($v, $sn, 'more');
            }
        }

    }

    public function refundOrder($oneIDArr, $moreIDArr)
    {

        if (count($oneIDArr)) {
            foreach ($oneIDArr as $k => $v) {
                if (!$v) {
                    continue;
                }
                $order = OrderT::infoToRefund($v);
                $order->state = OrderEnum::REFUND;
                //检测是否需要微信退款
                if ($order->pay_way == PayEnum::PAY_WEIXIN) {
                    (new OrderService())->refundWxOrder($v, 'one');
                }
                $res = $order->save();
                if (!$res) {
                    throw new  UpdateException(['msg' => '更新订单失败']);
                }
                $this->sendRefundTemplate($order['user']['openid'], $order['money']);
            }
        }

        if (count($moreIDArr)) {
            foreach ($moreIDArr as $k => $v) {
                if (!$v) {
                    continue;
                }
                $order = OrderParentT::infoToRefund($v);
                $order->state = OrderEnum::REFUND;
                //检测是否需要微信退款
                if ($order->pay_way == PayEnum::PAY_WEIXIN) {
                    (new OrderService())->refundWxOrder($v, 'more');
                }
                $res = $order->save();
                if (!$res) {
                    throw new  UpdateException(['msg' => '更新订单失败']);
                }
                $this->sendRefundTemplate($order['user']['openid'], $order['money']);
            }
        }

    }

    private function sendRefundTemplate($openid, $money)
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

    private function sendReceiveTemplate($openid, $ordering_date, $dinner, $canteen)
    {
        $data = [
            'first' => "您的配送订单已被食堂接单，请等候送达。",
            'keyword1' => $ordering_date,
            'keyword2' => $dinner,
            'keyword3' => $canteen,
            'remark' => "如有疑问，请联系食堂负责人。"
        ];
        $templateConfig = OfficialTemplateT::template('receive');
        if ($templateConfig) {
            $res = (new Template())->send($openid, $templateConfig->template_id, $templateConfig->url, $data);
            if ($res['errcode'] != 0) {
                LogService::save(json_encode($res));
            }
        }
    }
}