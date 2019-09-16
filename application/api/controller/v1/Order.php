<?php
/**
 * Created by PhpStorm.
 * User: 明良
 * Date: 2019/9/4
 * Time: 15:52
 */

namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\model\OnlineOrderingT;
use app\api\model\PersonalChoiceT;
use app\api\service\OrderService;
use app\lib\enum\CommonEnum;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use app\lib\exception\UpdateException;
use think\facade\Request;

class Order extends BaseController
{
    /**
     * @api {POST} /api/v1/order/personChoice/save 微信端-个人选菜-新增订单
     * @apiGroup   Official
     * @apiVersion 3.0.0
     * @apiDescription    微信端-个人选菜-新增订单
     * @apiExample {post}  请求样例:
     *    {
     *       "ordering_date": "2019-09-07",
     *       "dinner_id": 1,
     *       "dinner": "早餐",
     *       "type": 1,
     *       "count": 1,
     *       "detail":[{"menu_id":1,"foods":[{"food_id":1,"price":5,"count":1},{"food_id":2,"price":5,"count":1}]}]
     *     }
     * @apiParam (请求参数说明) {string} ordering_date  订餐日期
     * @apiParam (请求参数说明) {int} dinner_id 餐次id
     * @apiParam (请求参数说明) {int} dinner 餐次名称
     * @apiParam (请求参数说明) {int} type 就餐类别：1|食堂；2|外卖
     * @apiParam (请求参数说明) {int} count 订餐数量
     * @apiParam (请求参数说明) {obj} detail 订餐菜品明细
     * @apiParam (请求参数说明) {string} detail|menu_id 菜品类别id
     * @apiParam (请求参数说明) {obj} detail|foods 菜品明细
     * @apiParam (请求参数说明) {string} detail|foods|food_id 菜品id
     * @apiParam (请求参数说明) {string} detail|foods|price 菜品实时单价
     * @apiParam (请求参数说明) {string} detail|foods|count 菜品数量
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"id":1}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id 订单id
     */
    public function personChoice()
    {
        $params = Request::param();
        $order = (new OrderService())->personChoice($params);
        return json(new SuccessMessageWithData(['data' => $order]));

    }

    /**
     * @api {GET} /api/v1/order/userOrdering  微信端-线上订餐-获取用户所有订餐信息（今天及今天以后）
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription  微信端-线上订餐-获取用户所有订餐信息（今天及今天以后）
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/order/userOrdering
     * @apiSuccessExample {json} 系统功能模块返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":1,"c_id":6,"canteen":"饭堂1","d_id":6,"dinner":"中餐","ordering_date":"2019-09-07","count":1,"type":"person_choice"}]}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id  订餐记录id
     * @apiSuccess (返回参数说明) {int} c_id 饭堂id
     * @apiSuccess (返回参数说明) {string} canteen 饭堂名称
     * @apiSuccess (返回参数说明) {int} d_id 餐次id
     * @apiSuccess (返回参数说明) {string} dinner 餐次名称
     * @apiSuccess (返回参数说明) {string} ordering_date 订餐日期
     * @apiSuccess (返回参数说明) {int} count 订餐数量
     * @apiSuccess (返回参数说明) {string}ordering_type 订餐方式：online|线上订餐；personal_choice|个人订餐
     */
    public function userOrdering()
    {
        $orders = (new OrderService())->userOrdering();
        return json(new SuccessMessageWithData(['data' => $orders]));

    }

    /**
     * @api {GET} /api/v1/order/online/info  微信端-线上订餐-获取饭堂餐次配置信息
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription  微信端-线上订餐-获取饭堂餐次配置信息（确定是否可以订餐、可以定几餐）
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/order/online/info
     * @apiSuccessExample {json} 系统功能模块返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":7,"c_id":6,"name":"早餐","type":"day","create_time":"2019-07-30 02:07:17","type_number":10,"meal_time_begin":"07:00:00","meal_time_end":"08:00:00","limit_time":"09:00:00","ordered_count":1},{"id":6,"c_id":6,"name":"中餐","type":"day","create_time":"2019-07-30 02:07:17","type_number":10,"meal_time_begin":"12:00:00","meal_time_end":"13:00:00","limit_time":"10:00:00"},{"id":7,"c_id":6,"name":"晚餐","type":"day","create_time":"2019-07-30 11:24:36","type_number":10,"meal_time_begin":"18:00:00","meal_time_end":"19:00:00","limit_time":"10:00:00"}]}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id  餐次id
     * @apiSuccess (返回参数说明) {string} name  餐次名称
     * @apiSuccess (返回参数说明) {string} type  时间设置类别：day|week 1、前n天是填写数字，说明每天的餐需要提前一个天数来订餐2、周，是只能填写周一到周日，说明一周的订餐规定需要在每周某天进行下周一整周的订餐
     * @apiSuccess (返回参数说明) {int} type_number 订餐时间类别对应数量（week：0-6；周日-周六）
     * @apiSuccess (返回参数说明) {string} limit_time  订餐限制时间
     * @apiSuccess (返回参数说明) {int} ordered_count  可订餐数量
     */
    public function infoForOnline()
    {
        $info = (new OrderService())->infoForOnline();
        return json(new SuccessMessageWithData(['data' => $info]));
    }

