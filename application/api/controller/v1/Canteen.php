<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\CanteenService;
use app\lib\exception\SuccessMessage;

class Canteen extends BaseController
{
    /**
     * @api {POST} /api/v1/canteen/save CMS管理端-新增饭堂
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription     CMS管理端-新增饭堂
     * @apiExample {post}  请求样例:
     *    {
     *       "canteens": "{"饭堂1号","饭堂2号"}",
     *       "c_id": 2
     *     }
     * @apiParam (请求参数说明) {string} canteens  饭堂名称json字符串
     * @apiParam (请求参数说明) {int} c_id  企业id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function save()
    {
        $params = $this->request->param();
        (new CanteenService())->save($params);
        return json(new SuccessMessage());
    }

}