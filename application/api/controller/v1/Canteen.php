<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\model\ConsumptionStrategyT;
use app\api\model\DinnerT;
use app\api\service\CanteenService;
use app\api\service\Token;
use app\lib\enum\CommonEnum;
use app\lib\exception\DeleteException;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use app\lib\exception\UpdateException;
use think\facade\Request;

class Canteen extends BaseController
{
    /**
     * @api {POST} /api/v1/canteen/save CMS管理端-新增饭堂
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription     CMS管理端-新增饭堂
     * @apiExample {post}  请求样例:
     *    {
     *       "canteens":"饭堂1号",
     *       "c_id": 2
     *     }
     * @apiParam (请求参数说明) {string} canteens  饭堂名称json字符串
     * @apiParam (请求参数说明) {int} c_id  企业id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function save()
    {
        $params = $this->request->param();
        $canteen_id = (new CanteenService())->save($params);
        return json(new SuccessMessageWithData(['data' => ['canteen_id' => $canteen_id]]));
    }

    /**
     * @api {POST} /api/v1/canteen/configuration/save CMS管理端-新增饭堂配置信息
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription     CMS管理端-新增饭堂配置信息
     * @apiExample {post}  请求样例:
     * {"c_id":2,"dinners":[{"name":"早餐","type":"day","type_number":10,"limit_time":"10:00","meal_time_begin":"07:00","meal_time_end":"08:00","fixed":2},{"name":"中餐","type":"day","type_number":10,"limit_time":"10:00","meal_time_bgin":"12:00","meal_time_end":"13:00","fixed":1}],"account":{"dining_mode":3,"out_dining_mode":3,"type":2,"confirm":2,"clean_type":3,"clean_day":1,"clean_time":"01:00:00","limit_money":0,"out":2,"reception":2},"out_config":{"in_fee":0,"out_fee":0,"address_limit":1,"remark":"备注"},"address":[{"province":"广东省","city":"江门市","area":"蓬江区","address":"人民医院A栋"}],"reception_config":{"approval":1,"single":2,"money":"10,20"}}
     * @apiParam (请求参数说明) {int} c_id  饭堂id
     * @apiParam (请求参数说明) {string} dinners  订餐信息json字符串
     * @apiParam (请求参数说明) {string} name  餐次名称
     * @apiParam (请求参数说明) {string} type  时间设置类别：day|week；1、前n天是填写数字，说明每天的餐需要提前一个天数来订餐;2、周，是只能填写周一到周日，说明一周的订餐规定需要在每周某天进行下周一整周的订餐
     * @apiParam (请求参数说明) {int} type_number 订餐时间类别对应数量 （week：0-6；周日-周六）
     * @apiParam (请求参数说明) {string} limit_time  订餐限制时间
     * @apiParam (请求参数说明) {string} meal_time_begin  就餐开始时间
     * @apiParam (请求参数说明) {string} meal_time_end  就餐截止时间
     * @apiParam (请求参数说明) {int} fixed  餐次是否采用标准金额：1｜是；2｜否
     * @apiParam (请求参数说明) {obj} account 饭堂账户设置
     * @apiParam (请求参数说明) {int} dining_mode  个人选菜就餐方式：1｜食堂；2｜外卖；3｜全部
     * @apiParam (请求参数说明) {int} out_dining_mode  外来人员个人选菜就餐方式：1｜食堂；2｜外卖；3｜全部
     * @apiParam (请求参数说明) {int} type  消费类别：1| 可透支消费；2|不可透支消费
     * @apiParam (请求参数说明) {int} confirm  是否开通微信端确认就餐功能：1｜ 是；2｜否
     * @apiParam (请求参数说明) {int} reception  是否开通接待票：1｜ 是；2｜否
     * @apiParam (请求参数说明) {int} clean_type  系统清零方式：1|系统自动清零；2|系统自动清零；3|无
     * @apiParam (请求参数说明) {int} clean_day  每月清零具体日期
     * @apiParam (请求参数说明) {int} clean_time  每月清零具体时间
     * @apiParam (请求参数说明) {int} limit_money  可预消费金额
     * @apiParam (请求参数说明) {int} out  是否允许非企业人员就餐：1| 允许；2|不允许
     * @apiParam (请求参数说明) {obj} out_config  外卖配置
     * @apiParam (请求参数说明) {int} in_fee  企业人员配送费用
     * @apiParam (请求参数说明) {int} out_fee  外来人员配送费用
     * @apiParam (请求参数说明) {int} address_limit  是否限制配送范围
     * @apiParam (请求参数说明) {string} remark  可预消费金额
     * @apiParam (请求参数说明) {obj} address  限制配送范围
     * @apiParam (请求参数说明) {string} province 省
     * @apiParam (请求参数说明) {string} city  城市
     * @apiParam (请求参数说明) {string} area  区域
     * @apiParam (请求参数说明) {string} address  详细地址
     * @apiParam (请求参数说明) {obj} reception_config  接待票配置
     * @apiParam (请求参数说明) {int} approval  是否需要审批：1 ｜是；2｜否
     * @apiParam (请求参数说明) {int} single  是否单个现金
     * @apiParam (请求参数说明) {string} money  金额：多个金额时用逗号分隔：10,20
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function saveConfiguration()
    {
        $params = $this->request->param();
        (new CanteenService())->saveConfiguration($params);
        return json(new SuccessMessage());
    }

    /**
     * @api {GET} /api/v1/canteen/configuration  CMS管理端-获取饭堂设置信息
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription  CMS管理端-获取饭堂设置信息
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/canteen/configuration?c_id=2
     * @apiParam (请求参数说明) {int} c_id  饭堂id
     * @apiSuccessExample {json} 系统功能模块返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"dinners":[{"id":5,"c_id":1,"name":"早餐","type":"day","create_time":"2019-07-30 02:07:17","type_number":10,"meal_time_bgin":"07:00:00","meal_time_end":"08:00:00","limit_time":"10:00:00"},{"id":6,"c_id":1,"name":"中餐","type":"day","create_time":"2019-07-30 02:07:17","type_number":10,"meal_time_bgin":"12:00:00","meal_time_end":"13:00:00","limit_time":"10:00:00"}],"account":{"id":3,"dining_mode":3,"out_dining_mode":3,"c_id":1,"type":2,"clean_type":3,"clean_day":0,"create_time":"2019-07-30 02:07:17","out":1,"confirm":1,"reception":1},"out_config":{"id":1,"in_fee":0,"out_fee":0,"address_limit":1,"remark":"备注"},"reception_config":{"id":1,"approval":1,"single":2,"money":"10,20"},"address":[{"id":1,"province":"广东省","city":"江门市","area":"蓬江区","address":"人民医院A栋"}]}}     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {string} dinners  订餐信息json字符串
     * @apiSuccess (返回参数说明) {string} id  餐次id
     * @apiSuccess (返回参数说明) {string} name  餐次名称
     * @apiSuccess (返回参数说明) {string} type  时间设置类别：day|week
     * @apiSuccess (返回参数说明) {int} fixed  餐次是否采用标准金额：1｜是；2｜否
     * @apiSuccess (返回参数说明) {string} create_time  创建时间
     * @apiSuccess (返回参数说明) {int} type_number 订餐时间类别对应数量 （week：0-6；周日-周六）
     * @apiSuccess (返回参数说明) {string} limit_time  订餐限制时间
     * @apiSuccess (返回参数说明) {string} meal_time_begin  就餐开始时间
     * @apiSuccess (返回参数说明) {string} meal_time_end  就餐截止时间
     * @apiSuccess (返回参数说明) {string} account 账户设置
     * @apiSuccess (返回参数说明) {int} account|id  设置id
     * @apiSuccess (返回参数说明) {int} dining_mode  个人选菜就餐方式：1｜食堂；2｜外卖；3｜全部
     * @apiSuccess (返回参数说明) {int} out_dining_mode  外来人员个人选菜就餐方式：1｜食堂；2｜外卖；3｜全部
     * @apiSuccess (返回参数说明) {int} type  消费类别：1| 可透支消费；2|不可透支消费
     * @apiSuccess (返回参数说明) {int} confirm  是否开通微信端确认就餐功能：1｜ 是；2｜否
     * @apiSuccess (返回参数说明) {int} clean_type  系统清零方式：1|系统自动清零；2|系统自动清零；3|无
     * @apiSuccess (返回参数说明) {int} clean_day  每月清零具体日期
     * @apiSuccess (返回参数说明) {int} clean_time  每月清零具体时间
     * @apiSuccess (返回参数说明) {int} create_time  创建时间
     * @apiSuccess (返回参数说明) {int} out  是否允许非企业人员就餐：1| 允许；2|不允许
     * @apiSuccess (返回参数说明) {obj} out_config  外卖配置
     * @apiSuccess (返回参数说明) {int} id  配置id
     * @apiSuccess (返回参数说明) {int} in_fee  企业人员配送费用
     * @apiSuccess (返回参数说明) {int} out_fee  外来人员配送费用
     * @apiSuccess (返回参数说明) {int} address_limit  是否限制配送范围
     * @apiSuccess (返回参数说明) {string} remark  可预消费金额
     * @apiSuccess (返回参数说明) {obj} address  限制配送范围
     * @apiSuccess (返回参数说明) {string} id 地址id
     * @apiSuccess (返回参数说明) {string} province 省
     * @apiSuccess (返回参数说明) {string} city  城市
     * @apiSuccess (返回参数说明) {string} area  区域
     * @apiSuccess (返回参数说明) {string} address  详细地址
     * @apiSuccess (返回参数说明) {obj} reception_config  接待票配置
     * @apiSuccess (返回参数说明) {int} id  配置id
     * @apiSuccess (返回参数说明) {int} approval  是否需要审批：1 ｜是；2｜否
     * @apiSuccess (返回参数说明) {int} single  是否单个现金
     * @apiSuccess (返回参数说明) {string} money  金额：多个金额时用逗号分隔：10,20
     */
    public function configuration()
    {
        $c_id = $this->request->param('c_id');
        $info = (new CanteenService())->configuration($c_id);
        return json(new SuccessMessageWithData(['data' => $info]));
    }

