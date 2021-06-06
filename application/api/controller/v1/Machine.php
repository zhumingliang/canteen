<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\MachineService;
use app\lib\exception\SuccessMessage;
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


    /**
     * @api {POST} /api/v1/machine/machineConfig CMS管理端-人脸消费机-消费机配置
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-人脸消费机-消费机配置
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 1,
     *       "machine_id": 458,
     *       "deduction_success_type": 1,
     *       "deduction_success_msg": "扣费成功",
     *       "deduction_fail_type": 1,
     *       "deduction_fail_msg": "扣费失败",
     *       "face_fail_type": 1,
     *       "face_fail_msg": "识别失败",
     *       "face_fail_content": "识别失败，请上传人脸信息",
     *       "deduction_success_sub": "1,2",
     *       "deduction_ail_sub": "1"
     *     }
     * @apiParam (请求参数说明) {int} id  消费机/人脸识别消费机 配置信息表id
     * @apiParam (请求参数说明) {int} machine_id  消费机id
     * @apiParam (请求参数说明) {int} deduction_success_type  扣费成功语音播报设置类型：1:扣费成功；2:消费费成功；3:自定义；4:无
     * @apiParam (请求参数说明) {string} deduction_success_msg  扣费成功语音播报设置内容
     * @apiParam (请求参数说明) {int} deduction_fail_type  扣费失败语音播报设置类型：1:扣费失败；2:消费失败；3:自定义；4:无
     * @apiParam (请求参数说明) {string} deduction_fail_msg   扣费失败语音播报设置内容
     * @apiParam (请求参数说明) {int} face_fail_type  人脸失败语音播报设置类型：1:识别失败；2:无法识别；3:自定义；4:无
     * @apiParam (请求参数说明) {string} face_fail_msg   人脸失败语音播报设置内容
     * @apiParam (请求参数说明) {string} face_fail_content  人脸识别失败文字描述
     * @apiParam (请求参数说明) {string} deduction_success_sub  扣费成功语音播报附加信息：1:姓名；2:份数；0:无;多个用逗号分隔
     * @apiParam (请求参数说明) {string} deduction_ail_sub  扣费失败语音播报附加信息：1:原因；0:无；多个用逗号分隔
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function machineConfig()
    {
        $params = Request::param();
        (new MachineService())->updateMachineConfig($params);
        return json(new SuccessMessage());
    }
    /**
     * @api {POST} /api/v1/printer/update CMS管理端-人脸消费机-人脸消费机人脸识别参数配置
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-人脸消费机-人脸消费机人脸识别参数配置
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 1,
     *       "machine_id": "458",
     *       "distance": 2,
     *       "living": 2,
     *       "accuracy": 50,
     *       "times": 0.01
     *     }
     * @apiParam (请求参数说明) {int} id  识别参数配置表id
     * @apiParam (请求参数说明) {int} machine_id  人脸识别消费机id
     * @apiParam (请求参数说明) {int} distance  识别距离：单位米
     * @apiParam (请求参数说明) {int} living  是否开启活体检测：1 是；2 ： 否
     * @apiParam (请求参数说明) {int} accuracy  准确率
     * @apiParam (请求参数说明) {int} times  识别时间
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function faceConfig()
    {
        $params = Request::param();
        (new MachineService())->updateFaceConfig($params);
        return json(new SuccessMessage());
    }
    /**
     * @api {GET} /api/v1/machine/machineConfig CMS管理端-人脸消费机-消费机配置-获取消费机配置列表
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription  人脸消费机PC端-消费机配置-获取消费机配置列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/machine/machineConfig
     * @apiParam (请求参数说明) {int} company_id 企业id
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {int} belong_id 归属id（饭堂id/小卖部id）
     * @apiParam (请求参数说明) {int} type 消费机类型：1 消费机；2：人脸识别机
     * @apiParam (请求参数说明) {int} name 消费机名称
     * @apiParam (请求参数说明) {int} code 消费机标识码
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":1,"per_page":20,"current_page":1,"last_page":1,"data":[{"id":458,"company_id":100,"belong_id":186,"type":2,"name":"测试的","code":"000001","config":[{"id":1,"machine_id":458,"deduction_success_type":1,"deduction_success_msg":"扣费成功","deduction_fail_type":1,"deduction_fail_msg":"扣费失败","face_fail_type":1,"face_fail_msg":"识别失败","face_fail_content":"识别失败，请上传人脸信息","deduction_success_sub":"1,2","deduction_ail_sub":"1"}],"face":[{"id":1,"machine_id":458,"distance":"2.00","living":2,"accuracy":60,"times":0}]}]}}
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 消费机id
     * @apiSuccess (返回参数说明) {int} company_id 企业id
     * @apiParam (请求参数说明) {int} belong_id 归属id（饭堂id/小卖部id）
     * @apiParam (请求参数说明) {int} type 消费机类型：1 消费机；2：人脸识别机
     * @apiParam (请求参数说明) {int} name 消费机名称
     * @apiParam (请求参数说明) {int} code 消费机标识码
     * @apiParam (请求参数说明) {int} config|id  消费机/人脸识别消费机 配置信息表id
     * @apiParam (请求参数说明) {int} machine_id  消费机id
     * @apiParam (请求参数说明) {int} deduction_success_type  扣费成功语音播报设置类型：1:扣费成功；2:消费费成功；3:自定义；4:无
     * @apiParam (请求参数说明) {string} deduction_success_msg  扣费成功语音播报设置内容
     * @apiParam (请求参数说明) {int} deduction_fail_type  扣费失败语音播报设置类型：1:扣费失败；2:消费失败；3:自定义；4:无
     * @apiParam (请求参数说明) {string} deduction_fail_msg   扣费失败语音播报设置内容
     * @apiParam (请求参数说明) {int} face_fail_type  人脸失败语音播报设置类型：1:识别失败；2:无法识别；3:自定义；4:无
     * @apiParam (请求参数说明) {string} face_fail_msg   人脸失败语音播报设置内容
     * @apiParam (请求参数说明) {string} face_fail_content  人脸识别失败文字描述
     * @apiParam (请求参数说明) {string} deduction_success_sub  扣费成功语音播报附加信息：1:姓名；2:份数；0:无;多个用逗号分隔
     * @apiParam (请求参数说明) {string} deduction_ail_sub  扣费失败语音播报附加信息：1:原因；0:无；多个用逗号分隔
     * @apiParam (请求参数说明) {int} face|id  识别参数配置表id
     * @apiParam (请求参数说明) {int} machine_id  人脸识别消费机id
     * @apiParam (请求参数说明) {int} distance  识别距离：单位米
     * @apiParam (请求参数说明) {int} living  是否开启活体检测：1 是；2 ： 否
     * @apiParam (请求参数说明) {int} accuracy  准确率
     * @apiParam (请求参数说明) {int} times  识别时间
     */
    public function getMachineConfig($page = 1, $size = 20,$belong_id=0,$type=0,$name = '',$code='')
    {
        $company_id=Request::param('company_id');
        $list=(new MachineService())->getMachineConfig($company_id,$page,$size,$belong_id,$type,$name,$code);
        return json(new SuccessMessageWithData(['data'=>$list]));
    }



}