<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\model\CompanyDepartmentT;
use app\api\model\CompanyStaffT;
use app\api\service\DepartmentService;
use app\lib\enum\CommonEnum;
use app\lib\exception\DeleteException;
use app\lib\exception\ParameterException;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use app\lib\exception\UpdateException;
use think\facade\Request;

class Department extends BaseController
{
    /**
     * @api {POST} /api/v1/department/save CMS管理端-设置-新增企业部门
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription     CMS管理端-设置-新增部门
     * @apiExample {post}  请求样例:
     *    {
     *       "c_id": 2,
     *       "name": "董事会",
     *       "parent_id": 0
     *     }
     * @apiParam (请求参数说明) {string} c_id  企业id
     * @apiParam (请求参数说明) {string} name  部门名称
     * @apiParam (请求参数说明) {int} parent_id  上级部门id;0表示无上级部门，本次新增为顶级部门
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"id":1}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id 新增部门id
     */
    public function save()
    {
        $params = Request::param();
        $id = (new DepartmentService())->save($params);
        return json(new SuccessMessageWithData(['data' => ['id' => $id]]));
    }

    /**
     * @api {POST} /api/v1/department/update CMS管理端-设置-更新企业部门
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription     CMS管理端-设置-新增部门
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 1,
     *       "name": "董事会-修改"
     *     }
     * @apiParam (请求参数说明) {string} id  部门id
     * @apiParam (请求参数说明) {string} name  部门名称
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function update()
    {
        $params = Request::only('id,name');
        $id = CompanyDepartmentT::update($params);
        if (!$id) {
            throw  new UpdateException();
        }
        return json(new SuccessMessage());
    }

    /**
     * @api {POST} /api/v1/department/delete CMS管理端-设置-删除企业部门
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription  CMS管理端-设置-删除企业部门
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 1
     *     }
     * @apiParam (请求参数说明) {string} id  部门id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function delete()
    {
        $id = Request::param('id');
        (new DepartmentService())->deleteDepartment($id);
        return json(new SuccessMessage());
    }

    /**
     * @api {GET} /api/v1/departments CMS管理端-获取企业部门列表
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-获取企业部门列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/departments?c_id=2
     * @apiParam (请求参数说明) {int} c_id 企业id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":3,"parent_id":0,"name":"董事会-修改","count":"0","items":[{"id":5,"parent_id":3,"name":"B部门","count":"0","items":[{"id":8,"parent_id":5,"name":"B1部门","count":"0"},{"id":9,"parent_id":5,"name":"B2部门","count":"0"}]},{"id":4,"parent_id":3,"name":"A部门","count":"0","items":[{"id":7,"parent_id":4,"name":"A2部门","count":"0"},{"id":6,"parent_id":4,"name":"A1部门","count":"0"}]}]}]}
     * @apiSuccess (返回参数说明) {int} id 部门id
     * @apiSuccess (返回参数说明) {String} parent_id 部门上级id
     * @apiSuccess (返回参数说明) {String} name  部门名称
     * @apiSuccess (返回参数说明) {int} count 部门员工数量
     * @apiSuccess (返回参数说明) {obj} items 当前模块子级
     */
    public function departments()
    {
        $c_id = Request::param('c_id');
        $departments = (new DepartmentService())->departments($c_id);
        return json(new SuccessMessageWithData(['data' => $departments]));
    }


    /**
     * @api {GET} /api/v1/departments/official 微信端-获取用户当前企业部门列表
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription 微信端-获取用户当前企业部门列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/departments/official
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":3,"parent_id":0,"name":"董事会-修改","count":"0","items":[{"id":5,"parent_id":3,"name":"B部门","count":"0","items":[{"id":8,"parent_id":5,"name":"B1部门","count":"0"},{"id":9,"parent_id":5,"name":"B2部门","count":"0"}]},{"id":4,"parent_id":3,"name":"A部门","count":"0","items":[{"id":7,"parent_id":4,"name":"A2部门","count":"0"},{"id":6,"parent_id":4,"name":"A1部门","count":"0"}]}]}]}
     * @apiSuccess (返回参数说明) {int} id 部门id
     * @apiSuccess (返回参数说明) {String} parent_id 部门上级id
     * @apiSuccess (返回参数说明) {String} name  部门名称
     * @apiSuccess (返回参数说明) {int} count 部门员工数量
     * @apiSuccess (返回参数说明) {obj} items 当前模块子级
     */
    public function officialDepartments()
    {
        $c_id = \app\api\service\Token::getCurrentTokenVar('current_company_id');
        $departments = (new DepartmentService())->departments($c_id);
        return json(new SuccessMessageWithData(['data' => $departments]));
    }


