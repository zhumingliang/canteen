<?php
/**
 * Created by PhpStorm.
 * User: 明良
 * Date: 2019/9/4
 * Time: 15:52
 */

namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\OrderService;
use app\lib\exception\SuccessMessageWithData;
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
     *       "type": 1,
     *       "count": 1,
     *       "detail":[{"menu_id":1,"foods":[{"food_id":1,"price":5,"count":1},{"food_id":2,"price":5,"count":1}]}]
     *     }
     * @apiParam (请求参数说明) {string} ordering_date  订餐日期
     * @apiParam (请求参数说明) {int} dinner_id 餐次id
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

}