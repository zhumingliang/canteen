<?php


namespace app\api\service\v2;


use app\api\model\PayT;
use app\api\model\RechargeCashT;
use app\api\service\LogService;
use app\api\service\Token;
use app\api\service\WeiXinPayService;
use app\lib\enum\CommonEnum;
use app\lib\enum\PayEnum;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;

class WalletService
{
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
        $order = PayT::getPreOrder($order_id);

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


    public function rechargeCash($params)
    {
        $detail = json_decode($params['detail'], true);
        if (empty($detail)) {
            throw new ParameterException(['msg' => '充值用户信息格式错误']);
        }
        $money = $params['type'] == 1 ? abs($params['money']) : 0 - abs($params['money']);
        $company_id = Token::getCurrentTokenVar('company_id');
        $admin_id = Token::getCurrentUid();
        $account_id = empty($params['account_id']) ? 0 : $params['account_id'];
        $data = (new \app\api\service\WalletService())->prefixDetail($company_id, $admin_id, $detail, $account_id, $money, $params['remark'], $params['type']);
        $cash = (new RechargeCashT())->saveAll($data);
        if (!$cash) {
            throw new SaveException();
        }
    }

}