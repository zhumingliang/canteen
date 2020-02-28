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
     *       "code": "34982",
     *       "type": 1,
     *     }
     * @apiParam (请求参数说明) {String} phone  用户输入手机号
     * @apiParam (请求参数说明) {String} code   用户输入验证码
     * @apiParam (请求参数说明) {String} type   验证人员类别：2:企业内部人员，1:外来人员
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     */
    public function bindPhone()
    {
        $phone = Request::param('phone');
        $code = Request::param('code');
        $type = Request::param('type');
        (new UserService())->bindPhone($phone, $code, $type);
        return json(new SuccessMessage());

    }

    /**
     * @api {POST} /api/v1/user/bindCanteen 公众号-用户选择进入饭堂
     * @apiGroup  Official
     * @apiVersion 1.0.1
     * @apiDescription  公众号-用户选择进入饭堂
     * @apiExample {post}  请求样例:
     *    {
     *       "canteen_id": 1
     *     }
     * @apiParam (请求参数说明) {int} canteen_id  饭堂id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function bindCanteen()
    {
        $canteen_id = Request::param('canteen_id');
        (new UserService())->bindCanteen($canteen_id);
        return json(new SuccessMessage());
    }

    /**
     * @api {GET} /api/v1/user/card 微信端-电子饭卡-获取用户该饭堂电子饭卡
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription  微信端-电子饭卡-获取用户该饭堂电子饭卡
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/user/card
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"url":""}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {int} url 饭卡地址
     */
    public function mealCard()
    {
        $card = (new UserService())->mealCard();
        return json(new SuccessMessageWithData(['data' => $card]));

    }

    /**
     * @api {GET} /api/v1/user/canteenMenus 微信端-菜品管理-获取用户管理饭堂信息
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription  微信端-菜品管理-获取用户管理饭堂信息
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/user/canteenMenus
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"c_id":4,"name":"饭堂1","dinners":[]},{"c_id":5,"name":"饭堂2","dinners":[]},{"c_id":1,"name":"饭堂","dinners":[]},{"c_id":6,"name":"饭堂","dinners":[{"id":5,"name":"早餐","menus":[]},{"id":6,"name":"中餐","menus":[{"id":1,"d_id":6,"category":"荤菜","status":1,"count":3},{"id":2,"d_id":6,"category":"汤","status":2,"count":0}]},{"id":7,"name":"晚餐","menus":[]}]},{"c_id":7,"name":"饭堂","dinners":[]}]}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {int} c_id 饭堂id
     * @apiSuccess (返回参数说明) {obj} name  饭堂名称
     * @apiSuccess (返回参数说明) {obj} dinner 餐次信息
     * @apiSuccess (返回参数说明) {int} id 餐次id
     * @apiSuccess (返回参数说明) {int} name 餐次名称
     * @apiSuccess (返回参数说明) {obj} menus 餐次菜单设置信息
     * @apiSuccess (返回参数说明) {int} id 餐次菜单设置id
     * @apiSuccess (返回参数说明) {int} d_id 餐次id
     * @apiSuccess (返回参数说明) {string} category 分类信息
     * @apiSuccess (返回参数说明) {string} status 1|固定；2|动态
     * @apiSuccess (返回参数说明) {string} count 可选菜品数量
     */
    public function userCanteenMenus()
    {
        $canteens = (new CanteenService())->adminCanteens();
        return json(new SuccessMessageWithData(['data' => $canteens]));

    }

    /**
     * @api {GET} /api/v1/user/canteens 微信端-获取当前用户可进入饭堂
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription  微信端-获取当前用户可进入饭堂
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/user/canteens
     * @apiSuccessExample {json} 企业内部人员返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":350,"company_id":3,"company":{"id":3,"name":"企业A"},"canteens":[{"id":1,"staff_id":350,"canteen_id":1,"info":{"id":1,"name":"大饭堂"}}]}]}
     * @apiSuccessExample {json} 外来人员返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"company_id":4,"company":"企业","canteen_id":5,"canteen":"饭堂"}]}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {obj} company 企业信息
     * @apiSuccess (返回参数说明) {string} company|name 企业名称
     * @apiSuccess (返回参数说明) {obj} canteens 企业下饭堂信息
     * @apiSuccess (返回参数说明) {obj} canteens|info  饭堂信息
     * @apiSuccess (返回参数说明) {int} info|id 饭堂id
     * @apiSuccess (返回参数说明) {string} info|name  饭堂名称
     */
    public function userCanteens()
    {
        $canteens = (new CanteenService())->userCanteens();
        return json(new SuccessMessageWithData(['data' => $canteens]));
    }

    /**
     * @api {GET} /api/v1/user/phone 微信端-获取当前用户手机号
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription  微信端-获取当前用户手机号
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/user/phone
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"phone":"18956225230"}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {string} phone 手机号
     */
    public function userPhone()
    {
        $user = (new UserService())->userInfo();
        return json(new SuccessMessageWithData(['data' => $user]));
    }


}