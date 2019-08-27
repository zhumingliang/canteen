<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\CanteenService;
use app\api\service\UserService;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use think\facade\Request;

class User extends BaseController
{
    /**
     * @api {POST} /api/v1/user/bindPhone 公众号-绑定手机号
     * @apiGroup  Official
     * @apiVersion 1.0.1
     * @apiDescription  公众号-绑定手机号
     * @apiExample {post}  请求样例:
     *    {
     *       "phone": "18956225230",
     *       "code": "34982"
     *     }
     * @apiParam (请求参数说明) {String} phone  用户输入手机号
     * @apiParam (请求参数说明) {String} code   用户输入验证码
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"count":1,"companies":[{"id":9,"company_id":2,"company":"一级企业"}]}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {int} count 用户归属企业数量：0|无企业；1|一个企业，后台会默认绑定无需再操作；2|多个企业，需要前端展示，客户再选择绑定
     * @apiSuccess (返回参数说明) {obj} companies  用户企业信息
     * @apiSuccess (返回参数说明) {int} company_id  企业ID
     * @apiSuccess (返回参数说明) {String} company  企业名称
     */
    public function bindPhone()
    {
        $phone = Request::param('phone');
        $code = Request::param('code');
        $bindRes = (new UserService())->bindPhone($phone, $code);
        return json(new SuccessMessageWithData(['data' => $bindRes]));

    }

    /**
     * @api {POST} /api/v1/user/bindCompany 公众号-用户选择进入企业（多企业情况）
     * @apiGroup  Official
     * @apiVersion 1.0.1
     * @apiDescription  公众号-绑定手机号
     * @apiExample {post}  请求样例:
     *    {
     *       "company_id": 1
     *     }
     * @apiParam (请求参数说明) {int} company_id  企业id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function bindCompany()
    {
        $company_id = Request::param('company_id');
        (new UserService())->bindCompany($company_id);
        return json(new SuccessMessage());
    }

    public function mealCard()
    {
        $card = (new UserService())->mealCard();

    }

    /**
     * @api {GET} /api/v1/user/canteenMenus 微信端-菜单管理-获取用户管理饭堂信息
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription  微信端-菜单管理-获取用户管理饭堂信息
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/user/canteenMenus
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"c_id":6,"name":"饭堂1","dinners":[{"id":5,"name":"早餐","menus":[]},{"id":6,"name":"中餐","menus":[{"id":1,"d_id":6,"category":"荤菜"},{"id":2,"d_id":6,"category":"汤"}]},{"id":7,"name":"晚餐","menus":[]}]}]}
     * @apiSuccess (返回参数说明) {int} c_id 饭堂id
     * @apiSuccess (返回参数说明) {obj} name  饭堂名称
     * @apiSuccess (返回参数说明) {obj} dinner 餐次信息
     * @apiSuccess (返回参数说明) {int} dinner|id 餐次id
     * @apiSuccess (返回参数说明) {int} dinner|name 餐次名称
     * @apiSuccess (返回参数说明) {obj} dinner|menus 餐次菜单设置信息
     * @apiSuccess (返回参数说明) {int} dinner|menus|id 餐次菜单设置id
     * @apiSuccess (返回参数说明) {int} dinner|menus|d_id 餐次id
     * @apiSuccess (返回参数说明) {string} dinner|menus|category 分类信息
     */
    public function userCanteenMenus()
    {
        $canteens = (new CanteenService())->userCanteens();
        return json(new SuccessMessageWithData(['data' => $canteens]));

    }

}