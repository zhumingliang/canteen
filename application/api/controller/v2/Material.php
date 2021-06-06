<?php


namespace app\api\controller\v2;


use app\api\controller\BaseController;
use app\api\service\MachineService;
use app\api\service\MaterialService;
use app\api\service\v2\DownExcelService;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use think\facade\Request;

class Material extends BaseController
{

    /**
     * @api {POST} /api/v2/food/material/save CMS管理端-材料管理-菜品材料明细-新增菜品材料
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-材料管理-菜品材料明细-新增菜品材料
     * @apiExample {post}  请求样例:
     *    {
     *       "f_id": 1,
     *       "name": 1,
     *       "count": 6
     * }
     * @apiParam (请求参数说明) {int} f_id  菜品ID
     * @apiParam (请求参数说明) {string} name 菜品材料名称
     * @apiParam (请求参数说明) {float} count 菜品材料数量:单位kg
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function saveFoodMaterial()
    {
        $params = Request::param();
        (new MaterialService())->saveFoodMaterial($params);
        return json(new SuccessMessage());
    }

    /**
     * @api {POST} /api/v2/food/material/update CMS管理端-材料管理-菜品材料明细-修改菜品材料
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-材料管理-菜品材料明细-修改菜品材料
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 1,
     *       "name": 1,
     *       "count": 6,
     *       "state": 2
     * }
     * @apiParam (请求参数说明) {int} id  菜品材料ID
     * @apiParam (请求参数说明) {string} name 菜品材料名称
     * @apiParam (请求参数说明) {float} count 菜品材料数量:单位kg
     * @apiParam (请求参数说明) {int} state  状态：删除时：传入 2
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function updateFoodMaterial()
    {
        $params = Request::param();
        (new MaterialService())->updateFoodMaterial($params);
        return json(new SuccessMessage());
    }

    /**
     * @api {POST} /api/v2/material/order/save CMS管理端-材料下单报表-新增材料信息
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-材料下单报表-新增材料信息
     * @apiExample {post}  请求样例:
     *    {
     *       "company_id": 6,
     *       "canteen_id": 6,
     *       "material": "牛肉",
     *       "price": 16.01
     *       "count": 20.001
     *     }
     * @apiParam (请求参数说明) {int} company_id 企业id
     * @apiParam (请求参数说明) {int} canteen_id 饭堂id
     * @apiParam (请求参数说明) {string} material 材料名称
     * @apiParam (请求参数说明) {float} price  单价，单位：元
     * @apiParam (请求参数说明) {string} count  数量，单位：kg
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function saveOrderMaterial()
    {
        $params = Request::param();
        (new MaterialService())->saveOrderMaterial($params);
        return json(new SuccessMessage());
    }

    /**
     * @api {POST} /api/v2/material/order/update CMS管理端-材料下单报表-修改材料信息
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-材料下单报表-修改材料信息
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 6,
     *       "price": 16.01
     *       "count": 20.001
     *     }
     * @apiParam (请求参数说明) {int} id 材料信息id
     * @apiParam (请求参数说明) {float} price  单价，单位：元
     * @apiParam (请求参数说明) {string} count  数量，单位：kg
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function updateOrderMaterial()
    {
        $params = Request::only('id,price,count');
        (new MaterialService())->updateOrderMaterial($params);
        return json(new SuccessMessage());
    }

    /**
     * @api {POST} /api/v2/material/order/delete CMS管理端-材料下单报表-删除材料信息
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-材料下单报表-删除材料信息
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 6
     *     }
     * @apiParam (请求参数说明) {int} id 材料信息id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function deleteOrderMaterial()
    {
        $id = Request::param('id');
        (new MaterialService())->deleteOrderMaterial($id);
        return json(new SuccessMessage());
    }

    /**
     * @api {GET} /api/v2/material/order/list CMS管理端-材料下单报表-材料信息列表
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-材料下单报表-材料信息列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v2/material/order/list?company_id=134&canteen_id=342&time_begin=2021-06-04&time_end=2021-06-04&page=1&size=1
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {int} company_id  企业id
     * @apiParam (请求参数说明) {int} canteen_id  饭堂ID，全部为0
     * @apiParam (请求参数说明) {string} time_begin  查询开始时间
     * @apiParam (请求参数说明) {string} time_end  查询结束时间
     * @apiParam (请求参数说明) {int} page  查询页码
     * @apiParam (请求参数说明) {int} size  每页数据条数
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":2,"per_page":"1","current_page":1,"last_page":2,"data":[{"id":1,"create_time":"2021-06-04 11:55","dinner":"早餐","canteen":"饭堂X","material":"牛肉","order_count":"0.000","count":"1.000","price":"40.00","report":2}]}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id  信息id
     * @apiSuccess (返回参数说明) {string} create_time 日期
     * @apiSuccess (返回参数说明) {string} dinner 餐次
     * @apiSuccess (返回参数说明) {string} canteen 地点
     * @apiSuccess (返回参数说明) {string} material 材料名称
     * @apiSuccess (返回参数说明) {int} order_count  材料数量
     * @apiSuccess (返回参数说明) {int} count 订货数量
     * @apiSuccess (返回参数说明) {int} price  单价
     * @apiSuccess (返回参数说明) {int} report 是否生成报表 1 是；2 否，已经生成报表不能再生成
     */
    public function orderMaterials($page = 1, $size = 10)
    {
        $timeBegin = Request::param('time_begin');
        $timeEnd = Request::param('time_end');
        $companyId = Request::param('company_id');
        $canteenId = Request::param('canteen_id');
        $data = (new MaterialService())->orderMaterials($timeBegin, $timeEnd, $companyId, $canteenId, $page, $size);
        return json(new SuccessMessageWithData(['data' => $data]));
    }

