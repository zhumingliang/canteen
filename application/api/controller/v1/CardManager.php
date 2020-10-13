<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\CardService;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use think\facade\Request;

class CardManager extends BaseController
{

    /**
     * @api {GET} /api/v1/card/staffs CMS管理端-消费卡管理-获取用户列表
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-消费卡管理-获取用户列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/card/staffs?page=1&size=10&name=2&card_code=4&status
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {int} name 用户名称
     * @apiParam (请求参数说明) {int} card_code 卡号
     * @apiParam (请求参数说明) {int} status  状态：0 全部；1:正常；2:挂失；3:注销；4 未绑定
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":329,"per_page":"1","current_page":1,"last_page":329,"data":[{"id":350,"t_id":3,"type":"员工","d_id":4,"department":"A部门","code":"123456","username":"里斯","phone":"18956225230","card_num":"a123","create_time":"2019-08-03 00:47:59","expiry_date":"0000-00-00 00:00:00","url":"http:\/\/canteen.tonglingok.com\/static\/qrcode\/517e9af47c57e0e789e4bd113d5b0c9b54a615ca.png","q_id":329,"canteens":[{"id":1,"staff_id":350,"canteen_id":1,"info":{"id":1,"name":"大饭堂"}}]}]}}
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 员工id
     * @apiSuccess (返回参数说明) {string} department 所属部门
     * @apiSuccess (返回参数说明) {string} name 姓名
     * @apiSuccess (返回参数说明) {string} company 归属企业
     * @apiSuccess (返回参数说明) {string} card_num 卡号
     * @apiSuccess (返回参数说明) {string} create_time 创建时间
     * @apiSuccess (返回参数说明) {string} face_code  人脸识别id
     * @apiSuccess (返回参数说明) {int} card_id  卡号id （id=0 表示未绑定卡）
     * @apiSuccess (返回参数说明) {string} card_code  卡号
     * @apiSuccess (返回参数说明) {int} state  卡号状态：1:正常；2:挂失；3:注销
     */
    public function staffs($name = "", $card_code = "", $status = 0, $page = 1, $size = 10)
    {
        $staffs = (new CardService())->cardManager($name, $card_code, $status, $page, $size);
        return json(new SuccessMessageWithData(['data' => $staffs]));

    }

    /**
     * @api {POST} /api/v1/staff/card/bind CMS管理端-消费卡管理-绑卡操作
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-消费卡管理-绑卡操作
     * @apiExample {post}  请求样例:
     *    {
     *       "staff_id": 1
     *       "card_code": 123,
     *       "birthday": "2020-10-01"
     *     }
     * @apiParam (请求参数说明) {int}  staff_id 员工id
     * @apiParam (请求参数说明) {string}  card_code 卡号
     * @apiParam (请求参数说明) {string}  birthday 生日
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function bind()
    {
        $staffId = Request::param('staff_id');
        $cardCode = Request::param("card_code");
        $birthday = Request::param("birthday");
        (new CardService())->bind($staffId, $cardCode,$birthday);
        return json(new SuccessMessage());
    }

    /**
     * @api {POST} /api/v1/staff/card/handle CMS管理端-消费卡管理-卡状态操作
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-消费卡管理-卡状态操作
     * @apiExample {post}  请求样例:
     *    {
     *       "card_id": 123,
     *       "state": 2
     *     }
     * @apiParam (请求参数说明) {int}  card_id 卡ID
     * @apiParam (请求参数说明) {int}  state 操作状态：1：恢复；2：挂失；3：注销
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */

    public function handle()
    {
        $cardId = Request::param('card_id');
        $state = Request::param('state');
        (new CardService())->handle($cardId,$state);
        return json(new SuccessMessage());

    }


}