    /**
     * @api {POST} /api/v1/department/staff/save CMS管理端-新增部门员工
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-新增部门员工
     * @apiExample {post}  请求样例:
     *    {
     *       "company_id": 1
     *       "d_id": 2,
     *       "t_id": 2,
     *       "code": "123456",
     *       "username": "张三",
     *       "phone": "18956225230"
     *       "card_num": "1212121"
     *     }
     * @apiParam (请求参数说明) {int}  company_id 企业id
     * @apiParam (请求参数说明) {string}  canteens json字符串,归属饭堂id列表
     * @apiParam (请求参数说明) {int} d_id  归属部门id
     * @apiParam (请求参数说明) {int} t_id  人员类型id
     * @apiParam (请求参数说明) {string} code  员工编号
     * @apiParam (请求参数说明) {string} username  姓名
     * @apiParam (请求参数说明) {string} phone  手机号
     * @apiParam (请求参数说明) {string} card_num  卡号
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function addStaff()
    {
        $params = Request::param();
        (new DepartmentService())->addStaff($params);
        return json(new SuccessMessage());
    }

    /**
     * @api {POST} /api/v1/department/staff/update CMS管理端-编辑部门员工
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-编辑部门员工
     * @apiExample {post}  请求样例:
     *    {
     *       "id":id,
     *       "canteens": [2,3]
     *       "cancel_canteens": [1]
     *       "d_id": 2,
     *       "t_id": 2,
     *       "code": "123456",
     *       "username": "张三",
     *       "phone": "18956225230"
     *       "card_num": "1212121"
     *       "expiry_date": "2019-08-03 15:48:03"
     *     }
     * @apiParam (请求参数说明) {int} id 员工id
     * @apiParam (请求参数说明) {string}  canteens json字符串,归属饭堂id列表
     * @apiParam (请求参数说明) {string}  cancel_canteens json字符串,取消绑定饭堂用户绑定关系id列表
     * @apiParam (请求参数说明) {int} d_id  归属部门id
     * @apiParam (请求参数说明) {int} t_id  人员类型id
     * @apiParam (请求参数说明) {string} code  员工编号
     * @apiParam (请求参数说明) {string} username  姓名
     * @apiParam (请求参数说明) {string} phone  手机号
     * @apiParam (请求参数说说明) {string} expiry_date  二维码有效期
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function updateStaff()
    {
        $params = Request::only('id,canteens,cancel_canteens,d_id,t_id,code,username,phone,card_num,expiry_date');
        (new DepartmentService())->updateStaff($params);
        return json(new SuccessMessage());
    }

    /**
     * @api {POST}  /api/v1/department/staff/upload CMS管理端-批量导入员工
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription  用file控件上传excel ，文件名称为：staffs
     * @apiExample {post}  请求样例:
     *    {
     *       "c_id": 1
     *     }
     * @apiParam (请求参数说明) {string} c_id 企业id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"fail":[{"name":"奥斯","msg":"系统中不存在该人员类型：员工1"}]}}
     * @apiSuccess (返回参数说明) {int} error_code 错误代码 0 表示没有错误
     * @apiSuccess (返回参数说明) {String} msg 操作结果描述
     * @apiSuccess (返回参数说明) {String} fail 上传失败信息
     * @apiSuccess (返回参数说明) {String} fail|name 上传失败用户名
     * @apiSuccess (返回参数说明) {String} fail|msg 上传失败原因
     */
    public function uploadStaffs()
    {
        $staffs_excel = request()->file('staffs');
        if (is_null($staffs_excel)) {
            throw  new ParameterException(['msg' => '缺少excel文件']);
        }
        $company_id = Request::param('c_id');
        $res = (new DepartmentService())->uploadStaffs($company_id, $staffs_excel);
        return json(new SuccessMessageWithData(['data' => $res]));

    }

