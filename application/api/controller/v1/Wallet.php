<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\model\OrderT;
use app\api\model\PayT;
use app\api\model\PayWxT;
use app\api\model\RechargeCashT;
use app\api\service\AdminService;
use app\api\service\v2\DownExcelService;
use app\api\service\WalletService;
use app\lib\exception\ParameterException;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use think\facade\Request;
use function Composer\Autoload\includeFile;

class Wallet extends BaseController
{
    /**
     * @api {POST} /api/v1/wallet/recharge/cash CMS管理端--充值管理--现金充值
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription     CMS管理端--充值管理--现金充值
     * @apiExample {post}  请求样例:
     *    {
     *       "account_id": 1,
     *       "money": 200,
     *       "remark": '备注',
     *       "detail":[{"staff_id":1},{"staff_id":2}]
     *     }
     * @apiParam (请求参数说明) {int} account_id 账户ID：如果企业没有开通账户管理可以不用传
     * @apiParam (请求参数说明) {int} money 充值金额
     * @apiParam (请求参数说明) {int} remark 备注
     * @apiParam (请求参数说明) {obj} detail  充值人员信息json字符串
     * @apiParam (请求参数说明) {int} staff_id  充值用户id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function rechargeCash()
    {
        $params = Request::param();
        (new WalletService())->rechargeCash($params);
        return json(new SuccessMessage());
    }


    /**
     * @api {POST} /api/v1/wallet/recharge/delete CMS管理端--充值管理--现金充值-回撤充值记录
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription     CMS管理端--充值管理--现金充值-回撤充值记录
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 1,
     *       "type": 1
     *     }
     * @apiParam (请求参数说明) {int} id 充值id,
     * @apiParam (请求参数说明) {int} type 充值渠道,
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function deleteRecharge()
    {
        $params = Request::param();
        (new WalletService())->rechargeCash($params);
        return json(new SuccessMessage());
    }


    /**
     * @api {POST}  /api/v1/wallet/recharge/upload CMS管理端--充值管理--批量充值
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription  用file控件上传excel ，文件名称为：cash
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} error_code 错误代码 0 表示没有错误
     * @apiSuccess (返回参数说明) {String} msg 操作结果描述
     */
    public function rechargeCashUpload()
    {
        $cash_excel = request()->file('cash');
        if (is_null($cash_excel)) {
            throw  new ParameterException(['msg' => '缺少excel文件']);
        }
        $res = (new WalletService())->rechargeCashUpload($cash_excel);
        return json(new SuccessMessageWithData(['data' => $res]));

    }

    /**
     * @api {GET} /api/v1/wallet/recharge/admins CMS管理端-充值管理-充值记录明细-充值人员列表
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-充值管理-充值记录明细-充值人员列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/wallet/recharge/admins?&module_id=1
     * @apiParam (请求参数说明) {int} module_id 模块id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":7,"role":"饭堂管理员2"}]}
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 角色id
     * @apiSuccess (返回参数说明) {string} role  角色名称
     */
    public function rechargeAdmins()
    {
        $module_id = Request::param('module_id');
        $admins = (new AdminService())->rechargeAdmins($module_id);
        return json(new SuccessMessageWithData(['data' => $admins]));


    }

