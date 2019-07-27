<?php
/**
 * Created by PhpStorm.
 * User: mingliang
 * Date: 2018/5/27
 * Time: 上午9:53
 */

namespace app\api\controller\v1;


use app\api\model\FormidT;
use app\api\model\TestT;
use app\api\model\UserT;
use app\api\service\AdminToken;
use app\api\service\DriverToken;
use app\api\service\UserToken;
use app\api\validate\TokenGet;
use app\lib\enum\CommonEnum;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use app\lib\exception\TokenException;
use think\Controller;
use think\facade\Cache;
use think\facade\Request;

class Token extends Controller
{
    /**
     * @api {POST} /api/v1/token/admin  CMS管理端-获取登陆token
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription  后台用户登录
     * @apiExample {post}  请求样例:
     *    {
     *       "account": "18956225230",
     *       "passwd": "a123456"
     *     }
     * @apiParam (请求参数说明) {String} phone    用户账号
     * @apiParam (请求参数说明) {String} pwd   用户密码
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"token":"fe6ed7b4a89aab3a31d0606a55116a49","role":"系统超级管理员","grade":1}}
     * @apiSuccess (返回参数说明) {int} grade 用户等级
     * @apiSuccess (返回参数说明) {int} role 用户角色名称
     * @apiSuccess (返回参数说明) {string} token 口令令牌，每次请求接口需要传入，有效期 2 hours
     */
    public function getAdminToken()
    {
        $params = $this->request->param();
        $at = new AdminToken($params['account'], $params['passwd']);
        $token = $at->get();
        return json(new SuccessMessageWithData(['data' => $token]));
    }

    /**
     * @api {GET} /api/v1/token/login/out  CMS管理端-退出登陆
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS退出当前账号登陆。
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/token/loginOut
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     *
     */
    public function loginOut()
    {

        $token = Request::header('token');
        Cache::rm($token);
        return json(new SuccessMessage());
    }


}