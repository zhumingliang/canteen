<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\SupplierService;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use think\facade\Request;

class Supplier extends BaseController
{
    /**
     * @api {POST} /api/v1/supplier/save  CMS管理端-小卖部设置-新增供应商
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription     CMS管理端-小卖部设置-新增供应商
     * @apiExample {post}  请求样例:
     *    {
     *       "c_id": "1",
     *       "name": "供应商A",
     *       "pwd": "a111111",
     *     }
     * @apiParam (请求参数说明) {int} c_id  企业id
     * @apiParam (请求参数说明) {string} name  供应商名称
     * @apiParam (请求参数说明) {string} pwd  密码
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function save()
    {
        $params = Request::param();
        (new SupplierService())->save($params);
        return json(new SuccessMessage());
    }

    /**
     * @api {POST} /api/v1/supplier/update  CMS管理端-小卖部设置-更新供应商
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription     CMS管理端-小卖部设置-更新供应商
     * @apiExample {post}  请求样例:
     *    {
     *       "id": "1",
     *       "name": "供应商A",
     *       "account": "18956225230",
     *       "pwd": "a111111",
     *     }
     * @apiParam (请求参数说明) {int} id  供应商id
     * @apiParam (请求参数说明) {string} name  供应商名称
     * @apiParam (请求参数说明) {string} account  账号
     * @apiParam (请求参数说明) {string} pwd  密码
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function update()
    {
        $params = Request::param();
        (new SupplierService())->update($params);
        return json(new SuccessMessage());

    }

    /**
     * @api {POST} /api/v1/supplier/delete  CMS管理端-小卖部设置-删除供应商
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription     CMS管理端-小卖部设置-删除供应商
     * @apiExample {post}  请求样例:
     *    {
     *       "id": "1"
     *     }
     * @apiParam (请求参数说明) {int} id  供应商id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function delete()
    {
        $id = Request::param('id');
        (new SupplierService())->delete($id);
        return json(new SuccessMessage());
    }

    /**
     * @api {GET} /api/v1/suppliers CMS管理端-小卖部设置-供应商列表
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-小卖部设置-供应商列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/suppliers?c_id=1&page=1&size=10
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {int} c_id 企业id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":1,"per_page":10,"current_page":1,"last_page":1,"data":[{"id":1,"name":"供应商1","account":"123456","c_id":2,"state":1,"create_time":"2019-09-19 10:02:39","company":"一级企业"}]}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 供应商id
     * @apiSuccess (返回参数说明) {String} create_time 创建时间
     * @apiSuccess (返回参数说明) {String} name  供应商名称
     * @apiSuccess (返回参数说明) {String} account  账号
     * @apiSuccess (返回参数说明) {String} company  企业名称
     */
    public function suppliers($page = 1, $size = 10)
    {
        $c_id = Request::param('c_id');
        $suppliers = (new SupplierService())->suppliers($page, $size, $c_id);
        return json(new SuccessMessageWithData(['data' => $suppliers]));
    }

    /**
     * @api {GET} /api/v1/company/suppliers CMS管理端-小卖部管理-商品管理-获取该企业下供货商列表
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-小卖部管理-商品管理-获取该企业下供货商列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/company/suppliers
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":1,"name":"供应商1"}]}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id 供应商id
     * @apiSuccess (返回参数说明) {String} name  供应商名称
     */
    public function companySuppliers()
    {
        $suppliers = (new SupplierService())->companySuppliers();
        return json(new SuccessMessageWithData(['data' => $suppliers]));
    }
}