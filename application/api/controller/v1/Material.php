<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\model\MaterialPriceT;
use app\api\service\ExcelService;
use app\api\service\FoodService;
use app\api\service\MaterialService;
use app\api\service\OrderStatisticService;
use app\lib\enum\CommonEnum;
use app\lib\exception\DeleteException;
use app\lib\exception\ParameterException;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use app\lib\exception\UpdateException;
use think\facade\Request;

class Material extends BaseController
{
    /**
     * @api {POST} /api/v1/material/save CMS管理端-材料价格明细-新增价格明细
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-材料价格明细-新增价格明细
     * @apiExample {post}  请求样例:
     *    {
     *       "c_id": 6,
     *       "name": "牛肉",
     *       "price": 60
     *       "unit": "kg"
     *     }
     * @apiParam (请求参数说明) {string} c_id 饭堂id
     * @apiParam (请求参数说明) {string} name 材料名称
     * @apiParam (请求参数说明) {float} price  单价
     * @apiParam (请求参数说明) {string} unit  单位
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function save()
    {
        $params = Request::param();
        (new MaterialService())->save($params);
        return json(new SuccessMessage());

    }

    /**
     * @api {POST} /api/v1/material/update CMS管理端-材料价格明细-更新价格明细
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-材料价格明细-更新价格明细
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 1,
     *       "name": "牛肉",
     *       "price": 60
     *       "unit": "kg"
     *     }
     * @apiParam (请求参数说明) {string} id 价格明细ID
     * @apiParam (请求参数说明) {string} name 材料名称
     * @apiParam (请求参数说明) {float} price  单价
     * @apiParam (请求参数说明) {string} unit  单位
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function update()
    {
        $params = Request::only('id,name,price,unit');
        $material = MaterialPriceT::update($params);
        if (!$material) {
            throw  new UpdateException();
        }
        return json(new SuccessMessage());

    }

    /**
     * @api {POST} /api/v1/material/handel CMS管理端-材料价格明细-删除价格明细
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription     CMS管理端-材料价格明细-删除价格明细
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 1
     *     }
     * @apiParam (请求参数说明) {string} id 价格明细ID
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function handel()
    {
        $id = Request::param('id');
        $material = MaterialPriceT::update(['state' => CommonEnum::STATE_IS_FAIL], ['id' => $id]);
        if (!$material) {
            throw  new DeleteException();
        }
        return json(new SuccessMessage());

    }

    /**
     * @api {POST}  /api/v1/material/upload CMS管理端-材料价格明细-批量导入价格明细
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription  用file控件上传excel ，文件名称为：materials
     * @apiExample {post}  请求样例:
     *    {
     *       "c_id": 1
     *     }
     * @apiParam (请求参数说明) {string} c_id 饭堂id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} error_code 错误代码 0 表示没有错误
     * @apiSuccess (返回参数说明) {String} msg 操作结果描述
     */
    public function uploadMaterials()
    {
        $materials_excel = request()->file('materials');
        if (is_null($materials_excel)) {
            throw  new ParameterException(['msg' => '缺少excel文件']);
        }
        $canteen_id = Request::param('c_id');
        (new MaterialService())->uploadMaterials($canteen_id, $materials_excel);
        return json(new SuccessMessage());

    }

    /**
     * @api {GET} /api/v1/materials CMS管理端-菜品材料明细列表
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-菜品材料明细列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/materials?page=1&size=10&key=''&canteen_ids='1'&company_ids='1'
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {string} key  关键词查询
     * @apiParam (请求参数说明) {string} canteen_ids 饭堂ids，选择全部时传入所有id并逗号分隔，选择此选择项其他筛选字段（company_ids）无需上传无需上传
     * @apiParam (请求参数说明) {string} company_ids 公司ids，选择全部时传入所有id并逗号分隔，选择此选择项其他筛选字段（canteen_ids）无需上传无需上传
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":6,"per_page":"1","current_page":1,"last_page":6,"data":[{"id":5,"name":"生姜","price":20,"unit":"kg","state":1,"create_time":"2019-08-16 10:27:50","canteen_id":6,"company_id":3,"canteen":"饭堂1","company":"企业A"}]}}
     * @apiSuccess (返回参数说明) {int} error_code 错误代码 0 表示没有错误
     * @apiSuccess (返回参数说明) {String} msg 操作结果描述
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 明细id
     * @apiSuccess (返回参数说明) {int} canteen_id 饭堂id
     * @apiSuccess (返回参数说明) {int} company_id 企业id
     * @apiSuccess (返回参数说明) {string} canteen 饭堂名称
     * @apiSuccess (返回参数说明) {string} company 企业名称
     * @apiSuccess (返回参数说明) {string} name 材料名称
     * @apiSuccess (返回参数说明) {float} price  单价
     * @apiSuccess (返回参数说明) {string} unit  单位
     * @apiSuccess (返回参数说明) {string} create_time 创建时间
     */
    public function materials($page = 1, $size = 10, $key = '')
    {
        $params = Request::param();
        $materials = (new MaterialService())->materials($page, $size, $key, $params);
        return json(new SuccessMessageWithData(['data' => $materials]));

    }

