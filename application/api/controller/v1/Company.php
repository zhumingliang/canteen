<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\CompanyService;
use app\lib\exception\SuccessMessage;
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
     * http://canteen.tonglingok.com/api/v1/companies?name="大企业"&create_time="2019-06-29"&page=1&size=10
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
     * http://canteen.tonglingok.com/api/v1/manager/companies?name="企业A"
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
        $companies = (new CompanyService())->managerCompanies($name);
        return json(new SuccessMessageWithData(['data' => $companies]));
    }

    /**
     * @api {GET} /api/v1/user/companies 微信端-获取当前用户所属企业列表
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription 微信端-获取当前用户所属企业列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/user/companies
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":9,"company_id":2,"company":"一级企业"}]}
     * @apiSuccess (返回参数说明) {int} id 企业id
     * @apiSuccess (返回参数说明) {int} company_id  企业ID
     * @apiSuccess (返回参数说明) {String} company  企业名称
     */
    public function userCompanies()
    {
        $companies = (new CompanyService())->userCompanies();
        return json(new SuccessMessageWithData(['data' => $companies]));

    }

    /**
     * @api {GET} /api/v1/admin/companies CMS管理端--系统管理员/企业超级管理员/企业管理员--查看可见企业
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端--系统管理员/企业超级管理员/企业管理员--查看可见企业
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/admin/companies
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":9,"parent_id":0,"name":"一级企业"}]}
     * @apiSuccess (返回参数说明) {int} id 企业id
     * @apiSuccess (返回参数说明) {int} parent_id  企业上级企业id
     * @apiSuccess (返回参数说明) {String} name  企业名称
     * @apiSuccess (返回参数说明) {obj} items  企业子级企业
     */
    public function adminCompanies()
    {
        $companies = (new CompanyService())->adminCompanies();
        return json(new SuccessMessageWithData(['data' => $companies]));
    }

    /**
     * @api {GET} /api/v1/company/consumptionLocation CMS管理端--企业管理-获取指定企业消费地点
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端--企业管理-获取指定企业消费地点
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/company/consumptionLocation
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"canteen":[{"id":6,"name":"饭堂1"},{"id":7,"name":"饭堂2"},{"id":17,"name":"newCanteen"}],"shop":{"id":1,"name":"小饭堂"}}}
     * @apiSuccess (返回参数说明) {obj} canteen 饭堂信息
     * @apiSuccess (返回参数说明) {int} id 饭堂id
     * @apiSuccess (返回参数说明) {string} name 饭堂名称
     * @apiSuccess (返回参数说明) {obj} shop 小卖部信息
     * @apiSuccess (返回参数说明) {int} id 小卖部id
     * @apiSuccess (返回参数说明) {string} name 小卖部名称
     * @apiSuccess (返回参数说明) {string} taking_mode 取货方式：1｜到店取；2｜送货上门；3｜全部都显示
     */
    public function consumptionLocation()
    {
        $company_id = Request::param('company_id');
        $locations = (new CompanyService())->consumptionLocation($company_id);
        return json(new SuccessMessageWithData(['data' => $locations]));
    }

    /**
     * @api {POST} /api/v1/company/wxConfig/save CMS管理端--企业管理--添加/修改企业微信支付配置信息
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端--企业管理--添加企业微信支付配置信息
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 1,
     *       "company_id": 1,
     *       "app_id": wx60311f2f47c86a3e,
     *       "mch_id": 1555725021
     *     }
     * @apiParam (请求参数说明) {int} company_id  账户id
     * @apiParam (请求参数说明) {string} app_id  公众号app_id
     * @apiParam (请求参数说明) {string} mch_id  支付商户id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function saveCompanyWxConfig()
    {
        $params = Request::param();
        (new CompanyService())->saveCompanyWxConfig($params);
        return json(new SuccessMessage());
    }

    /**
     * @api {POST} /api/v1/company/wxConfig CMS管理端--企业管理--查看企业微信支付配置信息
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端--企业管理--添加企业微信支付配置信息
     * @apiExample {post}  请求样例:
     *    {
     *       "company_id": 1
     *     }
     * @apiParam (请求参数说明) {int} company_id  企业id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"id":5,"company_id":95,"state":1,"app_id":"wx60311f2f47c86a3e","mch_id":"1596044941","create_time":"2020-05-29 09:21:09","update_time":"2020-05-29 09:21:09"}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id  配置id
     * @apiSuccess (返回参数说明) {string} app_id  公众号app_id
     * @apiSuccess (返回参数说明) {string} mch_id  支付商户id
     */
    public function wxConfig()
    {
        $companyId = Request::param('company_id');
        $config = (new CompanyService())->wxConfig($companyId);
        return json(new SuccessMessageWithData(['data' => $config]));
    }

    /**
     * @api {POST} /api/v1/company/nhConfig CMS管理端--企业管理--查看企业农行支付配置信息
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端--企业管理--添加企业微信支付配置信息
     * @apiExample {post}  请求样例:
     *    {
     *       "company_id": 1
     *     }
     * @apiParam (请求参数说明) {int} company_id  企业id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"id":10,"company_id":98,"code":"JF-EPAY2020062403555","create_time":"2020-06-29 08:12:03","update_time":"2020-06-29 08:12:07","state":1,"pfx":"enpingsk","prikey":"aa123123"}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id  配置id
     * @apiSuccess (返回参数说明) {string} code  缴费项目编号
     * @apiSuccess  (返回参数说明) {string} prikey  支付商户id
     */
    public function nhConfig()
    {
        $companyId = Request::param('company_id');
        $config = (new CompanyService())->nhConfig($companyId);
        return json(new SuccessMessageWithData(['data' => $config]));
    }


    /**
     * @api {POST} /api/v1/company/nhConfig/save CMS管理端--企业管理--添加/更新企业农行支付配置信息
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端--企业管理--添加企业农行支付配置信息
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 1,
     *       "company_id": 1,
     *       "code":"JF-EPAY2019062401722",
     *       "prikey": ab123,
     *       "pfx": nonghang,
     *     }
     * @apiParam (请求参数说明) {int} id  账户id：更新操作传入
     * @apiParam (请求参数说明) {int} company_id  企业id
     * @apiParam (请求参数说明) {string} code  缴费项目编号
     * @apiParam (请求参数说明) {string} prikey  支付商户id
     * @apiParam (请求参数说明) {string} pfx  证书地址
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function saveCompanyNHConfig()
    {
        $params = Request::param();
        (new CompanyService())->saveCompanyNHConfig($params);
        return json(new SuccessMessage());
    }


    /**
     * @api {GET} /api/v1/company/qrcode CMS管理端--企业管理-获取非企业人员二维码
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端--企业管理-获取非企业人员二维码
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/company/qrcode
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"url":"http://"}}
     * @apiSuccess (返回参数说明) {string} url 地址
     */
    public function getOutQRCode()
    {
        $company_id = Request::param('company_id');
        $url = (new CompanyService())->getOutQRCode($company_id);
        return json(new SuccessMessageWithData(['data' => $url]));
    }

    /**
     * @api {GET} /api/v1/company/consumptionType CMS管理端--企业管理-获取企业消费方式
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端--企业管理-获取企业消费方式
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/company/consumptionType?company_id=74
     * @apiParam (请求参数说明) {int} company_id  企业id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"consumptionType":"qrcode,card"}}
     * @apiSuccess (返回参数说明) {string} consumptionType 消费方式：qrcode： 二维码；card：刷卡；face：刷脸，多种类型逗号分隔
     */
    public function consumptionType()
    {
        $company_id = Request::param('company_id');
        $config = (new CompanyService())->consumptionType($company_id);
        return json(new SuccessMessageWithData(['data' => $config]));

    }

    /**
     * @api {POST} /api/v1/company/consumptionType/update CMS管理端--企业管理--修改企业消费方式
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端--企业管理--修改企业消费方式
     * @apiExample {post}  请求样例:
     *    {
     *       "company_id": 1,
     *       "consumption_type": "qrcode,face"
     *     }
     * @apiParam (请求参数说明) {int} company_id  企业id
     * @apiParam (请求参数说明) {string} consumption_type  消费方式：qrcode： 二维码；card：刷卡；face：刷脸，多种类型逗号分隔
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function updateConsumptionType()
    {
        $company_id = Request::param('company_id');
        $consumption_type = Request::param('consumption_type');
        (new CompanyService())->updateConsumptionType($company_id, $consumption_type);
        return json(new SuccessMessage());
    }


}