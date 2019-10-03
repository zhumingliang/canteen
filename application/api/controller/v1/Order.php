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
     *       "detail":[{"menu_id":1,"foods":[{"food_id":1,"name":"商品1","price":5,""count":1},{"food_id":2,"name":"商品1","price":5,"count":1}]}]
     *     }
     * @apiParam (请求参数说明) {string} ordering_date  订餐日期
     * @apiParam (请求参数说明) {int} dinner_id 餐次id
     * @apiParam (请求参数说明) {int} dinner 餐次名称
     * @apiParam (请求参数说明) {int} type 就餐类别：1|食堂；2|外卖
     * @apiParam (请求参数说明) {int} count 订餐数量
     * @apiParam (请求参数说明) {obj} detail 订餐菜品明细
     * @apiParam (请求参数说明) {string} detail|menu_id 菜品类别id
     * @apiParam (请求参数说明) {obj} detail|foods 菜品明细
     * @apiParam (请求参数说明) {string} foods|food_id 菜品id
     * @apiParam (请求参数说明) {string} foods|price 菜品实时单价
     * @apiParam (请求参数说明) {string} foods|count 菜品数量
     * @apiParam (请求参数说明) {string} foods|name 菜品名称
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
     * @apiSuccessExample {json} 返回样例:
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
     * @apiSuccessExample {json} 返回样例:
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
     * @api {POST} /api/v1/order/changeCount 微信端---线上订餐---修改订单预定数量
     * @apiGroup   Official
     * @apiVersion 3.0.0
     * @apiDescription    微信端---线上订餐---修改订单预定数量
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
     *       "count": 2,
     *       "detail": [{"menu_id":1,"add_foods":[{"food_id":1,"name":"商品1","price":5,"count":1},{"food_id":1,"name":"商品1","price":5,"count":1},{"food_id":2,"name":"商品1","price":5,"count":1}],"update_foods":[{"detail_id":1,"count":1}],"cancel_foods":"3,4"}]
     *     }
     * @apiParam (请求参数说明) {int} id  订单id
     * @apiParam (请求参数说明) {int} count 订餐数量
     * @apiParam (请求参数说明) {obj} detail 订餐菜品明细
     * @apiParam (请求参数说明) {string} detail|menu_id 菜品类别id
     * @apiParam (请求参数说明) {obj} detail|add_foods 新增菜品明细
     * @apiParam (请求参数说明) {string} add_foods|food_id 菜品id
     * @apiParam (请求参数说明) {string} add_foods|price 菜品实时单价
     * @apiParam (请求参数说明) {string} add_foods|count 菜品数量
     * @apiParam (请求参数说明) {string} add_foods|price 菜品实时单价
     * @apiParam (请求参数说明) {string} add_foods|name 菜品名称
     * @apiParam (请求参数说明) {obj} detail|update_foods 修改菜品明细
     * @apiParam (请求参数说明) {string} update_foods|detail_id 订单菜品明细id
     * @apiParam (请求参数说明) {string} update_foods|count 修改菜品数量
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

    /**
     * @api {GET} /api/v1/order/personalChoice/info  微信端-个人选菜-获取订单信息
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription  微信端-个人选菜-获取订单信息
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/order/personalChoice/info?id=8
     * @apiParam (请求参数说明) {int} id  订单id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"id":8,"dinner_id":6,"canteen_id":6,"ordering_date":"2019-09-07","count":1,"type":1,"money":"10.0","foods":[{"detail_id":5,"o_id":8,"food_id":1,"menu_id":0,"count":1},{"detail_id":6,"o_id":8,"food_id":3,"menu_id":0,"count":1}]}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiParam (请求参数说明) {string} id  订单id
     * @apiParam (请求参数说明) {string} ordering_date  订餐日期
     * @apiParam (请求参数说明) {int} dinner_id 餐次id
     * @apiParam (请求参数说明) {int} canteen_id 饭堂id
     * @apiParam (请求参数说明) {int} type 就餐类别：1|食堂；2|外卖
     * @apiParam (请求参数说明) {int} count 订餐数量
     * @apiParam (请求参数说明) {obj} foods 订餐菜品明细
     * @apiParam (请求参数说明) {int} foods|detail_id 订单菜品明细id
     * @apiParam (请求参数说明) {int} foods|menu_id 菜品类别id
     * @apiParam (请求参数说明) {int} foods|food_id 菜品id
     * @apiParam (请求参数说明) {string} foods|count 菜品数量
     */
    public function personalChoiceInfo()
    {
        $id = Request::param('id');
        $info = (new OrderService())->personalChoiceInfo($id);
        return json(new SuccessMessageWithData(['data' => $info]));

    }

    /**
     * @api {GET} /api/v1/order/userOrderings 微信端-订单查询-订单列表
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription 微信端-订单查询-订单列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/order/userOrderings?$page=1&size=100&type=3&id=1
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {int} type  类型：1|就餐；2|外卖；3|小卖部
     * @apiParam (请求参数说明) {int} id 类型为：就餐和外卖时该字段为饭堂id，类型为小卖部时，该字段为企业id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":1,"per_page":10,"current_page":1,"last_page":1,"data":[{"id":8,"address":"饭堂1","type":"食堂","create_time":"2019-09-09 16:34:15","dinner":"中餐","money":"10.0"}]}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 订单id
     * @apiSuccess (返回参数说明) {string} address  地点
     * @apiSuccess (返回参数说明) {float} type  类型
     * @apiSuccess (返回参数说明) {string} create_time 日期
     * @apiSuccess (返回参数说明) {int} dinner 名称
     * @apiSuccess (返回参数说明) {int} money 金额
     */
    public function userOrderings($page = 1, $size = 10)
    {
        $type = Request::param('type');
        $id = Request::param('id');
        $orders = (new OrderService())->userOrders($type, $id, $page, $size);
        return json(new SuccessMessageWithData(['data' => $orders]));
    }

    /**
     * @api {GET} /api/v1/order/detail 微信端-订单查询-获取订单详情
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription 微信端-订单查询-获取订单详情
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/order/detail?id=8&type=1
     * @apiParam (请求参数说明) {int} type  类型：1|就餐；2|外卖；3|小卖部
     * @apiParam (请求参数说明) {int} id  订单id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"id":8,"u_id":3,"order_type":1,"ordering_type":"personal_choice|","count":1,"address_id":1,"state":1,"foods":[{"detail_id":5,"o_id":8,"food_id":1,"count":1,"name":"菜品1"},{"detail_id":6,"o_id":8,"food_id":3,"count":1,"name":"菜品2"}],"address":{"id":1,"province":"广东省","city":"江门市","area":"蓬江区","address":"江门市白石大道东4号路3栋","name":"张三","phone":"18956225230","sex":1}}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id 订单id
     * @apiSuccess (返回参数说明) {float} order_type  订单类型，饭堂订单：1|食堂，2|外卖；小卖部订单：1|到店取；2|送货上门
     * @apiSuccess (返回参数说明) {int} count  订餐数量
     * @apiSuccess (返回参数说明) {int} ordering_type  订单类别：shop|小卖部；personal_choice|个人选菜；online|在线订餐
     * @apiSuccess (返回参数说明) {obj} address 地址信息：order_type=2时此数据不为空
     * @apiSuccess (返回参数说明) {string} address|province  省
     * @apiSuccess (返回参数说明) {string} address|city  城市
     * @apiSuccess (返回参数说明) {string} address|area  区
     * @apiSuccess (返回参数说明) {string} address|address  详细地址
     * @apiSuccess (返回参数说明) {string} address|name  姓名
     * @apiSuccess (返回参数说明) {string} address|phone  手机号
     * @apiSuccess (返回参数说明) {int} address|sex  性别：1|男；2|女
     * @apiSuccess (返回参数说明) {obj} foods ：order_type=2时此数据不为空
     * @apiSuccess (返回参数说明) {int} foods|food_id 菜品id
     * @apiSuccess (返回参数说明) {string} foods|price 菜品实时单价
     * @apiSuccess (返回参数说明) {string} foods|count 菜品数量
     * @apiSuccess (返回参数说明) {string} foods|name 菜品名称
     * @apiSuccess (返回参数说明) {string} foods|unit 小卖部商品单位
     */
    public function orderDetail()
    {
        $type = Request::param('type');
        $id = Request::param('id');
        $order = (new OrderService())->orderDetail($type, $id);
        return json(new SuccessMessageWithData(['data' => $order]));
    }


}