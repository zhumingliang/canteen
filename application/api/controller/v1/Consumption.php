<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\ConsumptionService;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use think\facade\Request;

class Consumption extends BaseController
{
    /**
     * @api {GET} /api/v1/consumption/staff 消费机-饭堂订单/小卖部订单--消费操作
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription 消费机-饭堂订单/小卖部订单--消费操作Machine
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/consumption/staff?code=123&type=shop
     * @apiParam (请求参数说明) {String} code 唯一识别码
     * @apiParam (请求参数说明) {String} type shop:小卖部提货码；canteen:饭堂消费码
     * @apiSuccessExample {json} 小卖部消费返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"money":164,"department":"股东","username":"langbin","products":[{"o_id":22,"name":"鸡蛋xxx","unit":"元\/500g","price":"8.00","count":1},{"o_id":22,"name":"捞面","unit":"份","price":"8.00","count":1},{"o_id":22,"name":"langbing2","unit":"g","price":"10.00","count":1},{"o_id":22,"name":"langbin3","unit":"kg","price":"15.00","count":1}]}}
     * @apiSuccessExample {json} 饭堂消费返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"dinner":"早餐","price":"2.0","money":4,"department":"股东","username":"langbin","balance":-648,"type":1,"remark":"订餐消费","products":[{"detail_id":16,"o_id":53,"food_id":49,"count":1,"name":"肉肉肉","price":"10.0"},{"detail_id":17,"o_id":53,"food_id":50,"count":1,"name":"鱼鱼","price":"3.0"},{"detail_id":18,"o_id":53,"food_id":51,"count":1,"name":"汤","price":"0.0"}]}}
     * @apiSuccess (返回参数说明) {string} dinner 餐次
     * @apiSuccess (返回参数说明) {float} price  定价
     * @apiSuccess (返回参数说明) {float} money 实际价格
     * @apiSuccess (返回参数说明) {string} department 所属部门
     * @apiSuccess (返回参数说明) {string} username 用户名
     * @apiSuccess (返回参数说明) {float} balance 余额
     * @apiSuccess (返回参数说明) {int} type 1:已经订餐消费；2：未订餐消费
     * @apiSuccess (返回参数说明) {string}  remark 备注
     * @apiSuccess (返回参数说明) {obj} products 商品/菜品信息
     * @apiSuccess (返回参数说明) {string}  name 名称
     * @apiSuccess (返回参数说明) {float}  price 价格
     * @apiSuccess (返回参数说明) {int}  count 数量
     * @apiSuccess (返回参数说明) {string}  unit 单位
     */
    public function staff()
    {
        $code = Request::param('code');
        $type = Request::param('type');
        $data = (new ConsumptionService())->staff($type, $code);
        return json(new SuccessMessageWithData(['data' => $data]));

    }


    /**
     * @api {POST} /api/v1/consumption/face 消费机-饭堂订单--人脸识别消费
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription     消费机-饭堂订单--人脸识别消费
     *    {
     *       "face_id": "sacews12123",
     *       "face_time": "2020-02-17 09:00",
     *       "phone": "18956225230"
     *     }
     * @apiParam (请求参数说明) {string} face_id  人脸识别机唯一id
     * @apiParam (请求参数说明) {string} face_time  识别时间
     * @apiParam (请求参数说明) {string} phone  手机号
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function consumptionWithFace()
    {
        $face_time = Request::param('face_time');
        $face_id = Request::param('face_id');
        $phone = Request::param('phone');
        (new ConsumptionService())->consumptionWithFace($face_time, $face_id, $phone);
        return json(new SuccessMessage());
    }

}