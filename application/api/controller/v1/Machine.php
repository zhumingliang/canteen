<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\MachineService;
use app\lib\exception\SuccessMessageWithData;
use think\facade\Request;
use think\response\Json;

class Machine extends BaseController
{

    /**
     * @api {GET} /api/v1/machine/records 微信端--获取消费机数据下发列表
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription 微信端--获取消费机数据下发列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/machine/records?day=2021-05-31&company_id=0&machine_id=0&status=0
     * @apiParam (请求参数说明) {string} day 日期
     * @apiParam (请求参数说明) {int} company_id 每页多少条数据
     * @apiParam (请求参数说明) {int} machine_id 饭堂id
     * @apiParam (请求参数说明) {int} status 状态：0：全部；1：成功；2：失败
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"company_id":135,"company":"芋泥啵啵奶（动态）","machine_id":457,"name":"Test12V050203","day":"2021-05-31","status":2}]}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {int} company_id 企业ID
     * @apiSuccess (返回参数说明) {int} machine_id 消费机ID
     * @apiSuccess (返回参数说明) {int} company 企业名称
     * @apiSuccess (返回参数说明) {int} name 消费机名称
     * @apiSuccess (返回参数说明) {int} day 日期
     * @apiSuccess (返回参数说明) {int} status 状态：1 ：下发成功；2：下发失败
     */
    public function records($company_id = 0, $day = "", $machine_id = 0, $status = 0)
    {
        $records = (new MachineService())->records($company_id, $day, $machine_id, $status);
        return json(new SuccessMessageWithData(['data' => $records]));
    }


    /**
     * @api {GET} /api/v1/machine/records/detail 微信端--获取消费机数据下发列表-明细
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription 微信端--获取消费机数据下发列表--明细
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/machine/detail?day=2021-05-31&machine_id=457
     * @apiParam (请求参数说明) {string} day 日期
     * @apiParam (请求参数说明) {int} machine_id 饭堂id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"create_time":"2021-05-31 16:22:08","state":1},{"create_time":"2021-05-31 16:25:23","state":0}]}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {int} create_time 下发时间
     * @apiSuccess (返回参数说明) {int} state 状态：1 ：下发失败；2：下发成功
     */
    public function detail()
    {
        $machineId = Request::param('machine_id');
        $day = Request::param('day');
        $data = (new MachineService())->detail($machineId, $day);
        return json(new SuccessMessageWithData(['data' => $data]));

    }



    /**
     * @api {GET} /api/v1/offline/machines 微信端-离线记录-获取所有设备
     * @apiGroup  Offcial
     * @apiVersion 3.0.0
     * @apiDescription 微信端-离线记录-获取所有设备
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/offline/machines?company_id=1
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {int} company_id  企业id，获取全部则传入0
     * @apiSuccessExample {json} 返回样例:
    {"msg":"ok","errorCode":0,"code":200,"data":[{"id":25,"machine_type":"canteen","name":"7寸屏分离机_2"}]}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id 设备id
     * @apiSuccess (返回参数说明) {string} machine_type 设备类别
     * @apiSuccess (返回参数说明) {string} name 设备硬件名称
     */
    public function machines($company_id=0)
    {
        //$companyId = Request::param('company_id');
        $machines = (new MachineService())->machines($company_id);
        return \json(new SuccessMessageWithData(['data' => $machines]));
    }


}