    /**
     * @api {GET} /api/v1/wallet/recharges CMS管理端-充值管理-充值记录列表
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-充值管理-充值记录列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/wallet/recharges?time_begin=2019-09-01&time_end=2019-11-01&admin_id=0&username&type=all&page=1&size=10&department_id
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {int} money_type 金额类型：0 全部；1：充值；2：退款
     * @apiParam (请求参数说明) {string} time_begin 查询开始时间
     * @apiParam (请求参数说明) {string} time_end 查询截止时间
     * @apiParam (请求参数说明) {String} username 被充值用户
     * @apiParam (请求参数说明) {int} admin_id 充值人员id，全部传入0
     * @apiParam (请求参数说明) {int} department_id 部门id，全部传入0
     * @apiParam (请求参数说明) {String} type 充值途径:目前有：cash：现金；1:微信；2:农行；all：全部
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":2,"per_page":20,"current_page":1,"last_page":1,"data":[{"create_time":"2019-10-31 18:32:47","username":null,"money":"200.00","type":"cash","admin":"系统超级管理员","remark":""},{"create_time":"2019-10-31 18:32:48","username":null,"money":"200.00","type":"cash","admin":"系统超级管理员","remark":""}]}}
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {String} create_time 创建时间
     * @apiSuccess (返回参数说明) {String} username  姓名
     * @apiSuccess (返回参数说明) {int} money 充值金额
     * @apiSuccess (返回参数说明) {int} account 账户名称
     * @apiSuccess (返回参数说明) {string} admin 充值人员
     * @apiSuccess (返回参数说明) {string} remark 备注
     * @apiSuccess (返回参数说明) {string} money_type 金额状态：1：充值；2：退款
     */
    public function rechargeRecords($page = 1, $size = 20, $type = 'all', $admin_id = 0, $username = '', $department_id = 0, $money_type = 0)
    {
        $time_begin = Request::param('time_begin');
        $time_end = Request::param('time_end');
        $records = (new WalletService())->rechargeRecords($time_begin, $time_end,
            $page, $size, $type, $admin_id, $username, $department_id, $money_type);
        return json(new SuccessMessageWithData(['data' => $records]));

    }

    /**
     * @api {GET} /api/v1/wallet/recharges/export CMS管理端-充值管理-充值记录列表-导出报表
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-充值管理-充值记录列表-导出报表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/wallet/recharges/export?time_begin=2019-09-01&time_end=2019-11-01&admin_id=0&username&type=all&department_id=0
     * @apiParam (请求参数说明) {string} time_begin 查询开始时间
     * @apiParam (请求参数说明) {string} time_end 查询截止时间
     * @apiParam (请求参数说明) {String} username 被充值用户
     * @apiParam (请求参数说明) {int} admin_id 充值人员id，全部传入0
     * @apiParam (请求参数说明) {int} department_id 部门id，全部传入0
     * @apiParam (请求参数说明) {int} money_type 金额类型：0 全部；1：充值；2：退款
     * @apiParam (请求参数说明) {String} type 充值途径:目前有：cash：现金；1:微信；2:农行；all：全部
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"url":"http:\/\/canteen.tonglingok.com\/static\/excel\/download\/材料价格明细_20190817005931.xls"}}
     * @apiSuccess (返回参数说明) {int} error_code 错误代码 0 表示没有错误
     * @apiSuccess (返回参数说明) {string} msg 操作结果描述
     * @apiSuccess (返回参数说明) {string} url 下载地址
     */
    public function exportRechargeRecords($type = 'all', $admin_id = 0, $username = '', $department_id = 0, $money_type = 0)
    {
        $time_begin = Request::param('time_begin');
        $time_end = Request::param('time_end');
        (new DownExcelService())->exportRechargeRecords($time_begin, $time_end, $type,
            $admin_id, $username, $department_id, $money_type, 'rechargeRecords');
        return json(new  SuccessMessage());
        /*        $records = (new WalletService())->exportRechargeRecords($time_begin, $time_end, $type, $admin_id, $username, $department_id);
                return json(new SuccessMessageWithData(['data' => $records]));*/

    }


    /**
     * @api {GET} /api/v1/wallet/users/balance CMS管理端-充值管理-饭卡余额查询
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-充值管理-饭卡余额查询
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/wallet/users/balance?&department_id=0&user&phone=&page=1&size=10
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {String} user 人员信息
     * @apiParam (请求参数说明) {String} phone 手机号
     * @apiParam (请求参数说明) {int} department_id 部门id，全部传0
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":5,"per_page":"1","current_page":1,"last_page":5,"data":[{"staff_id":6699,"username":"蚊","code":"1000","card_num":"680141047","phone":"15014335935","department":"1部","balance":"1216.30"}]}}
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {String} username  姓名
     * @apiSuccess (返回参数说明) {int} code 编码
     * @apiSuccess (返回参数说明) {string} card_num 卡号
     * @apiSuccess (返回参数说明) {string} phone 手机号
     * @apiSuccess (返回参数说明) {string} department 部门名称
     * @apiSuccess (返回参数说明) {string} balance 余额
     */
    public function usersBalance($page = 1, $size = 20, $department_id = 0, $user = '', $phone = '')
    {
        $users = (new WalletService())->usersBalance($page, $size, $department_id, $user, $phone);
        return json(new SuccessMessageWithData(['data' => $users]));

    }

