<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\ModuleService;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use think\facade\Request;

class Module extends BaseController
{

    /**
     * @api {POST} /api/v1/module/system/save CMS管理端-新增系统功能模块
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-新增系统功能模块
     * @apiExample {post}  请求样例:
     *    {
     *       "name": "设置",
     *       "url": "module/system",
     *       "parent_id": 0
     *     }
     * @apiParam (请求参数说明) {string} name  模块名称
     * @apiParam (请求参数说明) {string} url  模块路由
     * @apiParam (请求参数说明) {int} parent_id  上级模块id;0表示无上级模块是顶级模块
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     */
    public function saveSystem()
    {
        $params = $this->request->param();;
        (new ModuleService())->saveSystem($params);
        return json(new SuccessMessage());

    }

    /**
     * @api {POST} /api/v1/module/system/canteen/save CMS管理端-新增系统饭堂功能模块
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-新增系统饭堂功能模块
     * @apiExample {post}  请求样例:
     *    {
     *       "name": "设置",
     *       "type": 1,
     *       "default": 1,
     *       "url": "module/system",
     *       "icon": "http://icon.com",
     *       "parent_id": 0
     *     }
     * @apiParam (请求参数说明) {string} name  模块名称
     * @apiParam (请求参数说明) {int} type  模块类别：1|pc;2|手机端
     * @apiParam (请求参数说明) {int} default  是否默认模块：1|是;2|否
     * @apiParam (请求参数说明) {string} url  模块路由
     * @apiParam (请求参数说明) {string} icon  模块图标
     * @apiParam (请求参数说明) {int} parent_id  上级模块id;0表示无上级模块是顶级模块
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     */
    public function saveSystemCanteen()
    {
        $params = $this->request->param();;
        (new ModuleService())->saveSystemCanteen($params);
        return json(new SuccessMessage());

    }

    /**
     * @api {POST} /api/v1/module/system/shop/save CMS管理端-新增系统小卖部功能模块
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-新增系统小卖部功能模块
     * @apiExample {post}  请求样例:
     *    {
     *       "name": "设置",
     *       "type": 1,
     *       "default": 1,
     *       "url": "module/system",
     *       "icon": "http://icon.com",
     *       "parent_id": 0
     *     }
     * @apiParam (请求参数说明) {string} name  模块名称
     * @apiParam (请求参数说明) {int} type  模块类别：1|pc;2|手机端
     * @apiParam (请求参数说明) {int} default  是否默认模块：1|是;2|否
     * @apiParam (请求参数说明) {string} url  模块路由
     * @apiParam (请求参数说明) {string} icon  模块图标
     * @apiParam (请求参数说明) {int} parent_id  上级模块id;0表示无上级模块是顶级模块
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     */
    public function saveSystemShop()
    {
        $params = $this->request->param();;
        (new ModuleService())->saveSystemShop($params);
        return json(new SuccessMessage());

    }

    /**
     * @api {GET} /api/v1/modules  CMS管理端-获取系统模块/系统饭堂模块/系统小卖部模块
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-获取系统模块/系统饭堂模块/系统小卖部模块
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/modules?type=1
     * @apiParam (请求参数说明) {int} type   模块类别：1|系统功能模块；2|系统饭堂功能模块；3|系统小卖部功能模块
     * @apiSuccessExample {json} 系统功能模块返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":1,"name":"配置管理","url":"module\/system","state":1,"create_time":"2019-07-26 00:04:25","update_time":"2019-07-26 00:04:25","parent_id":0,"items":[{"id":2,"name":"企业管理","url":"module\/system","state":1,"create_time":"2019-07-26 00:05:43","update_time":"2019-07-26 00:05:43","parent_id":1},{"id":3,"name":"企业明细","url":"module\/system","state":1,"create_time":"2019-07-26 00:05:53","update_time":"2019-07-26 00:05:53","parent_id":1}]}]}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id 模块id
     * @apiSuccess (返回参数说明) {string} url 模块路由
     * @apiSuccess (返回参数说明) {int} state 模块状态：1|正常；2|停用
     * @apiSuccess (返回参数说明) {string} create_time 创建时间
     * @apiSuccess (返回参数说明) {string} parent_id 上级id；0表示顶级
     * @apiSuccess (返回参数说明) {obj} items 当前模块子级
     * @apiSuccessExample {json} 系统饭堂功能模块/系统小卖部功能模块返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":2,"name":"小卖部","type":1,"default":1,"state":1,"create_time":"2019-07-26 00:10:48","url":"module\/system","icon":"http:\/\/icon.com","parent_id":0},{"id":1,"name":"设置","type":1,"default":1,"state":1,"create_time":"2019-07-26 00:09:41","url":"module\/system","icon":"http:\/\/icon.com","parent_id":0}]}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id 模块id
     * @apiSuccess (返回参数说明) {string} url 模块路由
     * @apiSuccess (返回参数说明) {int} state 模块状态：1|正常；2|停用
     * @apiSuccess (返回参数说明) {int} type  模块类别：1|pc;2|手机端
     * @apiSuccess (返回参数说明) {int} default  是否默认模块：1|是;2|否
     * @apiSuccess (返回参数说明) {string} create_time 创建时间
     * @apiSuccess (返回参数说明) {string}icon  模块图标
     * @apiSuccess (返回参数说明) {string} parent_id 上级id；0表示顶级
     * @apiSuccess (返回参数说明) {obj} items 当前模块子级
     */
    public function systemModules()
    {
        $type = $this->request->param('type');
        $modules = (new ModuleService())->systemModules($type);
        return json(new SuccessMessageWithData(['data' => $modules]));


    }