    /**
     * @api {POST} /api/v1/canteen/configuration/update CMS管理端-更新饭堂配置信息
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-更新饭堂配置信息
     * @apiExample {post}  请求样例:
     * {"c_id":2,"dinners":[{"id":1,"name":"早餐","type":"day","type_number":10,"limit_time":"10:00","meal_time_begin":"07:00","meal_time_end":"08:00"},{"name":"晚餐","type":"day","type_number":10,"limit_time":"10:00","meal_time_begin":"18:00","meal_time_end":"19:00"}],"account":{"id":1,"type":2,"confirm":1,"dining_mode":2,"out_dining_mode":2,"clean_type":3,"clean_day":"02:00:00"},"out_config":{"id":1,"in_fee":0,"out_fee":0,"address_limit":1,"remark":"备注","state":2},"address":{"add":[{"id":1,"province":"广东省","city":"江门市","area":"蓬江区","address":"人民医院A栋"}],"cancel":[1,2]},"reception_config":{"id":1,"approval":1,"single":2,"money":"10,20"}}
     * @apiParam (请求参数说明) {int} c_id  饭堂id
     * @apiParam (请求参数说明) {string} dinners  订餐信息json字符串
     * @apiParam (请求参数说明) {string} id  餐次设置id，更新操作需要传如此字段
     * @apiParam (请求参数说明) {string} name  餐次名称
     * @apiParam (请求参数说明) {string} type  时间设置类别：day|week (天、周)
     * @apiParam (请求参数说明) {int} type_number 订餐时间类别对应数量    1、前n天是填写数字，说明每天的餐需要提前一个天数来订餐2、周，是只能填写周一到周日，说明一周的订餐规定需要在每周某天进行下周一整周的订餐
     * @apiParam (请求参数说明) {string} limit_time  订餐限制时间
     * @apiParam (请求参数说明) {string} meal_time_begin  就餐开始时间
     * @apiParam (请求参数说明) {string} meal_time_end  就餐截止时间
     * @apiParam (请求参数说明) {int} fixed  餐次是否采用标准金额：1｜是；2｜否
     * @apiParam (请求参数说明) {string} account 饭堂账户设置
     * @apiParam (请求参数说明) {int} id  饭堂账户设置ID
     * @apiParam (请求参数说明) {int} dining_mode  个人选菜就餐方式：1｜食堂；2｜外卖；3｜全部
     * @apiParam (请求参数说明) {int} out_dining_mode  外来人员个人选菜就餐方式：1｜食堂；2｜外卖；3｜全部
     * @apiParam (请求参数说明) {int} type  消费类别：1| 可透支消费；2|不可透支消费
     * @apiParam (请求参数说明) {int} confirm  是否开通微信端确认就餐功能：1｜ 是；2｜否
     * @apiParam (请求参数说明) {int} clean_type  系统清零方式：1|系统自动清零；2|系统自动清零；3|无
     * @apiParam (请求参数说明) {int} clean_day  每月清零具体日期
     * @apiParam (请求参数说明) {int} clean_time  每月清零具体时间
     * @apiParam (请求参数说明) {obj} out_config  外卖配置
     * @apiParam (请求参数说明) {int} id  配置id 首次新增无需上传
     * @apiParam (请求参数说明) {int} in_fee  企业人员配送费用
     * @apiParam (请求参数说明) {int} out_fee  外来人员配送费用
     * @apiParam (请求参数说明) {int} address_limit  是否限制配送范围
     * @apiParam (请求参数说明) {int} state  状态：1：正常；2 ：停用
     * @apiParam (请求参数说明) {string} remark  可预消费金额
     * @apiParam (请求参数说明) {obj} address  限制配送范围
     * @apiParam (请求参数说明) {obj} add 新增地址
     * @apiParam (请求参数说明) {string} id 地址id
     * @apiParam (请求参数说明) {string} province 省
     * @apiParam (请求参数说明) {string} city  城市
     * @apiParam (请求参数说明) {string} area  区域
     * @apiParam (请求参数说明) {string} address  详细地址
     * @apiParam (请求参数说明) {obj} cancel  取消地址
     * @apiParam (请求参数说明) {obj} reception_config  接待票配置
     * @apiParam (请求参数说明) {int} id 接待票配置id
     * @apiParam (请求参数说明) {int} approval  是否需要审批：1 ｜是；2｜否
     * @apiParam (请求参数说明) {int} single  是否单个现金
     * @apiParam (请求参数说明) {string} money  金额：多个金额时用逗号分隔：10,20
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function updateConfiguration()
    {
        $params = $this->request->param();
        (new CanteenService())->updateConfiguration($params);
        return json(new SuccessMessage());
    }

    /**
     * @api {POST} /api/v1/canteen/consumptionStrategy/save CMS管理端-新增消费策略
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-新增消费策略
     * @apiExample {post}  请求样例:
     *    {
     *       "c_id": ,
     *       "t_id": 2,
     *       "unordered_meals": 1
     *       "consumption_type": 1
     *     }
     * @apiParam (请求参数说明) {int} c_id 饭堂id
     * @apiParam (请求参数说明) {int} t_id  人员类型id
     * @apiParam (请求参数说明) {int} unordered_meals  是否未订餐允许就餐：1|是；2|否
     * @apiParam (请求参数说明) {int} consumption_type  打卡方式：1|一次性打开方式；2|逐次打卡消费
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":14,"unordered_meals":1,"detail":"","consumption_count":1,"ordered_count":1,"dinner":{"id":5,"name":"早餐"},"role":{"id":1,"name":"局长"},"canteen":{"id":1,"name":"大饭堂"}},{"id":15,"unordered_meals":1,"detail":"","consumption_count":1,"ordered_count":1,"dinner":{"id":6,"name":"中餐"},"role":{"id":1,"name":"局长"},"canteen":{"id":1,"name":"大饭堂"}},{"id":16,"unordered_meals":1,"detail":"","consumption_count":1,"ordered_count":1,"dinner":{"id":7,"name":"晚餐"},"role":{"id":1,"name":"局长"},"canteen":{"id":1,"name":"大饭堂"}}]}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id 消费策略id
     * @apiSuccess (返回参数说明) {int} unordered_meals 是否未订餐允许就餐：1|是；2|否
     * @apiSuccess (返回参数说明) {int} consumption_count 允许消费次数
     * @apiSuccess (返回参数说明) {int} consumption_type 打卡方式：1|一次性打开方式；2|逐次打卡消费
     * @apiSuccess (返回参数说明) {int} ordered_count 订餐数量
     * @apiSuccess (返回参数说明) {string} detail 策略明细
     * @apiSuccess (返回参数说明) {obj} dinner 餐次信息
     * @apiSuccess (返回参数说明) {int} id 餐次id
     * @apiSuccess (返回参数说明) {string} name 餐次名称
     * @apiSuccess (返回参数说明) {obj} role 人员类型
     * @apiSuccess (返回参数说明) {int} id 人员类型id
     * @apiSuccess (返回参数说明) {string} name 人员类型名称
     * @apiSuccess (返回参数说明) {obj} canteen 饭堂信息
     * @apiSuccess (返回参数说明) {int} id 饭堂id
     * @apiSuccess (返回参数说明) {string} name 饭堂名称
     */
    public function saveConsumptionStrategy()
    {
        $params = Request::param();
        $strategies = (new CanteenService())->saveConsumptionStrategy($params);
        return json(new SuccessMessageWithData(['data' => $strategies]));
    }