    /**
     * @api {GET} /api/v1/wallet/users/balance/export CMS管理端-充值管理-饭卡余额查询-导出报表
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-充值管理-饭卡余额查询-导出报表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/wallet/users/balance/export?&department_id=0&user&phone=
     * @apiParam (请求参数说明) {String} user 人员信息
     * @apiParam (请求参数说明) {String} phone 手机号
     * @apiParam (请求参数说明) {int} department_id 部门id，全部传0
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"url":"http:\/\/canteen.tonglingok.com\/static\/excel\/download\/材料价格明细_20190817005931.xls"}}
     * @apiSuccess (返回参数说明) {int} error_code 错误代码 0 表示没有错误
     * @apiSuccess (返回参数说明) {string} msg 操作结果描述
     * @apiSuccess (返回参数说明) {string} url 下载地址
     */
    public function exportUsersBalance($department_id = 0, $user = '', $phone = '')
    {
        (new DownExcelService())->exportUsersBalance($department_id, $user, $phone, 'userBalance');
        return json(new SuccessMessage());
        /*        $users = (new WalletService())->exportUsersBalance($department_id, $user, $phone);
                return json(new SuccessMessageWithData(['data' => $users]));*/

    }


    /**
     * @api {POST} /api/v1/wallet/clearBalance CMS管理端--充值管理--饭卡余额查询-一键清零
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端--充值管理--饭卡余额查询-一键清零（企业管理员才有权限）
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function clearBalance()
    {
        (new WalletService())->clearBalance();
        return json(new SuccessMessage());

    }

    /**
     * @api {POST} /api/v1/wallet/supplement CMS管理端--设置--补录管理-单个充值
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端--设置--补录管理-单个充值
     * @apiExample {post}  请求样例:
     *    {
     *       "canteen_id": 7,
     *       "remark": 备注,
     *       "consumption_date":2019-11-04,
     *       "dinner_id":7,
     *       "money":10,
     *       "staff_ids":"1,2,3",
     *       "type":1,
     *       "account_id":1,
     *     }
     * @apiParam (请求参数说明) {int} money 充值金额
     * @apiParam (请求参数说明) {int} remark 备注
     * @apiParam (请求参数说明) {obj} canteen_id  饭堂id
     * @apiParam (请求参数说明) {string} consumption_date  消费日期
     * @apiParam (请求参数说明) {string} dinner_id  餐次id
     * @apiParam (请求参数说明) {string} staff_ids  d多用户id，用逗号分隔：1,2,3
     * @apiParam (请求参数说明) {string} type  消费状态：1：补充；2：补扣
     * @apiParam (请求参数说明) {int} account_id  账户ID
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function rechargeSupplement()
    {
        $params = Request::param();
        (new WalletService())->rechargeSupplement($params);
        return json(new SuccessMessage());
    }

    /**
     * @api {POST}  /api/v1/wallet/supplement/upload CMS管理端--设置--补录管理--批量充值
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription  用file控件上传excel ，文件名称为：supplement
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} error_code 错误代码 0 表示没有错误
     * @apiSuccess (返回参数说明) {String} msg 操作结果描述
     */
    public function rechargeSupplementUpload()
    {
        $supplement_excel = request()->file('supplement');
        if (is_null($supplement_excel)) {
            throw  new ParameterException(['msg' => '缺少excel文件']);
        }
        $res = (new WalletService())->rechargeSupplementUpload($supplement_excel);
        return json(new SuccessMessageWithData(['data' => $res]));
    }

    /**
     * @api {POST} /api/v1/wallet/pay 微信端--用户充值
     * @apiGroup   Official
     * @apiVersion 3.0.0
     * @apiDescription    微信端--用户充值
     * @apiExample {post}  请求样例:
     *    {
     *       "method_id": 1,
     *       "money": 100
     *     }
     * @apiParam (请求参数说明) {int} money 充值金额
     * @apiParam (请求参数说明) {int} method_id  充值方式：1：微信支付
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"id":1}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id 订单id
     */
    public function saveOrder()
    {
        $params = Request::param();
        $order_id = (new WalletService())->saveOrder($params);
        return json(new SuccessMessageWithData(['data' => $order_id]));

    }

