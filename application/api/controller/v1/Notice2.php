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
//        $phone = '15018891369';
//        $company_id = 69;
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
        $s_id = $staff['id'];
        $notices = db('notice_user_t')
            ->alias('a')
            ->leftJoin('canteen_notice_t b', 'a.n_id = b.id')
            ->where('a.s_id', $s_id)
            ->field('b.id,a.s_id,a.read,b.title,b.content,b.equity_url,b.equity_title,b.create_time,b.author,b.type,b.state,b.img_path')
            ->order('b.create_time desc')
            ->paginate($size, false, ['page' => $page]);
        return json(new SuccessMessageWithData(['data' => $notices]));
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

        if (empty($param['d_ids']) && empty($param['s_ids'])) {
            throw new AuthException(['msg' => '请输入通知部门或人员ID']);
        }
        if (empty($param['create_time'])) {
            $param['create_time'] = date('Y-m-d H:i:s');
            $param['update_time'] = date('Y-m-d H:i:s');
        }
        $params['u_id'] = Token::getCurrentUid();
        $notice = NoticeT::create($param);
        if (!$notice) {
            throw new SaveException();
        }
        $n_id = $notice->id;
        $staffs = array();
        if (!empty($param['d_ids'])) {
            $staff = Db::table('canteen_company_staff_t')
                ->whereIn('d_id', $param['d_ids'])
                ->field('id')
                ->select();
            if (!empty($staff)) {
                foreach ($staff as $staff_id) {
                    array_push($staffs, $staff_id['id']);
                }
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
                'n_id' => $n_id,
                'create_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s')
            ];
            $save = db('notice_user_t')
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
        $u_id = Token::getCurrentUid();
        $data = Db::table('canteen_notice_t')
            ->where([
                'state' => '1',
                'type' => '1',
                'u_id' => $u_id
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
        Db::table('canteen_notice_user_t')
            ->whereIn('n_id', $n_id)
            ->whereIn('s_id', $s_id)
            ->where('read', '2')
            ->data(['read' => '1', 'update_time' => date('Y-m-d H:i:s')])
            ->update();
        return json(new SuccessMessage());
    }

}