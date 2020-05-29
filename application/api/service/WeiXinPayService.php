<?php


namespace app\api\service;

use app\api\model\PayWxConfigT;
use app\lib\enum\CommonEnum;
use app\lib\exception\ParameterException;
use EasyWeChat\Factory;
use think\facade\Env;

class WeiXinPayService
{


    public function getApp($company_id)
    {
        $config = PayWxConfigT::info($company_id);
        if (!$config) {
            throw  new ParameterException(['msg' => '企业未设置微信支付配置']);
        }
        if (empty($config->mch_id) || empty($config->app_id)) {
            throw  new ParameterException(['msg' => '微信支付配置异常']);
        }
        $sub_mch_id = $config->mch_id;
        $sub_app_id = $config->app_id;
        $certPath = Env::get('app_path') . 'lib/wxcert/';
        $config = [
            // 必要配置
            'app_id' => 'wx60311f2f47c86a3e',
            'mch_id' => '1555725021',
            'key' => '1234567890qwertyuiopasdfghjklzxc',   // API 密钥
            // 如需使用敏感接口（如退款、发送红包等）需要配置 API 证书路径(登录商户平台下载 API 证书)
            'cert_path' => $certPath . 'cert.pem', // XXX: 绝对路径！！！！
            'key_path' => $certPath . 'key.pem',      // XXX: 绝对路径！！！！
          //  'sub_appid' => $sub_app_id,
           // 'sub_mch_id' => $sub_mch_id,
            'notify_url' => 'http://canteen.tonglingok.com/api/v1/wallet/WXNotifyUrl',
            // 你也可以在下单时单独设置来想覆盖它
        ];
        $app = Factory::payment($config);
        $app->setSubMerchant($sub_mch_id);  // 子商户 AppID 为可选项

        return $app;
    }

    public function getPayInfo($data)
    {
        $app = $this->getApp($data['company_id']);
        $result = $app->order->unify([
            'body' => $data['body'],
            'out_trade_no' => $data['out_trade_no'],
            'total_fee' => $data['total_fee'],
            'trade_type' => 'JSAPI',
            'sign_type' => 'MD5',
            'openid' => $data['openid']
        ]);
       /* $jssdk = $app->jssdk;
        $config = $jssdk->sdkConfig($result['prepay_id']);
        print_r($config);*/
        //print_r($result);
        return $result;
    }

    public function refundOrder($company_id, $order_number, $refundNumber, $totalFee, $refundFee)
    {
        $app = $this->getApp($company_id);
        $totalFee = $totalFee * 100;
        $refundFee = $refundFee * 100;
        // 参数分别为：商户订单号、商户退款单号、订单金额、退款金额、其他参数
        $result = $app->refund->byOutTradeNumber($order_number, $refundNumber, $totalFee, $refundFee);
        if (empty($result['result_code']) || $result['result_code'] != 'SUCCESS' || $result['return_code'] != 'SUCCESS') {
            return [
                'res' => CommonEnum::STATE_IS_FAIL,
                'return_msg' => json_encode($result),
            ];
        }
        return [
            'res' => CommonEnum::STATE_IS_OK,
            'return_msg' => json_encode($result),
        ];

    }
}