<?php


namespace app\api\service;


use app\lib\exception\ParameterException;
use EasyWeChat\Factory;

class WeiXinPayService
{
    private $app_id = null;
    private $mch_id = null;
    private $key = null;
    private $notify_url = null;


    public function __construct()
    {

    }

    private function getFactory()
    {
        if (empty($app_id) || empty($this->mch_id) || empty($this->key) || empty($this->notify_url)) {
            throw  new ParameterException(['msg' => '微信支付参数异常']);
        }
        $config = [
            // 必要配置
            'app_id' => $this->app_id,
            'mch_id' => $this->mch_id,
            'key' => $this->key,   // API 密钥

            // 如需使用敏感接口（如退款、发送红包等）需要配置 API 证书路径(登录商户平台下载 API 证书)
            // 'cert_path' => 'path/to/your/cert.pem', // XXX: 绝对路径！！！！
            //   'key_path' => 'path/to/your/key',      // XXX: 绝对路径！！！！

            'notify_url' => $this->notify_url,     // 你也可以在下单时单独设置来想覆盖它
        ];
        $app = Factory::payment($config);
        return $app;
    }

}