    /**
     * @api {POST} /api/v1/module/system/handel CMS管理端-功能模块状态操作（系统模块/系统饭堂模块/系统小卖部模块）
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-功能模块状态操作（系统模块/企业模块）
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 1,
     *       "state":1,
     *       "type":1
     *     }
     * @apiParam (请求参数说明) {int} id  模块ID
     * @apiParam (请求参数说明) {int} state  模块状态：1|启用；2|停用
     * @apiParam (请求参数说明) {int} type  模块类别：1|系统功能模块；2|系统饭堂功能模块；3|系统小卖部功能模块
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     */
    public function handelSystem()
    {
        $params = Request::only(['id', 'state', 'type']);
        (new ModuleService())->handelModule($params);
        return json(new SuccessMessage());

    }

    /**
     * @api {POST} /api/v1/module/update CMS管理端-修改系统模块/系统饭堂模块/系统小卖部模块
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription   CMS管理端-修改系统模块/系统饭堂模块/系统小卖部模块
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 1,
     *       "name": "设置",
     *       "type": 1,
     *     }
     * @apiParam (请求参数说明) {int} id  模块ID
     * @apiParam (请求参数说明) {string} name  模块名称
     * @apiParam (请求参数说明) {int} type  模块类别：1|系统功能模块；2|系统饭堂功能模块；3|系统小卖部功能模块
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     */
    public function updateModule()
    {
        $params = $this->request->param();
        (new ModuleService())->updateModule($params);
        return json(new SuccessMessage());

    }

    /**
     * @api {GET} /api/v1/modules/admin  CMS管理端-登录成功后获取模块权限列表
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-登录成功后获取模块权限列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/modules/admin
     * @apiSuccessExample {json} 系统功能模块返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":1,"name":"配置管理","url":"module\/system","state":1,"create_time":"2019-07-26 00:04:25","update_time":"2019-07-26 00:04:25","parent_id":0,"items":[{"id":2,"name":"企业管理","url":"module\/system","state":1,"create_time":"2019-07-26 00:05:43","update_time":"2019-07-26 00:05:43","parent_id":1},{"id":3,"name":"企业明细","url":"module\/system","state":1,"create_time":"2019-07-26 00:05:53","update_time":"2019-07-26 00:05:53","parent_id":1}]}]}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id 模块id
     * @apiSuccess (返回参数说明) {string} url 模块路由
     * @apiSuccess (返回参数说明) {string} create_time 创建时间
     * @apiSuccess (返回参数说明) {string} parent_id 上级id；0表示顶级
     * @apiSuccess (返回参数说明) {obj} items 当前模块子级
     */
    public function adminModules()
    {
        $modules = (new ModuleService())->adminModules();
        return json(new SuccessMessageWithData(['data' => $modules]));
    }

    /**
     * @api {GET} /api/v1/modules/company  CMS管理端-获取企业功能模块（饭堂模块/小卖部模块）
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription  CMS管理端-获取企业功能模块（饭堂模块/小卖部模块）
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/modules/company?c_id=2
     * @apiParam (请求参数说明) {int} c_id  企业id
     * @apiSuccessExample {json} 系统功能模块返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":1,"name":"配置管理","url":"module\/system","state":1,"create_time":"2019-07-26 00:04:25","update_time":"2019-07-26 00:04:25","parent_id":0,"items":[{"id":2,"name":"企业管理","url":"module\/system","state":1,"create_time":"2019-07-26 00:05:43","update_time":"2019-07-26 00:05:43","parent_id":1},{"id":3,"name":"企业明细","url":"module\/system","state":1,"create_time":"2019-07-26 00:05:53","update_time":"2019-07-26 00:05:53","parent_id":1}]}]}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id 模块id
     * @apiSuccess (返回参数说明) {string} url 模块路由
     * @apiSuccess (返回参数说明) {int} state 模块状态：1|正常；2|停用
     * @apiSuccess (返回参数说明) {string} create_time 创建时间
     * @apiSuccess (返回参数说明) {string} parent_id 上级id；0表示顶级
     * @apiSuccess (返回参数说明) {obj} items 当前模块子级
     * @apiSuccessExample {json} 系统饭堂功能模块/系统小卖部功能模块返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"canteen":[{"id":1,"name":"设置","type":1,"default":1,"state":1,"create_time":"2019-07-26 00:09:41","url":"module\/system","icon":"http:\/\/icon.com","parent_id":0,"have":1,"items":[{"id":2,"name":"小卖部","type":2,"default":1,"state":1,"create_time":"2019-07-26 00:10:48","url":"module\/system","icon":"http:\/\/icon.com","parent_id":1,"have":1}]}],"shop":[{"id":1,"name":"小卖部","type":1,"default":1,"state":1,"create_time":"2019-07-26 00:30:21","url":"module\/system","icon":"http:\/\/icon.com","parent_id":0,"have":1}]}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {obj} canteen 饭堂模块
     * @apiSuccess (返回参数说明) {obj} shop 小卖部模块
     * @apiSuccess (返回参数说明) {int} id 模块id
     * @apiSuccess (返回参数说明) {string} url 模块路由
     * @apiSuccess (返回参数说明) {string} name 模块名称
     * @apiSuccess (返回参数说明) {int} type  模块类别：1|pc;2|手机端
     * @apiSuccess (返回参数说明) {int} have  该饭堂是否拥有该模块：1|是;2|否
     * @apiSuccess (返回参数说明) {string} create_time 创建时间
     * @apiSuccess (返回参数说明) {string}icon  模块图标
     * @apiSuccess (返回参数说明) {string} parent_id 上级id；0表示顶级
     * @apiSuccess (返回参数说明) {obj} items 当前模块子级
     */
    public function companyModules()
    {
        $c_id = $this->request->param('c_id');
        $modules = (new ModuleService())->companyModules($c_id);
        return json(new SuccessMessageWithData(['data' => $modules]));

    }

}