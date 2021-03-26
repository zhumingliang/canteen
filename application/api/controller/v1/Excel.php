<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\ExcelService;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;

class Excel extends BaseController
{
    /**
     * @api {GET} /api/v1/excels  CMS管理端-获取下载excel记录
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription  CMS管理端-获取下载excel记录
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/excels
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":1,"status":1,"name":"饭堂消费总报表","url":"http://","time_begin":"2021-03-01","time_end":"2021-03-01"}]}
     * @apiSuccess (返回参数说明) {int} id 记录ID
     * @apiSuccess (返回参数说明) {int} status 下载状态：1：下载中；2 ：下载完成；3 ：下载失败
     * @apiSuccess (返回参数说明) {string} name  表头名称
     * @apiSuccess (返回参数说明) {string} url 下载地址
     * @apiSuccess (返回参数说明) {string} time_begin 开始时间
     * @apiSuccess (返回参数说明) {string} time_end  结束时间
     */
    public function excels()
    {
        $excels = (new ExcelService())->excels();
        return json(new SuccessMessageWithData(['data' => $excels]));
    }

    /**
     * @api {POST} /api/v1/excel/delete  CMS管理端-删除下载excel记录
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription     CMS管理端-删除下载excel记录
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 1,
     *       "type": "all"
     *     }
     * @apiParam (请求参数说明) {int} id  记录id
     * @apiParam (请求参数说明) {string} type  all 清除所有；one：清除一个
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function delete($id, $type = 'one')
    {
        (new ExcelService())->deleteExcel($id,$type);
        return json(new  SuccessMessage());

    }

}