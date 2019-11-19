<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\SendSMSService;
use app\lib\exception\SuccessMessage;
use think\Cache;
use think\facade\Request;
use think\facade\Session;

class SendSMS extends BaseController
{
    /**
     * @api {POST} /api/v1/sms/send  微信端-发送验证手机验证码
     * @apiGroup  Official
     * @apiVersion 1.0.1
     * @apiDescription  微信端-发送验证手机验证码
     * @apiExample {post}  请求样例:
     *    {
     *       "phone":"18956225230"
     *     }
     * @apiParam (请求参数说明) {String} phone  手机号
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     */
    public function sendCode()
    {
        $phone = Request::param('phone');
        (new SendSMSService())->sendCode($phone,'register');
        return json(new SuccessMessage());

    }

}