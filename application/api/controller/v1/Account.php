<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\AccountService;
use app\lib\exception\SuccessMessage;
use think\facade\Request;

class Account extends BaseController
{
    /**
     * @api {POST} /api/v1/account/save  PC端-新增企业账户
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription     PC端-新增企业账户
     * @apiExample {post}  请求样例:
     *    {
     *       "province": "广东省",
     *       "city": "江门市",
     *       "area": "蓬江区",
     *       "address": "江门市白石大道东4号路3栋 ",
     *       "name": "张三",
     *       "phone": "18956225230",
     *       "sex": 1
     *     }
     * @apiParam (请求参数说明) {string} province  省
     * @apiParam (请求参数说明) {string} city  城市
     * @apiParam (请求参数说明) {string} area  区
     * @apiParam (请求参数说明) {string} address  详细地址
     * @apiParam (请求参数说明) {string} name  姓名
     * @apiParam (请求参数说明) {string} phone  手机号
     * @apiParam (请求参数说明) {int} sex  性别：1|男；2|女
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function save()
    {
        $params = Request::param();
        (new AccountService())->save($params);
        return json(new SuccessMessage());

    }

}