    /**
     * @api {GET} /api/v1/wallet/pay/getPreOrder  微信端-微信支付-获取支付信息
     * @apiGroup  Official
     * @apiVersion 1.0.1
     * @apiDescription 微信端-微信支付-微信支付获取支付信息
     * @apiExample {get}  请求样例:
     * http://mengant.cn/api/v1/wallet/pay/getPreOrder?order_id=1
     * @apiParam (请求参数说明) {int} order_id 订单id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"return_code":"SUCCESS","return_msg":"OK","appid":"wx60311f2f47c86a3e","mch_id":"1555725021","sub_mch_id":"1563901631","nonce_str":"kU7RuppRQZDrFfwu","sign":"B4B16DDD14C77B5D94FFE9B8CA4A0D50","result_code":"SUCCESS","prepay_id":"wx221520364672093fca904b5d1308980100","trade_type":"JSAPI"}}
     * @apiSuccess (返回参数说明) {String} data 前端支付所需数据
     */
    public function getPreOrder()
    {
        $order_id = Request::param('order_id');
        $info = (new WalletService())->getPreOrder($order_id);
        return json(new SuccessMessageWithData(['data' => $info]));

    }


    public function WXNotifyUrl()
    {
        $app = app('wechat.payment');
        $response = $app->handlePaidNotify(function ($message, $fail) {
            $order_num = $message['out_trade_no'];
            $order = PayT::where('order_num', $order_num)->find();

            if (!$order || $order->status == 'paid') {
                return true;
            }
            if ($message['return_code'] === 'SUCCESS') { // return_code 表示通信状态，不代表支付状态
                // 用户是否支付成功
                if ($message['result_code'] === 'SUCCESS') {
                    //保存支付记录
                    $data = [
                        'out_trade_no' => $message['out_trade_no'],
                        'openid' => $message['openid'],
                        'total_fee' => $message['total_fee'],
                        'transaction_id' => $message['transaction_id']
                    ];
                    PayWxT::create($data);
                    $order->paid_at = time(); // 更新支付时间为当前时间
                    $order->status = 'paid';
                    //修改订餐订单状态
                    $orderType = $order->order_type;
                    if ($orderType == "pre") {
                        (new WalletService())->paySuccessForPre($order->prepare_id, $order->type, $order->times);
                    } else {
                        (new WalletService())->paySuccess($order->order_id, $order->type, $order->times);

                    }

                } elseif ($message['result_code'] === 'FAIL') {
                    // 用户支付失败
                    $order->status = 'paid_fail';
                }
            } else {
                return $fail('通信失败，请稍后再通知我');
            }

            $order->save(); // 保存订单
            return true; // 返回处理完成
        });

        $response->send();
    }

    /**
     * @api {GET} /api/v1/wallet/pay/nonghang/link  微信端-农行支付-获取支付链接
     * @apiGroup  Official
     * @apiVersion 1.0.1
     * @apiDescription  微信端-农行支付-获取支付链接
     * @apiExample {get}  请求样例:
     * http://mengant.cn/api/v1/wallet/pay/nonghang/link
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"url":"https:\/\/enjoy.abchina.com\/jf-openweb\/wechat\/shareEpayItem?code=JF-EPAY2019062401722"}}     * @apiSuccess (返回参数说明) {String} data 前端支付所需数据
     * @apiSuccess (返回参数说明) {String} url  支付链接
     */
    public function payLink()
    {
        $data = (new WalletService())->payLink();
        return json(new SuccessMessageWithData(['data' => $data]));
    }


