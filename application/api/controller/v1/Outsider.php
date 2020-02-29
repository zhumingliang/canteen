<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\OutsiderService;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use think\facade\Request;

class Outsider extends BaseController
{

    /**
     * @api {POST} /api/v1/outsider/save CMS管理端-外来人员设置-饭堂/权限编辑
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-外来人员设置-饭堂/权限编辑
     * @apiExample {post}  请求样例:
     *    {
     *       "id":2,
     *       "company_id": 1,
     *       "rules": "1,2,3,4"
     *     }
     * @apiParam (请求参数说明) {int} id  配置id（更新操作传入，新增操作无需传入）
     * @apiParam (请求参数说明) {string} company_id 配置所属企业id（更新操作传入，新增操作无需传入）
     * @apiParam (请求参数说明) {string} rules  可见模块，用逗号分隔。注意，一级模块也需要上传
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     */
    public function saveCanteen()
    {
        $params = $this->request->param();
        (new OutsiderService())->updateOutsider($params);
        return json(new SuccessMessage());
    }

    /**
     * @api {GET} /api/v1/outsiders CMS管理端-外来人员设置-企业配置列表
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-外来人员设置-企业配置列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/outsiders?&page=1&size=10&company_id=0
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {int} company_id 企业id：0 表示获取全部
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":2,"per_page":"10","current_page":1,"last_page":1,"data":[{"company_id":78,"company":"宜通世纪","canteen":"12楼饭堂，11楼饭堂"},{"company_id":82,"company":"666","canteen":"测试地址"}]}}     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {string} company_id 归属企业id
     * @apiSuccess (返回参数说明) {string} company 归属企业
     * @apiSuccess (返回参数说明) {obj} canteen 饭堂信息
     */
    public function outsiders($page = 1, $size = 10, $company_id = 0)
    {
        $roles = (new OutsiderService())->outsiders($page, $size, $company_id);
        return json(new SuccessMessageWithData(['data' => $roles]));

    }

    /**
     * @api {GET} /api/v1/outsider CMS管理端-外来人员设置-企业配信息
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-外来人员设置-企业配信息
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/outsider?company_id=10
     * @apiParam (请求参数说明) {int} company_id 企业id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"id":10,"company_id":10,"modules":[{"c_m_id":133,"company_id":10,"id":10,"m_id":10,"category":1,"type":1,"name":"订单管理","url":"Order","icon":"","parent_id":0,"create_time":"2019-10-31 13:48:28","order":6,"have":2,"items":[{"c_m_id":128,"company_id":10,"id":11,"m_id":11,"category":1,"type":1,"name":"订餐明细","url":"OrderStatistics","icon":"","parent_id":10,"create_time":"2019-10-31 13:57:03","order":1,"have":2},{"c_m_id":129,"company_id":10,"id":22,"m_id":22,"category":1,"type":1,"name":"订餐统计","url":"statistics","icon":"","parent_id":10,"create_time":"2019-11-01 13:53:01","order":2,"have":2}]},{"c_m_id":134,"company_id":10,"id":14,"m_id":14,"category":1,"type":1,"name":"充值管理","url":"chargeManage","icon":"","parent_id":0,"create_time":"2019-10-31 14:01:34","order":7,"have":2,"items":[{"c_m_id":130,"company_id":10,"id":21,"m_id":21,"category":1,"type":1,"name":"饭卡余额查询","url":"reamain","icon":"","parent_id":14,"create_time":"2019-11-01 13:52:37","order":3,"have":2},{"c_m_id":131,"company_id":10,"id":16,"m_id":16,"category":1,"type":1,"name":"充值记录明细","url":"chargingStatistics","icon":"","parent_id":14,"create_time":"2019-10-31 14:02:32","order":4,"have":2},{"c_m_id":132,"company_id":10,"id":15,"m_id":15,"category":1,"type":1,"name":"现金充值","url":"cashCharge","icon":"","parent_id":14,"create_time":"2019-10-31 14:01:54","order":5,"have":2}]}]}}
     * @apiSuccess (返回参数说明) {int} id 角色id
     * @apiSuccess (返回参数说明) {obj} modules 模块信息
     * @apiSuccess (返回参数说明) {int} id 模块id
     * @apiSuccess (返回参数说明) {int} c_m_id 模块与企业关联id
     * @apiSuccess (返回参数说明) {string} url 模块路由
     * @apiSuccess (返回参数说明) {string} name 模块名称
     * @apiSuccess (返回参数说明) {int} type  模块类别：1|pc;2|手机端
     * @apiSuccess (返回参数说明) {int} have  该用户是否拥有该模块：1|是;2|否
     * @apiSuccess (返回参数说明) {string} create_time 创建时间
     * @apiSuccess (返回参数说明) {string}icon  模块图标
     * @apiSuccess (返回参数说明) {string} parent_id 上级id；0表示顶级
     * @apiSuccess (返回参数说明) {obj} items 当前模块子级
     */
    public function outsider()
    {
        $id = Request::param('id');
        $role = (new OutsiderService())->outsider($id);
        return json(new SuccessMessageWithData(['data' => $role]));
    }


}