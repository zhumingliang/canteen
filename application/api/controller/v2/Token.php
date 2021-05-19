<?php


namespace app\api\controller\v2;


use app\api\controller\BaseController;
use app\api\service\AdminToken;
use app\api\service\v2\AdminVerifyToken;
use app\lib\exception\SuccessMessageWithData;
use think\facade\Request;

class Token extends BaseController
{
    /**
     * @api {POST} /api/v2/token/admin  CMS管理端-获取登录token
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription  后台用户登录
     * @apiExample {post}  请求样例:
     *    {
     *       "account": "zml",
     *       "passwd": "a11111",
     *       "code": 123
     *     }
     * @apiParam (请求参数说明) {String} account    用户账号
     * @apiParam (请求参数说明) {String} passwd   用户密码
     * @apiParam (请求参数说明) {String} code   验证码
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"token":"fe6ed7b4a89aab3a31d0606a55116a49","role":"系统超级管理员","grade":1}}
     * @apiSuccess (返回参数说明) {int} grade 用户等级:1|系统管理员；2|企业系统管理员；3|企业内部角色
     * @apiSuccess (返回参数说明) {int} role 用户角色名称
     * @apiSuccess (返回参数说明) {string} token 口令令牌，每次请求接口需要传入，有效期 24 hours
     */
    public function getAdminToken()
    {
        $params = $this->request->param();
        $code = Request::param('code');
        $at = new AdminVerifyToken($params['account'], $params['passwd'],$code);
        $token = $at->get();
        return json(new SuccessMessageWithData(['data' => $token]));
    }


}