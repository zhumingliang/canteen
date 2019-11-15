<?php


namespace app\api\service;


use app\api\model\PayWxConfigT;
use app\lib\enum\CommonEnum;
use app\lib\exception\ParameterException;
use EasyWeChat\Factory;

class WeiXinPayService
{
    private $app_id = null;
    private $mch_id = null;
    private $key = null;
    private $notify_url = null;
    private $cert_path = null;
    private $key_path = null;


    public function __construct()
    {
        $company_id = Token::getCurrentTokenVar('current_company_id');
        $config = $this->getCompanyWXConfig($company_id);
        $this->app_id = $config['app_id'];
        $this->mch_id = $config['mch_id'];
        $this->key = $config['key'];
        $this->notify_url = $config['notify_url'];
        $this->cert_path = $config['cert_path'];
        $this->key_path = $config['key_path'];

    }

    private function getFactory()
    {
        if (empty($this->app_id) || empty($this->mch_id) || empty($this->key) || empty($this->notify_url)) {
            throw  new ParameterException(['msg' => '微信支付参数异常']);
        }
        $config = [
            // 必要配置
            'app_id' => $this->app_id,
            'mch_id' => $this->mch_id,
            'key' => $this->key,   // API 密钥
            // 如需使用敏感接口（如退款、发送红包等）需要配置 API 证书路径(登录商户平台下载 API 证书)
            'cert_path' => $this->cert_path, // XXX: 绝对路径！！！！
            'key_path' => $this->key_path,      // XXX: 绝对路径！！！！
            'notify_url' => $this->notify_url,     // 你也可以在下单时单独设置来想覆盖它
        ];
        $app = Factory::payment($config);
        return $app;
    }

    private function getCompanyWXConfig($company_id)
    {
        $config = PayWxConfigT::where('company_id', $company_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->find();
        if (empty($config)) {
            throw  new ParameterException(['msg' => '企业未配置微信支付参数']);
        }
        return $config;
    }

}