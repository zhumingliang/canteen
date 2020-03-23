<?php
/**
 * Created by PhpStorm.
 * User: 明良
 * Date: 2019/9/17
 * Time: 2:12
 */

namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\NoticeService;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use think\facade\Request;

class Notice extends BaseController
{
    /**
     * @api {POST} /api/v1/notice/save  微信端-新增公告发布
     * @apiGroup   Official
     * @apiVersion 3.0.0
     * @apiDescription    微信端-新增公告发布
     * @apiExample {post}  请求样例:
     *    {
     *       "title": "国庆放假通知",
     *       "content": "国庆放假七天",
     *       "author": "张三",
     *       "d_ids": "1,2,3",
     *       "s_ids": "1,2,3"
     *     }
     * @apiParam (请求参数说明) {string} title  标题
     * @apiParam (请求参数说明) {string} content  内容
     * @apiParam (请求参数说明) {string} author  作者
     * @apiParam (请求参数说明) {string} d_ids  部门id，多个用逗号分隔
     * @apiParam (请求参数说明) {string} s_ids  部门人员id，多个用逗号分隔
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {string} msg 信息描述
     */
    public function save()
    {
        $params = Request::param();
        (new NoticeService())->saveNotice($params);
        return json(new SuccessMessage());
    }

    /**
     * @api {GET} /api/v1/notices/admin 微信端-公告发布-公告列表
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription 微信端-公告发布-公告列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/notices/admin?page=1&size=10
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":1,"per_page":10,"current_page":1,"last_page":1,"data":[{"id":1,"title":"国庆放假通知","content":"国庆放假七天","author":"里斯","create_time":"     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id  通知id
     * @apiSuccess (返回参数说明) {string} title  标题
     * @apiSuccess (返回参数说明) {string} content  内容
     * @apiSuccess (返回参数说明) {string} author  作者
     * @apiSuccess (返回参数说明) {string} create_time 创建时间
     */
    public function adminNotices($page = 1, $size = 10)
    {
        $notices = (new NoticeService())->adminNotices($page, $size);
        return json(new SuccessMessageWithData(['data' => $notices]));
    }

    /**
     * @api {GET} /api/v1/notices/user 微信端-通知-通知列表
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription 微信端-通知-通知列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/notices/user?page=1&size=10
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":1,"per_page":10,"current_page":1,"last_page":1,"data":[{"id":1,"s_id":9,"read":2,"title":"国庆放假通知","content":"国庆放假七天","equity_url":"http:\/\/ a.ccom","equity_title":"国庆优惠","create_time":"2019-09-17 16:43:05","author":"里斯","type":"公告"}]}}
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id  通知id
     * @apiSuccess (返回参数说明) {string} title  标题
     * @apiSuccess (返回参数说明) {string} content  内容
     * @apiSuccess (返回参数说明) {string} author  作者
     * @apiSuccess (返回参数说明) {string} equity_url  权益跳转地址
     * @apiSuccess (返回参数说明) {string} equity_title  权益标题
     * @apiSuccess (返回参数说明) {string} type  通知类别
     * @apiSuccess (返回参数说明) {string} create_time 创建时间
     */
    public function userNotices($page = 1, $size = 10)
    {
        $notices = (new NoticeService())->userNotices($page, $size);
        return json(new SuccessMessageWithData(['data' => $notices]));
    }

    /**
     * @api {POST} /api/v1/notice/delete 微信端-公告发布-删除公告
     * @apiGroup   CMS
     * @apiVersion 3.0.0
     * @apiDescription  微信端-公告发布-删除公告
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 1
     *     }
     * @apiParam (请求参数说明) {int} id  公告ID
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     */
    public function deleteNotice()
    {
        $id = Request::param('id');
        (new NoticeService())->deleteNotice($id);
        return json(new SuccessMessage());
    }

    /**
     * @api {GET} /api/v1/notice 微信端-公告发布-公告信息
     * @apiGroup  Official
     * @apiVersion 3.0.0
     * @apiDescription 微信端-公告发布-公告列表
     * @apiExample {get}  请求样例:
     * http://canteen.tonglingok.com/api/v1/notice?id=1
     * @apiParam (请求参数说明) {int} id 公告id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"id":1,"d_ids":"3,4","title":"国庆放假通知","content":"国庆放假七天","equity_url":"http:\/\/ a.ccom","create_time":"2019-09-17 16:43:05","equity_title":"国庆优惠","type":1,"author":"里斯","s_ids":"30"}}
     * @apiSuccess (返回参数说明) {int} id  通知id
     * @apiSuccess (返回参数说明) {string} title  标题
     * @apiSuccess (返回参数说明) {string} content  内容
     * @apiSuccess (返回参数说明) {string} author  作者
     * @apiSuccess (返回参数说明) {string} create_time 创建时间
     * @apiSuccess (返回参数说明) {string} d_ids  部门id，多个用逗号分隔
     * @apiSuccess (返回参数说明) {string} s_ids  部门人员id，多个用逗号分隔
     * @apiSuccess (返回参数说明) {string} equity_url  权益跳转地址
     * @apiSuccess (返回参数说明) {string} equity_title  权益标题
     */
    public function notice()
    {
        $id = Request::param('id');
        $notice = (new NoticeService())->notice($id);
        return json(new SuccessMessageWithData(['data' => $notice]));
    }


}