    /**
     * @api {GET} /api/v1/material/export CMS管理端-导出材料价格明细（返回excel地址）
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription  CMS管理端-导出材料价格明细（返回excel地址）
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/material/export?key=''&canteen_ids='1'&company_ids='1'
     * @apiParam (请求参数说明) {string} key  关键词查询
     * @apiParam (请求参数说明) {string} canteen_ids 饭堂ids，选择全部时传入所有id并逗号分隔，选择此选择项其他筛选字段（company_ids）无需上传无需上传
     * @apiParam (请求参数说明) {string} company_ids 公司ids，选择全部时传入所有id并逗号分隔，选择此选择项其他筛选字段（canteen_ids）无需上传无需上传
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"url":"http:\/\/canteen.tonglingok.com\/static\/excel\/download\/材料价格明细_20190817005931.xls"}}     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} error_code 错误代码 0 表示没有错误
     * @apiSuccess (返回参数说明) {string} msg 操作结果描述
     * @apiSuccess (返回参数说明) {string} url 下载地址
     */
    public function export($key = '')
    {
        $params = Request::param();
        $url = (new MaterialService())->exportMaterials($key, $params);
        return json(new SuccessMessageWithData(["data" => ['url' => $url]]));
    }

    /**
     * @api {GET} /api/v1/materials/food CMS管理端-材料管理-菜品材料明细
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-材料管理-菜品材料明细
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/materials/food?&page=1&size=10&dinner_ids='1'&canteen_ids='1'&company_ids='1'
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {String} dinner_ids 餐次ids，选择全部时传入所有id并逗号分隔，选择此选择项其他筛选字段（canteen_ids/company_ids）无需上传无需上传
     * @apiParam (请求参数说明) {String} canteen_ids 饭堂ids，选择全部时传入所有id并逗号分隔，选择此选择项其他筛选字段（dinner_ids/company_ids）无需上传无需上传
     * @apiParam (请求参数说明) {String} company_ids 公司ids，选择全部时传入所有id并逗号分隔，选择此选择项其他筛选字段（dinner_ids/canteen_ids）无需上传无需上传
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":2,"per_page":"10","current_page":1,"last_page":1,"data":[{"id":3,"company":"企业A","canteen":"饭堂1","dinner":"中餐","name":"西红柿牛肉","material":[{"id":1,"f_id":3,"name":"牛肉","count":15,"unit":""},{"id":3,"f_id":3,"name":"西红柿","count":10,"unit":""}]},{"id":1,"company":"企业A","canteen":"饭堂1","dinner":"中餐","name":"红烧牛肉","material":[]}]}}
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 菜品id
     * @apiSuccess (返回参数说明) {string} canteen 饭堂名称
     * @apiSuccess (返回参数说明) {string} company 企业名称
     * @apiSuccess (返回参数说明) {string} dinner  餐次
     * @apiSuccess (返回参数说明) {string} name  菜品名称
     * @apiSuccess (返回参数说明) {obj} material  菜品材料明细
     * @apiSuccess (返回参数说明) {int} material|id  明细ID
     * @apiSuccess (返回参数说明) {string} material|name  名称
     * @apiSuccess (返回参数说明) {string} material|count  数量
     * @apiSuccess (返回参数说明) {string} material|unit  单位
     */
    public function foodMaterials($page = 1, $size = 10)
    {
        $params = Request::param();
        $foodMaterials = (new FoodService())->foodMaterials($page, $size, $params);
        return json(new SuccessMessageWithData(['data' => $foodMaterials]));
    }

    /**
     * @api {GET} /api/v1/material/exportFoodMaterials CMS管理端-材料管理-菜品材料明细-导出
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription  CMS管理端-材料管理-菜品材料明细-导出
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/material/exportFoodMaterials?dinner_ids=6&canteen_ids=6&company_ids=3
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {String} dinner_ids 餐次ids，选择全部时传入所有id并逗号分隔，选择此选择项其他筛选字段（canteen_ids/company_ids）无需上传无需上传
     * @apiParam (请求参数说明) {String} canteen_ids 饭堂ids，选择全部时传入所有id并逗号分隔，选择此选择项其他筛选字段（dinner_ids/company_ids）无需上传无需上传
     * @apiParam (请求参数说明) {String} company_ids 公司ids，选择全部时传入所有id并逗号分隔，选择此选择项其他筛选字段（dinner_ids/canteen_ids）无需上传无需上传
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"url":"http:\/\/canteen.tonglingok.com\/static\/excel\/download\/材料价格明细_20190817005931.xls"}}     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} error_code 错误代码 0 表示没有错误
     * @apiSuccess (返回参数说明) {string} msg 操作结果描述
     * @apiSuccess (返回参数说明) {string} url 下载地址
     */
    public function exportFoodMaterials()
    {
        $params = Request::param();
        $url = (new FoodService())->exportFoodMaterials($params);
        return json(new SuccessMessageWithData(['data' => $url]));
    }

    public function exportMaterialReports()
    {
        $time_begin = Request::param('time_begin');
        $time_end = Request::param('time_end');
        $canteen_id = Request::param('canteen_id');
        $url = (new OrderStatisticService())
            ->exportMaterialReports($time_begin, $time_end, $canteen_id);
        return json(new SuccessMessageWithData(['data' => $url]));
    }
}