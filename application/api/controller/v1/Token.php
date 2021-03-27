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
use app\api\service\LogService;
use app\api\service\MachineToken;
use app\api\service\OfficialToken;
use app\api\service\SupplierToken;
use app\api\service\UserService;
use app\api\service\UserToken;
use app\api\validate\TokenGet;
use app\lib\enum\CommonEnum;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use app\lib\exception\TokenException;
use GatewayClient\Gateway;
use think\Controller;
use think\facade\Cache;
use think\facade\Request;
use zml\tp_tools\Redis;

class  Token extends Controller
{
    /**
     * @api {POST} /api/v1/token/admin  CMS管理端-获取登录token
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription  后台用户登录
     * @apiExample {post}  请求样例:
     *    {
     *       "account": "zml",
     *       "passwd": "a11111"
     *     }
     * @apiParam (请求参数说明) {String} account    用户账号
     * @apiParam (请求参数说明) {String} passwd   用户密码
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"token":"fe6ed7b4a89aab3a31d0606a55116a49","role":"系统超级管理员","grade":1}}
     * @apiSuccess (返回参数说明) {int} grade 用户等级:1|系统管理员；2|企业系统管理员；3|企业内部角色
     * @apiSuccess (返回参数说明) {int} role 用户角色名称
     * @apiSuccess (返回参数说明) {string} token 口令令牌，每次请求接口需要传入，有效期 24 hours
     */
    public function getAdminToken()
    {
        $params = $this->request->param();
        $client_id = Request::param('client_id');
        $at = new AdminToken($params['account'], $params['passwd'], $client_id);
        $token = $at->get();
        return json(new SuccessMessageWithData(['data' => $token]));
    }

    /**
     * @api {POST} /api/v1/token/supplier  CMS管理端-获取供应商登陆token
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription  后台用户登录
     * @apiExample {post}  请求样例:
     *    {
     *       "account": "zml",
     *       "passwd": "a11111"
     *     }
     * @apiParam (请求参数说明) {String} account    用户账号
     * @apiParam (请求参数说明) {String} pwd   用户密码
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"token":"fe6ed7b4a89aab3a31d0606a55116a49","name":"系统超级管理员"}}
     * @apiSuccess (返回参数说明) {string} token 口令令牌，每次请求接口需要传入，有效期 24 hours
     * @apiSuccess (返回参数说明) {string} name 供应商名称
     */
    public function getSupplierToken()
    {
        $params = $this->request->param();
        $at = new SupplierToken($params['account'], $params['passwd']);
        $token = $at->get();
        return json(new SuccessMessageWithData(['data' => $token]));
    }

    /**
     * @api {GET} /api/v1/token/login/out  CMS管理端-退出登陆
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS退出当前账号登陆。
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/token/login/out
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {string} token 口令令牌，每次请求接口需要传入，有效期 24 hours
     */
    public function loginOut()
    {
        $token = Request::header('token');
        // Cache::rm($token);
        $type = \app\api\service\Token::getCurrentTokenVar('type');
        if ($type == 'official') {
            (new UserService())->clearUserInfo();
        }
        Redis::instance()->delete($token);
        return json(new SuccessMessage());
    }

    /**
     * @api {GET} /api/v1/token/official 公众号-获取登录token
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription  公众号获取登录token
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/token/official?code=121
     * @apiParam (请求参数说明) {String} code    授权token
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0,"code":200,"data":{"token":"26837cbfd8c9c55d830d3f726927bfed","phone":1,"canteen_selected":2,"outsiders":2}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} outsiders 用户是否为外来人员:1|是；2|否
     * @apiSuccess (返回参数说明) {int} phone 用户是否绑定手机号:1|绑定；2|未绑定；phone=2时需要用户绑定手机号，phone=1时，检测canteen_selected字段
     * @apiSuccess (返回参数说明) {int} canteen_selected 用户是否已选择饭堂：1|选择；2|未选择 ，canteen_selected=1时跳转首页，canteen_selected=2时通过接口获取当前用户可选择饭堂
     */
    public function getOfficialToken()
    {
        $code = Request::param('code');
        $token = (new OfficialToken())->get($code);
        return json(new SuccessMessageWithData(['data' => $token]));

    }

    /**
     * @api {POST} /api/v1/token/machine 消费机-获取登录token
     * @apiGroup  Machine
     * @apiVersion 3.0.0
     * @apiDescription  消费机-获取登录token
     * @apiExample {post}  请求样例:
     *    {
     *       "code": 212121,
     *       "passwd": "a11111",
     *       "client_id": 1
     *     }
     * @apiParam (请求参数说明) {String} code    设备唯一识别码
     * @apiParam (请求参数说明) {String} passwd    登录密码
     * @apiParam (请求参数说明) {String} client_id   websocket服务返回的登录id
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0,"code":200,"data":{"token":"26837cbfd8c9c55d830d3f726927bfed"}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {string} token 登录token：有效期：7天
     */
    public function getMachineToken()
    {
        $code = Request::param('code');
        $passwd = Request::param('passwd');
        $client_id = Request::param('client_id');
        $token = (new MachineToken())->get($code, $passwd, $client_id);
        return json(new SuccessMessageWithData(['data' => $token]));
    }

    /**
     * @api {POST} /api/v1/token/admin/bind CMS管理端-绑定webSocket
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription  CMS管理端-绑定webSocket
     * @apiExample {post}  请求样例:
     *    {
     *       "client_id": 1
     *     }
     * @apiParam (请求参数说明) {String} client_id   websocket服务返回的登录id
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0,"code":200}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function bindSocket($client_id)
    {
        $adminId = \app\api\service\Token::getCurrentUid();
        $group = 'canteen:admin';
        Gateway::joinGroup($client_id, $group);
        Gateway::bindUid($client_id, $adminId);
        return json(new SuccessMessage());
    }

}