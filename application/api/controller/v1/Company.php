<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\CompanyService;
use app\lib\exception\SuccessMessageWithData;
use think\facade\Request;

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

    /**
     * @api {GET} /api/v1/companies CMS管理端-企业明细-企业列表
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-企业明细-企业列表
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/companies?name="大企业"&create_time="2019-06-29"&page=1&size=10
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {String} name 名称
     * @apiParam (请求参数说明) {String} create_time 查询时间
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":1,"per_page":10,"current_page":1,"last_page":1,"data":[{"id":2,"create_time":"2019-07-29 01:28:17","name":"一级企业","grade":1,"parent_id":0,"parent_name":null,"canteen":[{"id":1,"c_id":2,"name":"饭堂"},{"id":4,"c_id":2,"name":"饭堂1"},{"id":5,"c_id":2,"name":"饭堂2"}],"shop":{"id":1,"c_id":2,"name":""}}]}}
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 企业id
     * @apiSuccess (返回参数说明) {String} create_time 创建时间
     * @apiSuccess (返回参数说明) {String} name  企业名称
     * @apiSuccess (返回参数说明) {int} grade 企业级别：1|一级，2|二级，等
     * @apiSuccess (返回参数说明) {int} parent_id 归属企业id
     * @apiSuccess (返回参数说明) {string} parent_name 归属企业名称
     * @apiSuccess (返回参数说明) {obj} canteen 企业饭堂信息
     * @apiSuccess (返回参数说明) {int} canteen|id 饭堂id
     * @apiSuccess (返回参数说明) {int} canteen|name 饭堂名称
     * @apiSuccess (返回参数说明) {obj} shop 企业小卖部信息
     * @apiSuccess (返回参数说明) {int} shop|id 小卖部id
     * @apiSuccess (返回参数说明) {int} shop|name 小卖部名称
     */
    public function companies($page = 1, $size = 10, $name = '', $create_time = '')
    {
        $companies = (new CompanyService())->companies($page, $size, $name, $create_time);
        return json(new SuccessMessageWithData(['data' => $companies]));
    }

    /**
     * @api {GET} /api/v1/manager/companies CMS管理端-企业管理-企业列表
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-企业管理-企业列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/companies?name="企业A"
     * @apiParam (请求参数说明) {String} name 名称
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":3,"name":"企业A","parent_id":2,"canteen":[{"id":6,"c_id":3,"name":"饭堂1"},{"id":7,"c_id":3,"name":"饭堂2"}],"shop":null,"items":[{"id":4,"name":"企业A1","parent_id":3,"canteen":[],"shop":null,"items":[{"id":6,"name":"企业A11","parent_id":4,"canteen":[],"shop":null}]},{"id":5,"name":"企业A2","parent_id":3,"canteen":[],"shop":null}]}]}
     * @apiSuccess (返回参数说明) {int} id 企业id
     * @apiSuccess (返回参数说明) {String} name  企业名称
     * @apiSuccess (返回参数说明) {int} grade 企业级别：1|一级，2|二级，等
     * @apiSuccess (返回参数说明) {obj} canteen 企业饭堂信息
     * @apiSuccess (返回参数说明) {int} canteen|id 饭堂id
     * @apiSuccess (返回参数说明) {int} canteen|name 饭堂名称
     * @apiSuccess (返回参数说明) {obj} shop 企业小卖部信息
     * @apiSuccess (返回参数说明) {int} shop|id 小卖部id
     * @apiSuccess (返回参数说明) {int} shop|name 小卖部名称
     * @apiSuccess (返回参数说明) {obj} items 企业下级字企业
     */
    public function managerCompanies()
    {
        $name = Request::param('name');
        $company = (new CompanyService())->managerCompanies($name);
        return json(new SuccessMessageWithData(['data' => $company]));
    }


}