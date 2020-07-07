<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\QrcodeService;
use app\api\service\Token as TokenService;

class Reception extends BaseController
{
    /**
     * 新增接待票申请
     */
    public function save()
    {
        //生成接待票就餐码
        $code = getRandChar(8);
        $qrcodeUrl = $this->qrCode($code);

        //获取人员信息
        $uID = TokenService::getCurrentUid();

    }

    /**
     * 修改接待票申请
     */
    public function update()
    {

    }

    /**
     * 接待票状态修改
     */
    public function handel()
    {

    }

    /**
     * 获取接待票详情
     */
    public function reception()
    {

    }

    /**
     * 后台管理获取接待票列表
     */
    public function receptionsForCMS()
    {

    }

    /**
     * 微信端管理获取接待票列表
     */
    public function receptionsForOfficial()
    {

    }

    /**
     * 生成接待票二维码 每张二维码的code是8位数随机码
     * @param  $code
     * @return string
     */
    private function qrCode($code)
    {
        $url = "reception&$code";
        return (new QrcodeService())->qr_code($url);

    }

    /**
     * 获取当前用户信息
     */
    public function userInfo()
    {

    }

    public function getReceptionMoney()
    {

    }
}