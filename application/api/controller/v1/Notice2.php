<?php


namespace app\api\controller\v1;

use app\api\model\NoticeUserV;
use app\api\service\DepartmentService;
use app\api\service\NoticeService;
use app\api\service\Token;
use app\lib\exception\AuthException;
use app\lib\exception\SaveException;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use think\Db;
use think\facade\Request;
use app\api\model\NoticeT;

class Notice2
{
    //用户通知
    public function userNotices($page = 1, $size = 10)
    {
        $phone = Token::getCurrentPhone();
        $company_id = Token::getCurrentTokenVar('current_company_id');
        $staff = (new DepartmentService())->getStaffWithPhone($phone, $company_id);
        if (empty($staff)) {
            return [
                'total' => 0,
                'per_page' => 10,
                'current_page' => 1,
                'last_page' => 1,
                'data' => array()
            ];
        }
        $page2 = ($page - 1) * $size;
        $s_id = $staff['id'];
        $notices = "SELECT
        `a`.`id` AS `id`,
        `a`.`s_id` AS `s_id`,
        `a`.`read` AS `read`,
        `b`.`title` AS `title`,
        `b`.`content` AS `content`,
        `b`.`equity_url` AS `equity_url`,
        `b`.`equity_title` AS `equity_title`,
        `b`.`create_time` AS `create_time`,
        `b`.`author` AS `author`,
        `b`.`type` AS `type`,
        `b`.`state` AS `state` ,
        `b`.`img_path` AS `img_path` 
        
    FROM
        ( `canteen_notice_user_t` `a` LEFT JOIN `canteen_notice_t` `b` ON ( ( `a`.`n_id` = `b`.`id` ) ) )
    WHERE `a`.`s_id` = " . $s_id . " 
    LIMIT " . $page2 . "," . $size;
        $notice = Db::query($notices);
        return json(new SuccessMessageWithData(['data' => $notice]));
    }

    //通知提醒
    public function notify()
    {
        $id = Request::param('id');
        $where = [
            's_id' => $id,
            'read' => '2'
        ];
        $notice = db::table('canteen_notice_user_t')
            ->where($where)
            ->select();
        if (count($notice) > 0) {
            $data = ['isRead' => false];
        } else {
            $data = ['isRead' => true];
        }
        return json(new SuccessMessageWithData(['data' => $data]));
    }

    //上传图片
    public function upload()
    {
        $file = Request::file('image');
        $path = dirname($_SERVER['SCRIPT_FILENAME']) . '/upload/static/image';
        if (!is_dir($path)) {
            mkdir(iconv("UTF-8", "GBK", $path), 0777, true);
        }
        $info = $file->move($path);
        if (!$info) {
            throw new SaveException();
        }
        $url = ['url' => '/upload/static/image/' . $info->getSaveName()];
        return json(new SuccessMessageWithData(['data' => $url]));

    }


    //发布公告
    public function saveNotice()
    {
        $param = Request::param();
        $param['img_path'] = Request::param('imgUrl');
        if (empty($param['title'])) {
            throw new AuthException(['msg' => '请输入标题']);
        }
        if (empty($param['author'])) {
            throw new AuthException(['msg' => '请输入发布者姓名']);
        }
        if (empty($param['content'])) {
            throw new AuthException(['msg' => '请输入内容']);
        }
        if (empty($param['d_ids']) && empty($param['s_ids'])) {
            throw new AuthException(['msg' => '请输入通知部门或人员ID']);
        }
        if (empty($param['create_time'])) {
            $param['create_time'] = date('Y-m-d H:i:s');
            $param['update_time'] = date('Y-m-d H:i:s');
        }
        if (empty($param['u_id'])) {
            $uid = db::table('canteen_user_t')
                ->where('nickname', $param['author'])
                ->field('id')
                ->find();
            $param['u_id'] = $uid['id'];
        }
        $notice = new NoticeT();
        $save = $notice->save($param);
        $id = Db::table('canteen_notice_t')
            ->order('create_time', 'desc')
            ->field('id')
            ->find();
        $staffs = array();
        if (!empty($param['d_ids'])) {
            $d_id = explode(',', $param['d_ids']);
            $staff = Db::table('canteen_company_staff_t')
                ->whereIn('d_id', $d_id)
                ->field('id')
                ->select();
            foreach ($staff as $staff2) {
                array_push($staffs, $staff2['id']);
            }
        }
        if (!empty($param['s_ids'])) {
            $s_id = explode(',', $param['s_ids']);
            foreach ($s_id as $s_id2) {
                array_push($staffs, $s_id2);
            }
        }
        foreach ($staffs as $staffs2) {
            $data = [
                's_id' => $staffs2,
                'read' => 2,
                'n_id' => $id['id'],
                'create_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s')
            ];
            db('notice_user_t')
                ->data($data)
                ->insert();
        }
        if ($save) {
            return json(new SuccessMessage());
        } else {
            throw new SaveException();
        }
    }

    //展示公告
    public function Notice($page = 1, $size = 10)
    {
        $data = Db::table('canteen_notice_t')
            ->where([
                'state' => '1',
                'type' => '1'
            ])
            ->field(['id', 'title', 'content', 'create_time', 'author', 'img_path'])
            ->order('create_time', 'desc')
            ->paginate($size, false, ['page' => $page]);
        return json($data);
    }

    //展示收件人
    public function receiver()
    {
        $id = Request::param('id');
//        $company_id = 69;
        $company_id = Token::getCurrentTokenVar('current_company_id');
        $staff_info = Db::table('canteen_notice_t')
            ->whereIn('id', $id)
            ->field('s_ids,d_ids')
            ->select();
        if ($staff_info->isEmpty()) {
            throw new AuthException(['msg' => '未找到人员信息']);
        }
        $s_ids = $staff_info[0]['s_ids'];
        $d_ids = $staff_info[0]['d_ids'];
        if (empty($s_ids)) {
            $s_ids = 0;
        }
        if (empty($d_ids)) {
            $d_ids = 0;
        }
        $dtResult = Db::query("select username from canteen_company_staff_t where state = 1 and company_id = " . $company_id . " and id in (" . $s_ids . ") or d_id in(" . $d_ids . ")");

        $Username = array_column($dtResult, 'username');
        return json(new SuccessMessageWithData(['data' => $Username]));
    }

    //取消小红点
    public function updateNotice()
    {
        $s_id = Request::param('s_id');
        $n_id = Request::param('n_id');
        $read = Db::table('canteen_notice_user_t')
            ->whereIn('n_id', $n_id)
            ->whereIn('s_id', $s_id)
            ->where('read', '2')
            ->data(['read' => '1'])
            ->update();
        return json(new SuccessMessage());
    }

}