    /**
     * @api {POST} /api/v1/canteen/consumptionStrategy/update CMS管理端-修改消费策略
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-修改消费策略
     * @apiExample {post}  请求样例:
     *    {
     *       "id":1 ,
     *       "unordered_meals": 1,
     *       "consumption_count": 1,
     *       "consumption_type": 1,
     *       "ordered_count": 1,
     *       "detail":[{"number":1,"strategy":[{"status":"ordering_meals","money":10,"sub_money":5},{"status":"no_meals_ordered","money":10,"sub_money":5},{"status":"unordered_meals","money":10,"sub_money":5}]},{"number":2,"strategy":[{"status":"ordering_meals","money":10,"sub_money":5},{"satus":"no_meals_ordered","money":10,"sub_money":5},{"status":"unordered_meals","money":10,"sub_money":5}]}],
     *     }
     * @apiParam (请求参数说明) {int} id 消费策略id
     * @apiParam (请求参数说明) {int} unordered_meals  是否未订餐允许就餐：1：是；2：否
     * @apiParam (请求参数说明) {int} consumption_count  允许消费次数
     * @apiParam (请求参数说明) {int} consumption_type  打卡方式：1：一次性打开方式；2：逐次打卡消费
     * @apiParam (请求参数说明) {int} ordered_count  订餐数量
     * @apiParam (请求参数说明) {int} detail  策略明细
     * @apiParam (请求参数说明) {int} number  次数类型
     * @apiParam (请求参数说明) {string} strategy  餐次策略明细
     * @apiParam (请求参数说明) {string} status  消费状态：ordering_meals：订餐就餐；no_meals_ordered：订餐未就餐；unordered_meals：未订餐就餐
     * @apiParam (请求参数说明) {float} money 标准金额
     * @apiParam (请求参数说明) {float} sub_money  附加金额
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function updateConsumptionStrategy()
    {
        $params = Request::param();
        (new CanteenService())->updateConsumptionStrategy($params);

        return json(new SuccessMessage());
    }

    /**
     * @api {GET} /api/v1/canteen/consumptionStrategy  CMS管理端-获取饭堂消费策略设置
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription  CMS管理端-获取饭堂消费策略设置
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/canteen/consumptionStrategy?c_id=2
     * @apiParam (请求参数说明) {int} c_id  饭堂id
     * @apiSuccessExample {json} 系统功能模块返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":14,"unordered_meals":1,"detail":[{"number":1,"strategy":[{"satus":"ordering_meals","money":10,"sub_money":5},{"satus":"no_meals_ordered","money":10,"sub_money":5},{"satus":"unordered_meals","money":10,"sub_money":5}]},{"number":2,"strategy":[{"satus":"ordering_meals","money":10,"sub_money":5},{"satus":"no_meals_ordered","money":10,"sub_money":5},{"satus":"unordered_meals","money":10,"sub_money":5}]}],"consumption_count":1,"ordered_count":1,"dinner":{"id":5,"name":"早餐"},"role":{"id":1,"name":"局长"},"canteen":{"id":1,"name":"大饭堂"}},{"id":15,"unordered_meals":1,"detail":null,"consumption_count":1,"ordered_count":1,"dinner":{"id":6,"name":"中餐"},"role":{"id":1,"name":"局长"},"canteen":{"id":1,"name":"大饭堂"}},{"id":16,"unordered_meals":1,"detail":null,"consumption_count":1,"ordered_count":1,"dinner":{"id":7,"name":"晚餐"},"role":{"id":1,"name":"局长"},"canteen":{"id":1,"name":"大饭堂"}}]}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id 消费策略id
     * @apiSuccess (返回参数说明) {int} unordered_meals 是否未订餐允许就餐：1：是；2：否
     * @apiSuccess (返回参数说明) {int} consumption_count 允许消费次数
     * @apiSuccess (返回参数说明) {int} consumption_type  打卡方式：1：一次性打开方式；2：逐次打卡消费
     * @apiSuccess (返回参数说明) {int} ordered_count 订餐数量
     * @apiSuccess (返回参数说明) {string} detail 策略明细
     * @apiSuccess (返回参数说明) {obj} dinner 餐次信息
     * @apiSuccess (返回参数说明) {int} id 餐次id
     * @apiSuccess (返回参数说明) {string} name 餐次名称
     * @apiSuccess (返回参数说明) {string} fixed  策略类型：1； 固定；2： 动态
     * @apiSuccess (返回参数说明) {obj} role 人员类型
     * @apiSuccess (返回参数说明) {int} id 人员类型id
     * @apiSuccess (返回参数说明) {string}  name 人员类型名称
     * @apiSuccess (返回参数说明) {obj} canteen 饭堂信息
     * @apiSuccess (返回参数说明) {int}  id 饭堂id
     * @apiSuccess (返回参数说明) {string}  name 饭堂名称
     * @apiSuccess (返回参数说明) {int} detail  策略明细
     * @apiSuccess (返回参数说明) {int}  number  次数类型
     * @apiSuccess (返回参数说明) {string}  strategy  餐次策略明细
     * @apiSuccess (返回参数说明) {string}  status  消费状态：ordering_meals|订餐就餐；no_meals_ordered|订餐未就餐；unordered_meals|未订餐就餐
     * @apiSuccess (返回参数说明) {float}  money 标准金额
     * @apiSuccess (返回参数说明) {float}  sub_money  附加金额
     */
    public function consumptionStrategy()
    {
        $c_id = Request::param('c_id');
        $strategies = (new CanteenService())->consumptionStrategy($c_id);
        return json(new SuccessMessageWithData(['data' => $strategies]));
    }