    /**
     * @api {GET} /api/v1/staffs CMS管理端-企业员工列表
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-企业员工列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/staffs?page=1&size=10&c_id=2&d_id=4
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {int} c_id 企业id
     * @apiParam (请求参数说明) {int} d_id 企业部门id,获取全部传入：0
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":329,"per_page":"1","current_page":1,"last_page":329,"data":[{"id":350,"t_id":3,"type":"员工","d_id":4,"department":"A部门","code":"123456","username":"里斯","phone":"18956225230","card_num":"a123","create_time":"2019-08-03 00:47:59","expiry_date":"0000-00-00 00:00:00","url":"http:\/\/canteen.tonglingok.com\/static\/qrcode\/517e9af47c57e0e789e4bd113d5b0c9b54a615ca.png","q_id":329,"canteens":[{"id":1,"staff_id":350,"canteen_id":1,"info":{"id":1,"name":"大饭堂"}}]}]}}
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 员工id
     * @apiSuccess (返回参数说明) {string} type 人员类型
     * @apiSuccess (返回参数说明) {int} t_id 人员类型id
     * @apiSuccess (返回参数说明) {string} department 所属部门
     * @apiSuccess (返回参数说明) {int} d_id 所属部门id
     * @apiSuccess (返回参数说明) {string} code 员工编号
     * @apiSuccess (返回参数说明) {string} username 姓名
     * @apiSuccess (返回参数说明) {string} phone 手机号
     * @apiSuccess (返回参数说明) {string} card_num 卡号
     * @apiSuccess (返回参数说明) {string} expiry_date 二维码有效期
     * @apiSuccess (返回参数说明) {string} url 二维码地址
     * @apiSuccess (返回参数说明) {int} q_id 二维码id
     * @apiSuccess (返回参数说明) {string} create_time 创建时间
     * @apiSuccess (返回参数说明) {obj} canteens  所属饭堂
     * @apiSuccess (返回参数说明) {obj} canteens|info  饭堂信息
     * @apiSuccess (返回参数说明) {string} info|id  饭堂id
     * @apiSuccess (返回参数说明) {string} info|name  饭堂名称
     */
    public function staffs($page = 1, $size = 10)
    {
        $params = Request::param();
        $c_id = $params['c_id'];
        $d_id = $params['d_id'];
        $staffs = (new DepartmentService())->companyStaffs($page, $size, $c_id, $d_id);
        return json(new SuccessMessageWithData(['data' => $staffs]));

    }

    /**
     * @api {POST} /api/v1/department/staff/delete CMS管理端-删除员工
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription  CMS管理端-删除员工
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 1
     *     }
     * @apiParam (请求参数说明) {string} id  员工id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function deleteStaff()
    {
        $id = Request::param('id');
        $staff = CompanyStaffT::update(['state' => CommonEnum::STATE_IS_FAIL], ['id' => $id]);
        if (!$staff) {
            throw  new DeleteException();
        }
        return json(new SuccessMessage());
    }

    /**
     * @api {POST} /api/v1/department/staff/move CMS管理端-移动员工部门
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription   CMS管理端-移动员工部门
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 1
     *       "d_id": 1
     *     }
     * @apiParam (请求参数说明) {string} id  员工id
     * @apiParam (请求参数说明) {string} d_id  部门id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function moveStaffDepartment()
    {
        $params = Request::param();

        $id = $params['id'];
        $d_id = $params['d_id'];
        $staff = CompanyStaffT::update(['d_id' => $d_id], ['id' => $id]);
        if (!$staff) {
            throw  new UpdateException();
        }
        return json(new SuccessMessage());
    }

    /**
     * @api {POST} /api/v1/staff/qrcode/save CMS管理端-生成员工二维码
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription     CMS管理端-生成员工二维码
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 2
     *       "minute": 10
     *       "hour": 0
     *       "day": 0
     *       "month": 0
     *       "year": 20
     *     }
     * @apiParam (请求参数说明) {int} id  员工id
     * @apiParam (请求参数说明) {int} minute   更新周期|分钟
     * @apiParam (请求参数说明) {int} hour   更新周期|小时
     * @apiParam (请求参数说明) {int} day   更新周期|天
     * @apiParam (请求参数说明) {int} month   更新周期|月
     * @apiParam (请求参数说明) {int} year  更新周期|年
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"usernmae":"里斯","url":"http:\/\/canteen.tonglingok.com\/static\/qrcode\/ebf8ef681436a5a53b91549fb44d3b469d0282b7.png","create_time":"2019-08-04 02:34:52","expiry_date":"2019-08-04 03:34:52"}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {string} username 用户名
     * @apiSuccess (返回参数说明) {string} url 二维码地址
     * @apiSuccess (返回参数说明) {string} create_time创建时间
     * @apiSuccess (返回参数说明) {string} expiry_date 二维码有效期
     */
    public function createStaffQrcode()
    {
        $params = Request::param();
        $info = (new DepartmentService())->updateQrcode2($params);
        return json(new SuccessMessageWithData(['data' => $info]));
    }

