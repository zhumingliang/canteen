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
    public function userNotices($page = 1,$size = 10){
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
        $page2=($page-1)*$size;
        $s_id=$staff['id'];
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
    WHERE `a`.`s_id` = ".$s_id." 
    LIMIT ".$page2.",".$size;
        $notice=Db::query($notices);
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
            return json(new SuccessMessageWithData(['data' =>$notice]));
        }
    }

    //上传图片
    public function upload()
    {
        $file=Request::file('image');
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
        $param['img_path']=Request::param('imgUrl');
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
                'type'  => '1'
            ])
            ->field(['title','content','create_time','author','img_path'])
            ->order('create_time','desc')
            ->paginate($size,false,['page'=>$page]);
        return json($data);
    }

    //展示收件人
    public function receiver()
    {
        $id= Request::param('id');
        $company_id = Token::getCurrentTokenVar('current_company_id');
        $s_id = Db::table('canteen_notice_t')
            ->whereIn('id', $id)
            ->field('s_ids')
            ->select();

        $data= $s_id[0]['s_ids'];
        $Uname = Db::table('canteen_company_staff_t')
            ->where('state',1)
            ->whereIn('id',$data)
            ->whereIn('company_id',$company_id)
            ->field('username')
            ->select();
        $Username = array_column($Uname,'username');
        return json(new SuccessMessageWithData(['data' => $Username]));
    }

    //取消小红点
    public function updateNotice(){
        $s_id=Request::param('s_id');
        $n_id=Request::param('n_id');
        $read=Db::table('canteen_notice_user_t')
            ->whereIn('n_id',$n_id)
            ->whereIn('s_id',$s_id)
            ->where('read','2')
            ->data(['read'=>'1'])
            ->update();
        return json(new SuccessMessage());
    }

}