    /**
     * @api {GET} /api/v1/canteens/role CMS管理端-获取用户可查看饭堂信息（企业管理端）
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-获取用户可查看饭堂信息
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/canteens/role
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":3,"name":"企业A","parent_id":2,"canteen":[{"id":6,"c_id":3,"name":"饭堂1"},{"id":7,"c_id":3,"name":"饭堂2"}]},{"id":4,"name":"企业A1","parent_id":3,"canteen":[]},{"id":5,"name":"企业A2","parent_id":3,"canteen":[]},{"id":6,"name":"企业A11","parent_id":4,"canteen":[]}]}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id 企业id
     * @apiSuccess (返回参数说明) {String} name  企业名称
     * @apiSuccess (返回参数说明) {obj} canteen 企业饭堂信息
     * @apiSuccess (返回参数说明) {int} canteen|id 饭堂id
     * @apiSuccess (返回参数说明) {int} canteen|name 饭堂名称
     */
    public function roleCanteens()
    {
        $canteens = (new CanteenService())->roleCanteens();
        return json(new SuccessMessageWithData(['data' => $canteens]));

    }

    /**
     * @api {GET} /api/v1/canteen/dinners/user 微信端--个人选菜-用户所选择饭堂可选择餐次
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription  微信端--个人选菜-用户所选择饭堂可选择餐次(用户订餐时需要检测当前时间段是否为订餐时间)
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/canteen/dinners/user
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":5,"c_id":6,"name":"早餐","type":"day","create_time":"2019-07-30 02:07:17","type_number":10,"meal_time_begin":"07:00:00","meal_time_end":"08:00:00","limit_time":"09:00:00"},{"id":6,"c_id":6,"name":"中餐","type":"day","create_time":"2019-07-30 02:07:17","type_number":10,"meal_time_begin":"12:00:00","meal_time_end":"13:00:00","limit_time":"10:00:00"},{"id":7,"c_id":6,"name":"晚餐","type":"day","create_time":"2019-07-30 11:24:36","type_number":10,"meal_time_begin":"18:00:00","meal_time_end":"19:00:00","limit_time":"10:00:00"}]}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id  餐次id
     * @apiSuccess (返回参数说明) {string} name  餐次名称
     * @apiSuccess (返回参数说明) {string} type  时间设置类别：day|week|month（可提前订餐时间类别）
     * @apiSuccess (返回参数说明) {int} type_number 订餐时间类别对应数量
     * @apiSuccess (返回参数说明) {string} limit_time  订餐限制时间
     * @apiSuccess (返回参数说明) {string} meal_time_begin  就餐开始时间
     * @apiSuccess (返回参数说明) {string} meal_time_end  就餐截止时间
     */
    public function currentCanteenDinners()
    {
        $canteen_id = Token::getCurrentTokenVar('current_canteen_id');
        $dinners = (new CanteenService())->getDinners($canteen_id);
        return json(new SuccessMessageWithData(['data' => $dinners]));

    }