    /**
     * @api {POST} /api/v1/order/online/save 微信端--线上订餐--新增订单
     * @apiGroup   Official
     * @apiVersion 3.0.0
     * @apiDescription    微信端--线上订餐--新增订单
     * @apiExample {post}  请求样例:
     *    {
     *       "detail":[{"d_id":5,"ordering":[{"ordering_date":"2019-10-01","count":1},{"ordering_date":"2019-10-02","count":1}]},{"d_id":6,"ordering":[{"ordering_date":"2019-10-01","count":1},{"ordering_date":"2019-10-02","count":1}]}]
     *     }
     * @apiParam (请求参数说明) {obj} detail  订餐明细
     * @apiParam (请求参数说明) {int} detail|d_id  餐次id
     * @apiParam (请求参数说明) {obj} detail|ordering 指定餐次订餐明细
     * @apiParam (请求参数说明) {string} detail|ordering|ordering_date  订餐日期
     * @apiParam (请求参数说明) {string} detail|ordering|count  订餐数量
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function orderingOnline()
    {
        $detail = Request::param('detail');
        (new OrderService())->orderingOnline($detail);
        return json(new SuccessMessage());

    }

    /**
     * @api {POST} /api/v1/order/cancel 微信端-取消订餐
     * @apiGroup   Official
     * @apiVersion 3.0.0
     * @apiDescription 微信端-取消订餐（线上订餐/个人选菜）
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 1
     *     }
     * @apiParam (请求参数说明) {string} id  订餐id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function orderCancel()
    {
        $id = Request::param('id');
        (new OrderService())->orderCancel($id);
        return json(new SuccessMessage());
    }

    /**
     * @api {POST} /api/v1/order/changeCount 微信端---个人选菜/线上订餐---修改订单预定数量
     * @apiGroup   Official
     * @apiVersion 3.0.0
     * @apiDescription    微信端---个人选菜/线上订餐---修改订单预定数量
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 1,
     *       "count": 1,
     *     }
     * @apiParam (请求参数说明) {int} id  订单id
     * @apiParam (请求参数说明) {int} count 订餐数量
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function changeOrderCount()
    {
        $id = Request::param('id');
        $count = Request::param('count');
        (new OrderService())->changeOrderCount($id, $count);
        return json(new SuccessMessage());

    }

    /**
     * @api {POST} /api/v1/order/changeFoods 微信端---个人选菜---修改订单菜品信息
     * @apiGroup   Official
     * @apiVersion 3.0.0
     * @apiDescription    微信端---个人选菜---修改订单菜品信息
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 1,
     *       "detail": [{"menu_id":1,"add_foods":[{"detail_id":1,"food_id":1,"price":5,"count":1},{"food_id":1,"price":5,"count":1},{"food_id":2,"price":5,"count":1}],"update_foods":[{"detail_id":1,"count":1}],"cancel_foods":"3,4"}]
     *     }
     * @apiParam (请求参数说明) {int} id  订单id
     * @apiParam (请求参数说明) {int} count 订餐数量
     * @apiParam (请求参数说明) {obj} detail 订餐菜品明细
     * @apiParam (请求参数说明) {string} detail|menu_id 菜品类别id
     * @apiParam (请求参数说明) {obj} detail|add_foods 新增菜品明细
     * @apiParam (请求参数说明) {string} detail|add_foods|food_id 菜品id
     * @apiParam (请求参数说明) {string} detail|add_foods|price 菜品实时单价
     * @apiParam (请求参数说明) {string} detail|add_foods|count 菜品数量
     * @apiParam (请求参数说明) {obj} detail|update_foods 修改菜品明细
     * @apiParam (请求参数说明) {string} detail|update_foods|detail_id 订单菜品明细id
     * @apiParam (请求参数说明) {string} detail|update_foods|count 修改菜品数量
     * @apiParam (请求参数说明) {string} detail|cancel_foods 取消菜品id列表，多个用逗号分隔，此id来自于订单信息中detail_id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function changeOrderFoods()
    {
        $params = Request::param();
        (new OrderService())->changeOrderFoods($params);
        return json(new SuccessMessage());

    }


}