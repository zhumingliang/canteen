<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\model\ConsumptionStrategyT;
use app\api\service\CanteenService;
use app\api\service\Token;
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
     *    {
     *       "c_id": 2,
     *       "dinners":[{"name":"早餐","type":"day","type_number":10,"limit_time":"10:00","meal_time_begin":"07:00","meal_time_end":"08:00","fixed":2},{"name":"中餐","type":"day","type_number":10,"limit_time":"10:00","meal_time_bgin":"12:00","meal_time_end":"13:00","fixed":1}],
     *       "account":{"type":2,"clean_type":3,"clean_day":1,"clean_time":01:00:00,"limit_money":0}
     *     }
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
     * @apiParam (请求参数说明) {int} type  消费类别：1| 可透支消费；2|不可透支消费
     * @apiParam (请求参数说明) {int} clean_type  系统清零方式：1|系统自动清零；2|系统自动清零；3|无
     * @apiParam (请求参数说明) {int} clean_day  每月清零具体日期
     * @apiParam (请求参数说明) {int} clean_time  每月清零具体时间
     * @apiParam (请求参数说明) {int} limit_money  可预消费金额
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
     * {"msg":"ok","errorCode":0,"code":200,"data":{"dinners":[{"id":5,"c_id":1,"name":"早餐","type":"day","create_time":"2019-07-30 02:07:17","type_number":10,"meal_time_bgin":"07:00:00","meal_time_end":"08:00:00","limit_time":"10:00:00"},{"id":6,"c_id":1,"name":"中餐","type":"day","create_time":"2019-07-30 02:07:17","type_number":10,"meal_time_bgin":"12:00:00","meal_time_end":"13:00:00","limit_time":"10:00:00"}],"account":{"id":3,"c_id":1,"type":2,"clean_type":3,"clean_day":0,"create_time":"2019-07-30 02:07:17"}}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {string} dinners  订餐信息json字符串
     * @apiSuccess (返回参数说明) {string} dinners|id  餐次id
     * @apiSuccess (返回参数说明) {string} dinners|name  餐次名称
     * @apiSuccess (返回参数说明) {string} dinners|type  时间设置类别：day|week
     * @apiSuccess (返回参数说明) {int} fixed  餐次是否采用标准金额：1｜是；2｜否
     * @apiSuccess (返回参数说明) {string} dinners|create_time  创建时间
     * @apiSuccess (返回参数说明) {int} dinners|type_number 订餐时间类别对应数量 （week：0-6；周日-周六）
     * @apiSuccess (返回参数说明) {string} dinners|limit_time  订餐限制时间
     * @apiSuccess (返回参数说明) {string} dinners|meal_time_begin  就餐开始时间
     * @apiSuccess (返回参数说明) {string} dinners|meal_time_end  就餐截止时间
     * @apiSuccess (返回参数说明) {string} account 账户设置
     * @apiSuccess (返回参数说明) {int} account|id  设置id
     * @apiSuccess (返回参数说明) {int} account|type  消费类别：1| 可透支消费；2|不可透支消费
     * @apiSuccess (返回参数说明) {int} account|clean_type  系统清零方式：1|系统自动清零；2|系统自动清零；3|无
     * @apiSuccess (返回参数说明) {int} account|clean_day  每月清零具体日期
     * @apiSuccess (返回参数说明) {int} account|clean_time  每月清零具体时间
     * @apiSuccess (返回参数说明) {int} account|create_time  创建时间
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
     *    {
     *       "c_id": 2,
     *       "dinners":[{"id":1,"name":"早餐","type":"day","type_number":10,"limit_time":"10:00","meal_time_begin":"07:00","meal_time_end":"08:00"},{"name":"晚餐","type":"day","type_number":10,"limit_time":"10:00","meal_time_begin":"18:00","meal_time_end":"19:00"}],
     *       "account":{"id":1,"type":2,"clean_type":3,"clean_day":1,"clean_day":02:00:00}
     *     }
     * @apiParam (请求参数说明) {int} c_id  饭堂id
     * @apiParam (请求参数说明) {string} dinners  订餐信息json字符串
     * @apiParam (请求参数说明) {string} dinners|id  餐次设置id，更新操作需要传如此字段
     * @apiParam (请求参数说明) {string} dinners|name  餐次名称
     * @apiParam (请求参数说明) {string} dinners|type  时间设置类别：day|week (天、周)
     * @apiParam (请求参数说明) {int} dinners|type_number 订餐时间类别对应数量    1、前n天是填写数字，说明每天的餐需要提前一个天数来订餐2、周，是只能填写周一到周日，说明一周的订餐规定需要在每周某天进行下周一整周的订餐
     * @apiParam (请求参数说明) {string} dinners|limit_time  订餐限制时间
     * @apiParam (请求参数说明) {string} dinners|meal_time_begin  就餐开始时间
     * @apiParam (请求参数说明) {string} dinners|meal_time_end  就餐截止时间
     * @apiParam (请求参数说明) {int} fixed  餐次是否采用标准金额：1｜是；2｜否
     * @apiParam (请求参数说明) {string} account 饭堂账户设置
     * @apiParam (请求参数说明) {int} account|id  饭堂账户设置ID
     * @apiParam (请求参数说明) {int} account|type  消费类别：1| 可透支消费；2|不可透支消费
     * @apiParam (请求参数说明) {int} account|clean_type  系统清零方式：1|系统自动清零；2|系统自动清零；3|无
     * @apiParam (请求参数说明) {int} account|clean_day  每月清零具体日期
     * @apiParam (请求参数说明) {int} account|clean_time  每月清零具体时间
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
     *     }
     * @apiParam (请求参数说明) {int} c_id 饭堂id
     * @apiParam (请求参数说明) {int} t_id  人员类型id
     * @apiParam (请求参数说明) {int} unordered_meals  是否未订餐允许就餐：1|是；2|否
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":14,"unordered_meals":1,"detail":"","consumption_count":1,"ordered_count":1,"dinner":{"id":5,"name":"早餐"},"role":{"id":1,"name":"局长"},"canteen":{"id":1,"name":"大饭堂"}},{"id":15,"unordered_meals":1,"detail":"","consumption_count":1,"ordered_count":1,"dinner":{"id":6,"name":"中餐"},"role":{"id":1,"name":"局长"},"canteen":{"id":1,"name":"大饭堂"}},{"id":16,"unordered_meals":1,"detail":"","consumption_count":1,"ordered_count":1,"dinner":{"id":7,"name":"晚餐"},"role":{"id":1,"name":"局长"},"canteen":{"id":1,"name":"大饭堂"}}]}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id 消费策略id
     * @apiSuccess (返回参数说明) {int} unordered_meals 是否未订餐允许就餐：1|是；2|否
     * @apiSuccess (返回参数说明) {int} consumption_count 允许消费次数
     * @apiSuccess (返回参数说明) {int} ordered_count 订餐数量
     * @apiSuccess (返回参数说明) {string} detail 策略明细
     * @apiSuccess (返回参数说明) {obj} dinner 餐次信息
     * @apiSuccess (返回参数说明) {int} dinner|id 餐次id
     * @apiSuccess (返回参数说明) {string} dinner|name 餐次名称
     * @apiSuccess (返回参数说明) {obj} role 人员类型
     * @apiSuccess (返回参数说明) {int} role|id 人员类型id
     * @apiSuccess (返回参数说明) {string} role|name 人员类型名称
     * @apiSuccess (返回参数说明) {obj} canteen 饭堂信息
     * @apiSuccess (返回参数说明) {int} canteen|id 饭堂id
     * @apiSuccess (返回参数说明) {string} canteen|name 饭堂名称
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
     *       "ordered_count": 1,
     *       "detail":[{"number":1,"strategy":[{"status":"ordering_meals","money":10,"sub_money":5},{"status":"no_meals_ordered","money":10,"sub_money":5},{"status":"unordered_meals","money":10,"sub_money":5}]},{"number":2,"strategy":[{"status":"ordering_meals","money":10,"sub_money":5},{"satus":"no_meals_ordered","money":10,"sub_money":5},{"status":"unordered_meals","money":10,"sub_money":5}]}],
     *     }
     * @apiParam (请求参数说明) {int} id 消费策略id
     * @apiParam (请求参数说明) {int} unordered_meals  是否未订餐允许就餐：1|是；2|否
     * @apiParam (请求参数说明) {int} consumption_count  允许消费次数
     * @apiParam (请求参数说明) {int} ordered_count  订餐数量
     * @apiParam (请求参数说明) {int} detail  策略明细
     * @apiParam (请求参数说明) {int} detail|number  次数类型
     * @apiParam (请求参数说明) {string} detail|strategy  餐次策略明细
     * @apiParam (请求参数说明) {string} detail|strategy|status  消费状态：ordering_meals|订餐就餐；no_meals_ordered|订餐未就餐；unordered_meals|未订餐就餐
     * @apiParam (请求参数说明) {float} detail|strategy|money 标准金额
     * @apiParam (请求参数说明) {float} detail|strategy|sub_money  附加金额
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function updateConsumptionStrategy()
    {
        $params = Request::param();
        $strategy = ConsumptionStrategyT::update($params);
        if (!$strategy) {
            throw new UpdateException();
        }
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
     * @apiSuccess (返回参数说明) {int} unordered_meals 是否未订餐允许就餐：1|是；2|否
     * @apiSuccess (返回参数说明) {int} consumption_count 允许消费次数
     * @apiSuccess (返回参数说明) {int} ordered_count 订餐数量
     * @apiSuccess (返回参数说明) {string} detail 策略明细
     * @apiSuccess (返回参数说明) {obj} dinner 餐次信息
     * @apiSuccess (返回参数说明) {int} dinner|id 餐次id
     * @apiSuccess (返回参数说明) {string} dinner|name 餐次名称
     * @apiSuccess (返回参数说明) {obj} role 人员类型
     * @apiSuccess (返回参数说明) {int} role|id 人员类型id
     * @apiSuccess (返回参数说明) {string} role|name 人员类型名称
     * @apiSuccess (返回参数说明) {obj} canteen 饭堂信息
     * @apiSuccess (返回参数说明) {int} canteen|id 饭堂id
     * @apiSuccess (返回参数说明) {string} canteen|name 饭堂名称
     * @apiSuccess (返回参数说明) {int} detail  策略明细
     * @apiSuccess (返回参数说明) {int} detail|number  次数类型
     * @apiSuccess (返回参数说明) {string} detail|strategy  餐次策略明细
     * @apiSuccess (返回参数说明) {string} detail|strategy|status  消费状态：ordering_meals|订餐就餐；no_meals_ordered|订餐未就餐；unordered_meals|未订餐就餐
     * @apiSuccess (返回参数说明) {float} detail|strategy|money 标准金额
     * @apiSuccess (返回参数说明) {float} detail|strategy|sub_money  附加金额
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
     * @api {POST} /api/v1/canteen/saveMachine CMS管理端-企业管理-添加饭堂硬件
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription     CMS管理端-企业管理-添加饭堂硬件
     * @apiExample {post}  请求样例:
     *    {
     *       "c_id": 6,
     *       "name": "1号设备",
     *       "number": "001",
     *       "code": "dadas12121",
     *       "pwd": "a111",
     *     }
     * @apiParam (请求参数说明) {string} name  设备名称
     * @apiParam (请求参数说明) {int} c_id  饭堂id
     * @apiParam (请求参数说明) {string} number  编号
     * @apiParam (请求参数说明) {string} code  设备号
     * @apiParam (请求参数说明) {string} pwd  设备登陆密码
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
     * @api {POST} /api/v1/canteen/updateMachine CMS管理端-企业管理-修改饭堂硬件信息
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription    CMS管理端-企业管理-修改饭堂硬件信息
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 1,
     *       "name": "1号设备",
     *       "number": "001",
     *       "code": "dadas12121",
     *       "pwd": "a111",
     *     }
     * @apiParam (请求参数说明) {int} id  设备id
     * @apiParam (请求参数说明) {string} name  设备名称
     * @apiParam (请求参数说明) {string} number  编号
     * @apiParam (请求参数说明) {string} code  设备号
     * @apiParam (请求参数说明) {string} pwd  设备登陆密码
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
     * http://canteen.tonglingok.com/api/v1/canteens/company
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"staffs":330,"canteens":[{"id":6,"c_id":3,"name":"饭堂1","modules":[{"id":1,"canteen_id":6,"parent_id":0,"type":1,"name":"设置"},{"id":2,"canteen_id":6,"parent_id":1,"type":2,"name":"小卖部"}],"machines":[{"id":2,"c_id":6,"name":"刷卡器1号","code":"a111111","number":"001","state":1}]},{"id":7,"c_id":3,"name":"饭堂2","modules":[{"id":1,"canteen_id":7,"parent_id":0,"type":1,"name":"设置"},{"id":2,"canteen_id":7,"parent_id":1,"type":2,"name":"小卖部"}],"machines":[]}]}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} staffs  企业人数
     * @apiSuccess (返回参数说明) {obj} canteens  饭堂信息
     * @apiSuccess (返回参数说明) {string} canteens|name  饭堂名称
     * @apiSuccess (返回参数说明) {obj} canteens|modules  饭堂模块信息
     * @apiSuccess (返回参数说明) {int} modules|id 模块id
     * @apiSuccess (返回参数说明) {string} modules|name  模块名称
     * @apiSuccess (返回参数说明) {string} modules|parent_id  模块上级id
     * @apiSuccess (返回参数说明) {int} modules|type  模块类别：1|pc端；2|小卖部
     * @apiSuccess (返回参数说明) {obj} canteens|machines 饭堂设备信息
     * @apiSuccess (返回参数说明) {int} machines|id 设备id
     * @apiSuccess (返回参数说明) {string} machines|number 设备序号
     * @apiSuccess (返回参数说明) {string} machines|code 设备硬件号
     * @apiSuccess (返回参数说明) {string} machines|name 设备硬件名称
     * @apiSuccess (返回参数说明) {int} machines|state 状态：1|正常；2|异常
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
     * http://canteen.tonglingok.com/api/v1/canteens
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
     * @api {GET} /api/v1/managerCanteens  微信端--总订单查询--获取当前角色可管理饭堂列表
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription  微信端--总订单查询--获取当前角色可管理饭堂列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/managerCanteens
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"canteen_id":5,"canteen_name":"饭堂2"},{"canteen_id":1,"canteen_name":"大饭堂"},{"canteen_id":6,"canteen_name":"饭堂1"},{"canteen_id":7,"canteen_name":"饭堂2"}]}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} canteen_id  饭堂ID
     * @apiSuccess (返回参数说明) {string} canteen_name  饭堂名称
     */
    public function managerCanteens()
    {
        $canteens = (new CanteenService())->managerCanteens();
        return json(new SuccessMessageWithData(['data' => $canteens]));
    }

}