    /**
     * @api {GET} /api/v1/canteen/dinners CMS管理端--选择饭堂查看餐次列表
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription  CMS管理端--选择饭堂查看餐次列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/canteen/dinners?canteen_id=3
     * @apiParam (请求参数说明) {int} canteen_id  饭堂id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":5,"name":"早餐"},{"id":6,"name":"中餐"},{"id":7,"name":"晚餐"}]}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id  餐次id
     * @apiSuccess (返回参数说明) {string} name  餐次名称
     */
    public function canteenDinners()
    {
        $canteen_id = Request::param('canteen_id');
        $dinners = (new CanteenService())->getDinnerNames($canteen_id);
        return json(new SuccessMessageWithData(['data' => $dinners]));

    }

    /**
     * @api {POST} /api/v1/canteen/saveComment  微信端--个人选菜--评价饭堂
     * @apiGroup   Official
     * @apiVersion 3.0.0
     * @apiDescription    微信端--个人选菜--评价饭堂
     * @apiExample {post}  请求样例:
     *    {
     *       "taste": 5,
     *       "service": 5
     *     }
     * @apiParam (请求参数说明) {int} taste  味道评分：1-5分
     * @apiParam (请求参数说明) {int} service  服务评分：1-5分
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function saveComment()
    {
        $params = Request::param();
        (new CanteenService())->saveComment($params);
        return json(new SuccessMessage());
    }

    /**
     * @api {POST} /api/v1/canteen/saveMachine CMS管理端-企业管理-添加硬件(饭堂/小卖部)
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription     CMS管理端-企业管理-添加饭堂硬件
     * @apiExample {post}  请求样例:
     *    {
     *       "company_id": 3,
     *       "belong_id": 6,
     *       "machine_type": "canteen",
     *       "name": "1号设备",
     *       "face_id": 12,
     *       "number": "001",
     *       "code": "dadas12121",
     *       "pwd": "a111",
     *       "out": 1,
     *       "sort_code": 1
     *     }
     * @apiParam (请求参数说明) {string} name  设备名称
     * @apiParam (请求参数说明) {int} company_id  企业id
     * @apiParam (请求参数说明) {int} belong_id  设备归属id
     * @apiParam (请求参数说明) {int} face_id  消费机关联人脸识别机的id
     * @apiParam (请求参数说明) {string} number  编号
     * @apiParam (请求参数说明) {string} machine_type  设备类别 canteen:饭堂id；shop：小卖部id
     * @apiParam (请求参数说明) {string} code  设备号
     * @apiParam (请求参数说明) {string} pwd  设备登陆密码
     * @apiParam (请求参数说明) {int} out  设备使用类别：1：外部食堂；2 ：内部食堂;3 无
     * @apiParam (请求参数说明) {int} sort_code  是否接收排队序列 1： 接收；2 ： 不接收
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function saveMachine()
    {
        $params = Request::param();
        (new CanteenService())->saveMachine($params);
        return json(new SuccessMessage());
    }

    /**
     * @api {POST} /api/v1/canteen/deleteMachine  CMS管理端-企业明细-删除设备
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-企业明细-删除设备
     * @apiExample {post}  请求样例:
     *    {
     *       "id": "1"
     *     }
     * @apiParam (请求参数说明) {int} id  设备id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function deleteMachine()
    {
        $id = Request::param('id');
        (new CanteenService())->deleteMachine($id);
        return json(new SuccessMessage());
    }

    /**
     * @api {POST} /api/v1/canteen/updateMachine CMS管理端-企业管理-修改硬件信息(饭堂和小卖部)
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-企业管理-修改饭堂硬件信息
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 1,
     *       "face_id": 1,
     *       "name": "1号设备",
     *       "number": "001",
     *       "code": "dadas12121",
     *       "pwd": "a111",
     *       "out": 1,
     *       "sort_code": 1
     *     }
     * @apiParam (请求参数说明) {int} id  设备id
     * @apiParam (请求参数说明) {int} face_id  消费机关联人脸识别机的id
     * @apiParam (请求参数说明) {string} name  设备名称
     * @apiParam (请求参数说明) {string} number  编号
     * @apiParam (请求参数说明) {string} code  设备号
     * @apiParam (请求参数说明) {string} pwd  设备登陆密码
     * @apiParam (请求参数说明) {int} out  设备使用类别：1：外部食堂；2 ：内部食堂;3 无
     * @apiParam (请求参数说明) {int} sort_code  是否接收排队序列 1： 接收；2 ： 不接收
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function updateMachine()
    {
        $params = Request::param();
        (new CanteenService())->updateMachine($params);
        return json(new SuccessMessage());
    }

    /**
     * @api {GET} /api/v1/canteens/company CMS管理端--企业明细-查看企业饭堂信息
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端--企业明细-查看企业饭堂信息
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/canteens/company?company_id=3
     * @apiParam (请求参数说明) {int} company_id  企业id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"staffs":331,"canteens":[{"id":6,"c_id":3,"name":"饭堂1"},{"id":7,"c_id":3,"name":"饭堂2"},{"id":17,"c_id":3,"name":"newCanteen"},{"id":41,"c_id":3,"name":"开饭啦"}]}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述id
     * @apiSuccess (返回参数说明) {int} staffs  企业人数
     * @apiSuccess (返回参数说明) {obj} canteens  饭堂信息
     * @apiSuccess (返回参数说明) {string} id  饭堂
     * @apiSuccess (返回参数说明) {string} name  饭堂名称
     */
    public function getCanteensForCompany()
    {
        $company_id = Request::param('company_id');
        $canteens = (new CanteenService())->getCanteensForCompany($company_id);
        return json(new SuccessMessageWithData(['data' => $canteens]));
    }