    /**
     * @api {GET} /api/v1/admin/departments 微信端-公告发布-获取用户可以查看部门列表
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription 微信端-公告发布-获取用户可以查看部门列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/admin/departments
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":3,"name":"董事会-修改"},{"id":4,"name":"A部门"},{"id":5,"name":"B部门"},{"id":6,"name":"A1部门"},{"id":7,"name":"A2部门"},{"id":8,"name":"B1部门"},{"id":9,"name":"B2部门"}]}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id 部门id
     * @apiSuccess (返回参数说明) {String} name  部门名称
     */
    public function adminDepartments()
    {
        $departments = (new DepartmentService())->adminDepartments();
        return json(new SuccessMessageWithData(['data' => $departments]));
    }

    /**
     * @api {GET} /api/v1/department/staffs 微信端-公告发布-获取部门人员列表
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription 微信端-公告发布-获取部门人员列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/department/staffs?department_id=3
     * @apiParam (请求参数说明) {int} department_id 部门id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":9,"username":"张三"},{"id":10,"username":"张三"},{"id":359,"username":"rush"},{"id":360,"username":"陈同学"},{"id":361,"username":"陈工"},{"id":362,"username":"陈2"},{"id":363,"username":"陈3"},{"id":364,"username":"黄工"}]}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id 员工id
     * @apiSuccess (返回参数说明) {string} username 员工姓名
     */
    public function departmentStaffs()
    {
        $department_id = Request::param('department_id');
        $staffs = (new DepartmentService())->departmentStaffs($department_id);
        return json(new SuccessMessageWithData(['data' => $staffs]));
    }

    /**
     * @api {GET} /api/v1/departments/recharge CMS管理端-充值管理-获取用户可以查看部门列表
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-充值管理-获取用户可以查看部门列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/departments/recharge
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":3,"name":"董事会-修改"},{"id":4,"name":"A部门"},{"id":5,"name":"B部门"},{"id":6,"name":"A1部门"},{"id":7,"name":"A2部门"},{"id":8,"name":"B1部门"},{"id":9,"name":"B2部门"}]}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id 部门id
     * @apiSuccess (返回参数说明) {String} name  部门名称
     */
    public function departmentsForRecharge()
    {
        $departments = (new DepartmentService())->departmentsForRecharge();
        return json(new SuccessMessageWithData(['data' => $departments]));

    }

    /**
     * @api {GET} /api/v1/department/staffs/recharge CMS管理端-充值管理--企业员工列表
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-充值管理--企业员工列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/department/staffs/recharge?page=1&size=10&department_id=4&key=
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {int} department_id 部门id 全部传0
     * @apiParam (请求参数说明) {int} key 关键字查询
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":6,"per_page":20,"current_page":1,"last_page":1,"data":[{"id":364,"company":"一级企业","department":"董事会-修改","code":"0080808","username":"黄工","phone":"13411111111"},{"id":363,"company":"一级企业","department":"董事会-修改","code":"1014112221","username":"陈3","phone":"13452151123"},{"id":362,"company":"一级企业","department":"董事会-修改","code":"1014112","username":"陈2","phone":"13452151123"},{"id":361,"company":"一级企业","department":"董事会-修改","code":"10110","username":"陈工","phone":"13411201101"},{"id":360,"company":"一级企业","department":"董事会-修改","code":"10110","username":"陈同学","phone":"13725305169"},{"id":359,"company":"一级企业","department":"董事会-修改","code":"011001","username":"rush","phone":"13725305169"}]}}
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 员工id
     * @apiSuccess (返回参数说明) {string} company 所属企业
     * @apiSuccess (返回参数说明) {string} department 所属部门
     * @apiSuccess (返回参数说明) {string} code 员工编号
     * @apiSuccess (返回参数说明) {string} card_num 员工卡号
     * @apiSuccess (返回参数说明) {string} username 姓名
     * @apiSuccess (返回参数说明) {string} phone 手机号
     */
    public function staffsForRecharge($page = 1, $size = 20, $department_id = 0, $key = '')
    {
        $staffs = (new DepartmentService())->staffsForRecharge($page, $size, $department_id, $key);
        return json(new SuccessMessageWithData(['data' => $staffs]));

    }

