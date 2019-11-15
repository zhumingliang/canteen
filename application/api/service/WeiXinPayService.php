<?php


namespace app\api\service;

class WeiXinPayService
{

    public function getPayInfo($data)
    {
        $app = app('wechat.payment');
        $result = $app->order->unify([
            'body' => $data['body'],
            'out_trade_no' => $data['out_trade_no'],
            'total_fee' => $data['total_fee'],
            'trade_type' => 'JSAPI', // 请对应换成你的支付方式对应的值类型
            'openid' => $data['openid']
        ]);
        return $result;
    }

}