    /**
     * @api {GET} /api/v1/canteens CMS管理端--查询类接口-获取企业下饭堂列表
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端--查询类接口-获取企业下饭堂列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/canteens?company_id=1
     * @apiParam (请求参数说明) {int} company_id  企业id,无企业列表传入0
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":6,"name":"饭堂1"},{"id":7,"name":"饭堂2"}]}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id  饭堂ID
     * @apiSuccess (返回参数说明) {obj} name  饭堂名称
     */
    public function canteens()
    {
        $company_id = Request::param('company_id');
        $canteens = (new CanteenService())->companyCanteens($company_id);
        return json(new SuccessMessageWithData(['data' => $canteens]));
    }

    /**
     * @api {GET} /api/v1/consumption/place CMS管理端--获取企业下消费地点列表（饭堂/小卖部）
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端--获取企业下消费地点列表（饭堂/小卖部）
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/consumption/plac?company_id=1
     * @apiParam (请求参数说明) {int} company_id  企业id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":6,"name":"饭堂1","type":"canteen"},{"id":7,"name":"小卖部","type":"shop"}]}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id  消费地点id
     * @apiSuccess (返回参数说明) {obj} name  消费地点名称
     * @apiSuccess (返回参数说明) {obj} type  消费地点类别：canteen：饭堂；shop:小卖部
     */
    public function consumptionPlace()
    {
        $company_id = Request::param('company_id');
        $canteens = (new CanteenService())->consumptionPlace($company_id);
        return json(new SuccessMessageWithData(['data' => $canteens]));
    }