    /**
     * @api {GET} /api/v1/export/staffs CMS管理端-设置-部门人员设置-导出企业员工信息
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-设置-部门人员设置-导出企业员工信息
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/export/staffs?company_id=2&department_id=4
     * @apiParam (请求参数说明) {int} company_id 企业id
     * @apiParam (请求参数说明) {int} department_id 企业部门id,获取全部传入：0
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"url":"http:\/\/canteen.tonglingok.com\/static\/excel\/download\/企业员工导出_20191210235358.xls"}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {string} url 导出excel下载链接
     */
    public function exportStaffs()
    {
        $company_id = Request::param('company_id');
        $department_id = Request::param('department_id');
        $url = (new DepartmentService())->exportStaffs($company_id, $department_id);
        return json(new SuccessMessageWithData(['data' => $url]));

    }

    /**
     * @api {GET} /api/v1/department/staffs/search CMS管理端-模糊查询企业员工
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-企业员工列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/department/staffs/search?page=1&size=10&c_id=2&d_id=4&key=张三
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {int} c_id 企业id
     * @apiParam (请求参数说明) {int} d_id 企业部门id,获取全部传入：0
     * @apiParam (请求参数说明) {string} key 手机号或者姓名
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":329,"per_page":"1","current_page":1,"last_page":329,"data":[{"id":350,"t_id":3,"type":"员工","d_id":4,"department":"A部门","code":"123456","username":"里斯","phone":"18956225230","card_num":"a123","create_time":"2019-08-03 00:47:59","expiry_date":"0000-00-00 00:00:00","url":"http:\/\/canteen.tonglingok.com\/static\/qrcode\/517e9af47c57e0e789e4bd113d5b0c9b54a615ca.png","q_id":329,"canteens":[{"id":1,"staff_id":350,"canteen_id":1,"info":{"id":1,"name":"大饭堂"}}]}]}}
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 员工id
     * @apiSuccess (返回参数说明) {string} type 人员类型
     * @apiSuccess (返回参数说明) {int} t_id 人员类型id
     * @apiSuccess (返回参数说明) {string} department 所属部门
     * @apiSuccess (返回参数说明) {int} d_id 所属部门id
     * @apiSuccess (返回参数说明) {string} code 员工编号
     * @apiSuccess (返回参数说明) {string} username 姓名
     * @apiSuccess (返回参数说明) {string} phone 手机号
     * @apiSuccess (返回参数说明) {string} card_num 卡号
     * @apiSuccess (返回参数说明) {string} expiry_date 二维码有效期
     * @apiSuccess (返回参数说明) {string} url 二维码地址
     * @apiSuccess (返回参数说明) {int} q_id 二维码id
     * @apiSuccess (返回参数说明) {string} create_time 创建时间
     * @apiSuccess (返回参数说明) {obj} canteens  所属饭堂
     * @apiSuccess (返回参数说明) {obj} canteens|info  饭堂信息
     * @apiSuccess (返回参数说明) {string} info|id  饭堂id
     * @apiSuccess (返回参数说明) {string} info|name  饭堂名称
     */
    public function searchStaff($page = 1, $size = 10, $key = '')
    {
        $company_id = Request::param('c_id');
        $department_id = Request::param('d_id');
        $staff = (new DepartmentService())->searchStaff($page, $size, $company_id, $department_id, $key);
        return json(new SuccessMessageWithData(['data' => $staff]));

    }
}