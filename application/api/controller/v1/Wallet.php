<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\WalletService;
use app\lib\exception\SuccessMessage;
use think\facade\Request;

class Wallet extends BaseController
{
    /**
     * @api {POST} /api/v1/waller/recharge/cash CMS管理端--充值管理--现金充值
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription     CMS管理端--充值管理--现金充值
     * @apiExample {post}  请求样例:
     *    {
     *       "money": 200,
     *       "remark": '备注',
     *       "detail":[{"phone":"18956225230","card_num":"123"},{"phone":"18956225230","card_num":"123"}]
     *     }
     * @apiParam (请求参数说明) {int} money 充值金额
     * @apiParam (请求参数说明) {int} remark 备注
     * @apiParam (请求参数说明) {obj} detail  充值人员信息json字符串
     * @apiParam (请求参数说明) {string} phone  充值用户手机号
     * @apiParam (请求参数说明) {string} card_num  充值用户卡号
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function rechargeCash()
    {
        $params = Request::param();
        (new WalletService())->rechargeCash($params);
        return json(new SuccessMessage());

    }

}