    /**
     * @api {GET} /api/v1/managerCanteens  微信端--总订单查询--获取当前角色可管理饭堂列表
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription  微信端--总订单查询--获取当前角色可管理饭堂列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/managerCanteens
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":5,"name":"饭堂2"},{"id":1,"name":"大饭堂"},{"id":6,"name":"饭堂1"},{"id":7,"name":"饭堂2"}]}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id  饭堂ID
     * @apiSuccess (返回参数说明) {string} name  饭堂名称
     */
    public function managerCanteens()
    {
        $canteens = (new CanteenService())->managerCanteens();
        return json(new SuccessMessageWithData(['data' => $canteens]));
    }

    /**
     * @api {GET} /api/v1/canteen/diningMode  微信端--个人选菜--获取当前饭堂就餐地点设置
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription  微信端--个人选菜--获取当前饭堂就餐地点设置
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/canteen/diningMode
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"dining_mode":3}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {int} dining_mode  个人选菜就餐方式：1｜食堂；2｜外卖；3｜全部
     */
    public function diningMode()
    {
        $mode = (new CanteenService())->diningMode();
        return json(new SuccessMessageWithData(['data' => ['dining_mode' => $mode]]));
    }

    /**
     * @api {GET} /api/v1/machines CMS管理端-企业管理-获取设备列表（小卖部/饭堂）
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-企业管理-获取设备列表（小卖部/饭堂）
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/machines?belong_id=1&machine_type=canteen&page=1&size=20
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {int} belong_id  归属id：饭堂id/小卖部id（和machine_type一一对应）
     * @apiParam (请求参数说明) {int} machine_type 设备类别：canteen：饭堂；shop：小卖部
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":1,"per_page":"20","current_page":1,"last_page":1,"data":[{"id":2,"machine_type":"canteen","name":"刷卡器1号","code":"a111111","number":"001","state":1}]}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 设备id
     * @apiSuccess (返回参数说明) {string} machine_type 设备类别
     * @apiSuccess (返回参数说明) {string} number 设备序号
     * @apiSuccess (返回参数说明) {string} code 设备硬件号
     * @apiSuccess (返回参数说明) {string} name 设备硬件名称
     * @apiSuccess (返回参数说明) {int} face_id  消费机关联人脸识别机的id
     * @apiSuccess (返回参数说明) {int} out  设备使用类别：1：外部食堂；2 ：内部食堂;3 无
     * @apiSuccess (返回参数说明) {int} sort_code  是否接收排队序列 1： 接收；2 ： 不接收
     * @apiSuccess (返回参数说明) {int} state 状态：1|正常；2|异常
     */
    public function machines($page = 1, $size = 20)
    {
        $belong_id = Request::param('belong_id');
        $machine_type = Request::param('machine_type');
        $machines = (new CanteenService())->machines($belong_id, $machine_type, $page, $size);
        return json(new SuccessMessageWithData(['data' => $machines]));
    }

