<?php


namespace app\api\controller\v1;


use app\api\service\PrinterService;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use think\Controller;
use think\facade\Request;

class Printer extends Controller
{
    /**
     * @api {POST} /api/v1/printer/save CMS管理端-企业管理-添加打印机
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription     CMS管理端-企业管理-添加打印机
     * @apiExample {post}  请求样例:
     *    {
     *       "company_id": 1,
     *       "canteen_id": 1,
     *       "name": "1号设备",
     *       "number": "001",
     *       "code": "dadas12121"
     *       "out": 2
     *     }
     * @apiParam (请求参数说明) {string} name  打印机名称
     * @apiParam (请求参数说明) {int} company_id  企业id
     * @apiParam (请求参数说明) {int} canteen_id  饭堂id
     * @apiParam (请求参数说明) {string} number  编号
     * @apiParam (请求参数说明) {string} code  设备号
     * @apiParam (请求参数说明) {int} out  设备使用类别：1：外部食堂；2 ：内部食堂;3：无;4:外卖
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function save()
    {
        $params = Request::param();
        (new PrinterService())->save($params);
        return json(new SuccessMessage());
    }

    /**
     * @api {POST} /api/v1/printer/delete  CMS管理端-企业明细-删除打印机
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-企业明细-删除打印机
     * @apiExample {post}  请求样例:
     *    {
     *       "id": "1"
     *     }
     * @apiParam (请求参数说明) {int} id  设备id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function delete()
    {
        $id = Request::param('id');
        (new PrinterService())->delete($id);
        return json(new SuccessMessage());
    }

    /**
     * @api {POST} /api/v1/printer/update CMS管理端-企业管理-修改打印机信息
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-企业管理-修改打印机信息
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 1,
     *       "name": "1号设备",
     *       "number": "001",
     *       "code": "dadas12121"
     *       "out": 2
     *     }
     * @apiParam (请求参数说明) {int} id  设备
     * @apiParam (请求参数说明) {string} name  打印机名称
     * @apiParam (请求参数说明) {string} number  编号
     * @apiParam (请求参数说明) {string} code  设备号
     * @apiParam (请求参数说明) {int} out  设备使用类别：1：外部食堂；2 ：内部食堂;3 无；4 外卖
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function update()
    {
        $params = Request::param();
        (new PrinterService())->update($params);
        return json(new SuccessMessage());
    }

    /**
     * @api {GET} /api/v1/printers CMS管理端-企业管理-获取打印机列表
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-企业管理-获取打印机列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/printers?canteen_id=1&page=1&size=20
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {int} canteen_id 饭堂id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":1,"per_page":"20","current_page":1,"last_page":1,"data":[{"id":2,"name":"打印机一号","code":"a111111","number":"001","out":1}]}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 设备id
     * @apiSuccess (返回参数说明) {string} number 设备序号
     * @apiSuccess (返回参数说明) {string} code 设备硬件号
     * @apiSuccess (返回参数说明) {string} name 设备硬件名称
     * @apiSuccess (返回参数说明) {int} out  设备使用类别：1：外部食堂；2 ：内部食堂;3 无；4：外卖
     */
    public function printers($page = 1, $size = 20)
    {
        $canteenId = Request::param('canteen_id');
        $printers = (new PrinterService())->prinaters($page, $size, $canteenId);
        return json(new SuccessMessageWithData(['data' => $printers]));

    }

}