    /*www.canteen1.com/api/v1/recharge/monthRechargeMoney?consumption_time=2020-05&phone=13630434754&company_id=95
   *{"msg":"ok","errorCode":0,"code":200,"data":1000.1}
   * （请求参数）{string}consumption_time消费日期
   * (返回参数）{int}data月充值总金额
   */
    //月充值合计
    public function monthRechargeMoney()
    {
        $consumption_time = Request::param('consumption_time');
        $staffId = \app\api\service\Token::getCurrentTokenVar('staff_id');
        $rechargeMoney = (new WalletService())->monthRechargeMoney($staffId, $consumption_time);
        return json(new SuccessMessageWithData(['data' => $rechargeMoney]));

    }

    /**
     * @api {GET} /api/v1/wallet/rechargeStatistic CMS管理端-充值管理-充值记录统计
     * @apiGroup  CMS管理端
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-充值管理-充值记录统计
     * @apiExample {get}  请求样例:
     * http://www.canteen1.com/api/v1/wallet/rechargeStatistic?begin_time=2020-01-01&end_time=2020-01-30&department_id=0&username=李君凤&page=1&size=10
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {string} begin_time  查询开始时间
     * @apiParam (请求参数说明) {string} end_time  查询结束时间
     * @apiParam (请求参数说明) {string} department_id  部门：传0代表全部
     * @apiParam (请求参数说明) {string} username  员工姓名
     * @apiParam (请求参数说明) {string} phone  员工手机号
     * @apiParam (请求参数说明) {string} company_id  企业id
     * @apiSuccessExample {json}返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":4,"per_page":"10","current_page":1,"last_page":1,"data":[{"department":"饭堂维护","username":"10","phone":"","money":"153.95","time":"2021-04-01-2021-04-30"},{"department":"饭堂维护","username":"黎灿嫦","phone":"","money":"12.03","time":"2021-04-01-2021-04-30"},{"department":"饭堂维护","username":"美东","phone":"","money":"101.00","time":"2021-04-01-2021-04-30"},{"department":"饭堂维护","username":"林志强","phone":"","money":"224.01","time":"2021-04-01-2021-04-30"}]}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {string} department 部门
     * @apiSuccess (返回参数说明) {string} username 姓名
     * @apiSuccess (返回参数说明) {string} phone 电话号码
     * @apiSuccess (返回参数说明) {string} money 充值金额统计
     * @apiSuccess (返回参数说明) {string} time 时间段
     */
    public function rechargeStatistic($page = 1, $size = 20)
    {
        //开始时间
        $begin_time = Request::param('begin_time');
        //结束时间
        $end_time = Request::param('end_time');
        //部门
        $department_id = Request::param('department_id');
        //姓名
        $username = Request::param('username');
        //电话号码
        $phone = Request::param('phone');
        //企业id
        $company_id = \app\api\service\Token::getCurrentTokenVar('company_id');
        $records = RechargeCashT::rechargeTotal($page, $size, $begin_time, $end_time, $username, $department_id, $phone, $company_id);
        return json(new SuccessMessageWithData(['data' => $records]));

    }

    /**
     * @api {GET} /api/v1/rechargeStatistic/export CMS管理端-充值管理-充值记录统计-导出报表
     * @apiGroup  CMS管理端
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-充值管理-充值记录统计-导出报表
     * @apiExample {get}  请求样例:
     * http://www.cnt.com/api/v1/rechargeStatistic/export?begin_time=2021-01-01&end_time=2021-01-30&department_id=0
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {string} begin_time  查询开始时间
     * @apiParam (请求参数说明) {string} end_time  查询结束时间
     * @apiParam (请求参数说明) {string} department  部门：传0代表全部,具体为部门id
     * @apiParam (请求参数说明) {string} username  员工姓名
     * @apiParam (请求参数说明) {string} phone  员工手机号
     * @apiSuccessExample {json}返回样例:
     * {"msg":"下载 excel失败","errorCode":40001}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     */
    public function exportRechargeStatistic()
    {
        //开始时间
        $begin_time = Request::param('begin_time');
        //结束时间
        $end_time = Request::param('end_time');
        //部门
        $department = Request::param('department');
        //姓名
        $username = Request::param('username');
        //电话号码
        $phone = Request::param('phone');
        (new \app\api\service\v2\DownExcelService())->exportRechargeTotal($begin_time, $end_time, $username, $department, $phone);
        return json(new SuccessMessage());

    }


}