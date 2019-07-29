<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\CompanyService;
use app\lib\exception\SuccessMessageWithData;

class Company extends BaseController
{
    /**
     * @api {POST} /api/v1/company/save CMS管理端-新增企业
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription     CMS管理端-新增企业
     * @apiExample {post}  请求样例:
     *    {
     *       "name": "一级企业",
     *       "parent_id": 0
     *     }
     * @apiParam (请求参数说明) {string} name  企业名称
     * @apiParam (请求参数说明) {int} parent_id  上级企业id;0表示无上级企业，本次新增为一级企业
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"company_id":"2"}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {int} company_id 企业id
     */
    public function save()
    {
        $params = $this->request->param();;
        $rd = (new CompanyService())->saveDefault($params);
        return json(new SuccessMessageWithData(['data' => $rd]));

    }


}