    /**
     * @api {POST} /api/v2/material/order/report CMS管理端-材料下单报表-提交报表
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-材料下单报表-提交报表
     * @apiExample {post}  请求样例:
     *    {
     *       "title": 6,
     *       "ids": 6,7,8
     *     }
     * @apiParam (请求参数说明) {string} title 报表名称
     * @apiParam (请求参数说明) {int} ids 材料id，多个用逗号分隔
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function orderMaterialReport()
    {
        $ids = Request::param('ids');
        $title = Request::param('title');
        (new MaterialService())->orderMaterialReport($title, $ids);
        return json(new SuccessMessage());
    }

    /**
     * @api {POST} /api/v2/material/order/report/cancel CMS管理端-材料下单报表-作废报表
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-材料下单报表-作废报表
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 6
     *     }
     * @apiParam (请求参数说明) {int} id 报表id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function orderMaterialReportCancel()
    {
        $id = Request::param('id');
        (new MaterialService())->orderMaterialReportCancel($id);
        return json(new SuccessMessage());
    }

    /**
     * @api {GET} /api/v2/material/order/reports CMS管理端-入库材料管理-报表列表
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-材料下单报表-材料信息列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v2/material/order/reports?company_id=134&canteen_id=342&time_begin=2021-06-04&time_end=2021-06-04&page=1&size=1
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {int} company_id  企业id
     * @apiParam (请求参数说明) {int} canteen_id  饭堂ID，全部为0
     * @apiParam (请求参数说明) {string} time_begin  查询开始时间
     * @apiParam (请求参数说明) {string} time_end  查询结束时间
     * @apiParam (请求参数说明) {int} page  查询页码
     * @apiParam (请求参数说明) {int} size  每页数据条数
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":1,"per_page":10,"current_page":1,"last_page":1,"data":[{"id":21,"canteen_id":342,"title":"报表","create_time":"2021-06-06 09:03:26","canteen":{"id":342,"name":"饭堂X"}}]}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id  列表id
     * @apiSuccess (返回参数说明) {string} create_time 日期
     * @apiSuccess (返回参数说明) {string} title 报表名称
     * @apiSuccess (返回参数说明) {obj} canteen 地点信息
     * @apiSuccess (返回参数说明) {string} name 地点名称
     */
    public function orderMaterialReports($page = 1, $size = 10)
    {
        $companyId = Request::param('company_id');
        $canteenId = Request::param('canteen_id');
        $timeBegin = Request::param('time_begin');
        $timeEnd = Request::param('time_end');
        $data = (new MaterialService())->orderMaterialReports($timeBegin, $timeEnd, $companyId, $canteenId, $page, $size);
        return json(new SuccessMessageWithData(['data' => $data]));

    }

    /**
     * @api {GET} /api/v2/material/order/report/detail CMS管理端-入库材料管理-报表详情
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-入库材料管理-报表详情
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v2/material/order/report/detail?company_id=134&canteen_id=342&time_begin=2021-06-04&time_end=2021-06-04&page=1&size=1
     * @apiParam (请求参数说明) {int} id 报表id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"title":"报表","detail":[{"id":43,"create_time":"2021-06-06 09:03","dinner":"晚餐","canteen":"饭堂X","material":"牛肉","order_count":"0.000","count":"1.000","price":"40.00"},{"id":42,"create_time":"2021-06-06 09:03","dinner":"早餐","canteen":"饭堂X","material":"牛肉","order_count":"0.000","count":"1.000","price":"40.00"}]}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {string} title 报表名称
     * @apiSuccess (返回参数说明) {obj} detail 报表详情
     * @apiSuccess (返回参数说明) {int} id  信息id
     * @apiSuccess (返回参数说明) {string} create_time 日期
     * @apiSuccess (返回参数说明) {string} dinner 餐次
     * @apiSuccess (返回参数说明) {string} canteen 地点
     * @apiSuccess (返回参数说明) {string} material 材料名称
     * @apiSuccess (返回参数说明) {int} order_count  材料数量
     * @apiSuccess (返回参数说明) {int} count 订货数量
     * @apiSuccess (返回参数说明) {int} price  单价
     */
    public function orderMaterialReportDetail()
    {
        $id = Request::param('id');
        $info = (new MaterialService())->orderMaterialReportDetail($id);
        return json(new SuccessMessageWithData(['data' => $info]));
    }

    public function orderMaterialReportExport()
    {
        $id = Request::param('id');
        (new DownExcelService())->exportOrderMaterialReport($id);
        return json(new SuccessMessage());
    }
}