    /**
     * @api {GET} /api/v1/machines/company CMS管理端-企业明细-获取企业设备列表
     * @apiGroup  CMS
     * @apiVersion 3.0.0
     * @apiDescription CMS管理端-企业明细-获取企业设备列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/machines/company?company_id=1&page=1&size=20
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {int} company_id 企业id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":1,"per_page":"20","current_page":1,"last_page":1,"data":[{"id":2,"machine_type":"canteen","name":"刷卡器1号","code":"a111111","number":"001","state":1}]}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 设备id
     * @apiSuccess (返回参数说明) {string} machine_type 设备类别
     * @apiSuccess (返回参数说明) {string} number 设备序号
     * @apiSuccess (返回参数说明) {string} code 设备硬件号
     * @apiSuccess (返回参数说明) {string} name 设备硬件名称
     * @apiSuccess (返回参数说明) {int} face_id  消费机关联人脸识别机的id
     * @apiSuccess (返回参数说明) {int} state 状态：1|正常；2|异常
     */
    public function companyMachines($page = 1, $size = 20)
    {
        $company_id = Request::param('company_id');
        $machines = (new CanteenService())->companyMachines($company_id, $page, $size);
        return json(new SuccessMessageWithData(['data' => $machines]));
    }

    /**
     * @api {POST} /api/v1/canteen/dinner/delete  CMS管理端-删除饭堂餐次
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription     CMS管理端-删除饭堂餐次
     * @apiExample {post}  请求样例:
     *    {
     *       "dinner_id": "1"
     *     }
     * @apiParam (请求参数说明) {int} dinner_id  饭堂餐次id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function deleteDinner()
    {
        $id = Request::param('dinner_id');
        (new CanteenService())->deleteDinner($id);
        return json(new SuccessMessage());
    }

    /**
     * @api {GET} /api/v1/canteen/check/confirm  微信端-检测微信端确认消费是否开启
     * @apiGroup   Official
     * @apiVersion 3.0.0
     * @apiDescription    微信端-检测微信端确认消费是否开启
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/canteen/check/confirm
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"confirm":1}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {int} confirm 是否开启：1：开启；2：关闭
     */
    public function checkConfirm($canteen_id = 0)
    {
        $res = (new CanteenService())->checkConfirm($canteen_id);
        return json(new SuccessMessageWithData(['data' => $res]));
    }


}