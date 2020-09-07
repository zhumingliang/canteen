<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\ShopService;
use app\api\service\WalletService;
use app\api\model\UserBalanceV;
use app\lib\enum\CommonEnum;
use app\lib\exception\DeleteException;
use think\Db;
use think\facade\Request;
use think\exception\DbException;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use app\lib\exception\AuthException;
use app\lib\exception\ParameterException;

class Pos extends BaseController
{
    /**
     * 扫描二维码登录
     */
    public function login()
    {
        $cardCode = Request::param('card_code');
        $companyId = Request::param('company_id');
        if (empty($companyId)) {
            throw new AuthException(['msg' => '企业id为空，未找到相关企业']);
        }
        if (empty($cardCode)) {
            throw new AuthException(['msg' => '请刷卡登录']);
        }
        $sql = "select t3.name from canteen_staff_card_t t1 left join canteen_company_staff_t t2 on t1.staff_id=t2.id left join canteen_staff_type_t t3 on t2.t_id=t3.id where t2.company_id='" . $companyId . "' and t1.card_code='" . $cardCode . "' and t2.state=1";
        $name = Db::query($sql);
        if ($name[0]['name'] == '管理员') {
            return json(new SuccessMessage());
        } else {
            throw new AuthException(['msg' => '非管理员，没有登录权限']);

        }
    }

    /**
     * 展示当天扣款笔数和金额
     */
    public function getTotalRecords()
    {
        //设置查询当天扣款笔数和金额的开始时间,因为当天24点后就是第二天，所以不需要再设置结束时间
        $start = date('Y-m-d');

        //扣款笔数等于count订餐数量字段总数
        $sql = "select count(count) as count from canteen_shop_order_t where date_format(create_time,'%Y-%m-%d') ='" . $start . "'";

        $total = Db::query($sql);
        //金额等于每笔订单的金额大于0的总数

        $SQL = "select ifnull(sum(money),0) as sum from canteen_shop_order_t where date_format(create_time,'%Y-%m-%d') = '" . $start . "'and money>0";

        $money = Db::query($SQL);
        $data = [['count' => $total[0]['count'],
            'sum' => $money[0]['sum']
        ]];
        return json(new SuccessMessageWithData(['data' => $data]));
    }

    /**
     * 展示人员信息
     */
    public function getStaffInfo()
    {
        $cardCode = Request::param('card_code');
        $companyId = Request::param('company_id');

        if (empty($companyId)) {
            throw new AuthException(['msg' => '未接收到企业id']);
        }
        if (empty($cardCode)) {
            throw new AuthException(['msg' => '未接收到卡号']);
        }
        $sql = "select t1.username ,t2.name as department_name ,t1.phone ,t3.state,t1.birthday from canteen_company_staff_t t1 left join canteen_company_department_t t2 on t1.d_id=t2.id left join canteen_staff_card_t t3 on t1.id=t3.staff_id where t3.card_code='" . $cardCode . "' and t1.company_id='" . $companyId . "' and t1.state=1";
        $data = Db::query($sql);

        return json(new SuccessMessageWithData(['data' => $data]));
    }

    /**
     * 展示账户信息
     */
    public function getAccounts($company_id = 0)
    {
        $phone = Request::param('phone');
        if (empty($phone)) {
            throw  new  AuthException(['msg' => '手机号码不能为空']);
        }
        $users = $this->usersBalance($phone, $company_id);
        return json(new SuccessMessageWithData(['data' => $users]));
    }

    /**
     * 扣费
     */
    public function consume()
    {
        $params = Request::param();
        $this->usersConsume($params, 'consume');
        return json(new SuccessMessage());
    }

    /**
     *退款
     */
    public function refund()
    {
        $params = Request::param();
        $this->usersConsume($params, 'refund');
        return json(new SuccessMessage());

    }

    /**
     * 绑卡
     */
    public function bindingCard()
    {
        $params = Request::param();
        $username = $this->usersBindingCard($params);
        $data = ['username' => $username];
        return json(new SuccessMessageWithData(['data' => $data]));
    }

    /**
     * 输入手机号码和出生日期获取卡信息
     */
    public function getCardInfo()
    {
        $phone = Request::param('phone');
        $birthday = Request::param('birthday');
        $companyId = Request::param('company_id');
        if (empty($phone)) {
            throw new Exception("手机号码不能为空！！");
        }
        if (empty($birthday)) {
            throw new Exception("出生日期不能为空！！");
        }
        if (empty($companyId)) {
            throw new Exception("企业id不能为空！！");
        }
        $data = db('company_staff_t')->where('phone', $phone)
            ->where('birthday', $birthday)
            ->where('company_id', $companyId)
            ->find();
        $username = $data['username'];
        $uId = $data['id'];
        if (empty($data)) {
            throw new AuthException(['msg' => '查询信息错误，未找到卡号']);
        } else {
            $data2 = db('staff_card_t')->where('staff_id', $uId)->find();
            $cardCode = $data2['card_code'];
            return json(new SuccessMessageWithData(['data' => ['username' => $username, 'card_code' => $cardCode]]));
        }
    }

    /**
     * 挂失
     */
    public function loss()
    {
        $phone=Request::param('phone');
        $birthday=Request::param('birthday');
        $companyId=Request::param('company_id');
        $date = date('Y-m-d H:i:s');
        $data = db('company_staff_t')->where('phone', $phone)
            ->where('birthday', $birthday)
            ->where('company_id', $companyId)
            ->find();
        $uId=$data['id'];
        if(empty($data)){
            throw new AuthException(['msg'=>'输入信息错误，挂失失败']);
        }
        else{
            $sql = "update canteen_staff_card_t set state = 1,update_time = '" . $date . "' where staff_id =" . $uId;
            $date = Db::execute($sql);
            $sql2=db('company_staff_t')
                ->where('phone', $phone)
                ->where('birthday', $birthday)
                ->where('company_id', $companyId)
                ->update(['state' => '2']);

            if ($date >0) {
                return json(new SuccessMessage());


            }
        }
    }

    /**
     * 注销
     */
    public function cancel()
    {
        $phone=Request::param('phone');
        $birthday=Request::param('birthday');
        $companyId=Request::param('company_id');
        $date = date('Y-m-d H:i:s');
        $data = db('company_staff_t')->where('phone', $phone)
            ->where('birthday', $birthday)
            ->where('company_id', $companyId)
            ->find();
        $uId=$data['id'];
        if(empty($data)){
            throw new AuthException(['msg'=>'输入信息错误，注销失败']);
        }
        else{
            $sql = "update canteen_staff_card_t set state = 3,update_time = '" . $date . "' where staff_id =" . $uId;
            $date = Db::execute($sql);
            $sql2=db('company_staff_t')
                ->where('phone', $phone)
                ->where('birthday', $birthday)
                ->where('company_id', $companyId)
                ->update(['state' => '1']);

            if ($date > 0) {
                return json(new SuccessMessage());



            }
        }
    }

    /**
     * 恢复
     */
    public function recover()
    {
        $phone=Request::param('phone');
        $birthday=Request::param('birthday');
        $companyId=Request::param('company_id');
        $date = date('Y-m-d H:i:s');
        $data = db('company_staff_t')->where('phone', $phone)
            ->where('birthday', $birthday)
            ->where('company_id', $companyId)
            ->find();
        $uId=$data['id'];
        if(empty($data)){
            throw new AuthException(['msg'=>'输入信息错误，恢复失败']);
        }
        else{
            $sql = "update canteen_staff_card_t set state = 1,update_time = '" . $date . "' where staff_id =" . $uId;
            $date = Db::execute($sql);
            $sql2=db('company_staff_t')
                ->where('phone', $phone)
                ->where('birthday', $birthday)
                ->where('company_id', $companyId)
                ->update(['state' => '1']);
            if ($date > 0) {
                return json(new SuccessMessage());
            }
        }
    }

    /**
     * 通过设备获取企业
     */
    public function machine()
    {
        $code = Request::param('code');
        if (empty($code)) {
            throw new AuthException(['msg' => '未获取到Pos机标识码']);
        }
        $sql = "select t1.company_id,t2.name as company_name ,t3.name as shop_name from canteen_machine_t t1 left join canteen_company_t t2 on t1.company_id=t2.id left join canteen_shop_t t3 on t1.belong_id=t3.id where t1.code='" . $code . "' and t1.state=1";
        $data = Db::query($sql);

        return json(new SuccessMessageWithData(['data' => $data]));
    }

    private function usersBalance($phone, $company_id)
    {
        $orderings = db('user_balance_v')->where('company_id', $company_id)
            ->where('phone', $phone)
            ->field('username,phone,department,sum(money) as balance')
            ->group('phone,company_id')
            ->find();
        return $orderings;
    }

    private function usersConsume($params, $type)
    {
        $money = $params['money'];
        $card_code = $params['card_code'];
        $cardInfo = db('staff_card_t')
            ->alias('t1')
            ->leftJoin('company_staff_t t2', 't1.staff_id = t2.id')
            ->where('card_code', $card_code)
            ->where('t1.state', CommonEnum::STATE_IS_OK)
            ->where('t2.state', CommonEnum::STATE_IS_OK)
            ->field('t1.staff_id,t2.d_id,t2.t_id,t2.company_id,t2.phone')
            ->find();
        if (empty($cardInfo)) {
            throw  new  AuthException(['msg' => '找不到该卡或卡已注销']);
        }
        $company_id = $cardInfo['company_id'];
        $phone = $cardInfo['phone'];
        $staff_id = $cardInfo['staff_id'];
        $d_id = $cardInfo['d_id'];
        $t_id = $cardInfo['t_id'];
        if ($type == 'refund') {
            $lastData = db('shop_order_t')
                ->where('staff_id', $staff_id)
                ->where('money' > 0)
                ->order('id desc')
                ->limit(1)
                ->field('money')
                ->find();
            $lastMoney = $lastData['money'];
            $refundMoney = str_replace("-", "", $money);
            if ($refundMoney > $lastMoney) {
                throw  new AuthException(['msg' => '退款金额必须小于或等于上一笔金额']);
            }
        }
        if ($type == 'consume') {
            $balance = UserBalanceV::userBalance($company_id, $phone);
            if ($balance < $money) {
                throw  new  AuthException(['msg' => '余额不足']);
            }
        }
        $order_num = makeOrderNo();
        $date = date('Y-m-d H:i:s');
        $data = [
            'company_id' => $company_id,
            'shop_id' => (new ShopService())->getShopId($company_id),
            'u_id' => 0,
            'order_num' => $order_num,
            'count' => 1,
            'money' => $money,
            'distribution' => 1,
            'state' => 1,
            'create_time' => $date,
            'update_time' => $date,
            'address_id' => 0,
            'pay' => 'paid',
            'pay_way' => 2,
            'used' => 1,
            'department_id' => $d_id,
            'staff_type_id' => $t_id,
            'complete' => 1,
            'used_time' => $date,
            'staff_id' => $staff_id,
            'phone' => $phone,
            'send' => 1
        ];
        $save = db('shop_order_t')
            ->data($data)
            ->insert();
        if ($save < 0) {
            throw  new  AuthException(['msg' => '扣费失败']);
        }
    }

    private function usersBindingCard($params)
    {
        $card_code = $params['card_code'];
        $birthday = $params['birthday'];
        $phone = $params['phone'];
        $company_id = $params['company_id'];
        $date = date('Y-m-d H:i:s');
        $phoneInfo = db('company_staff_t')
            ->where('company_id', $company_id)
            ->where('phone', $phone)
            ->select();
        if (count($phoneInfo) == 0) {
            throw new AuthException(['msg' => '绑卡失败，手机号码不存在']);
        }
        $user = db('company_staff_t')
            ->where('company_id', $company_id)
            ->where('phone', $phone)
            ->where('birthday', $birthday)
            ->find();
        if (empty($user)) {
            throw new AuthException(['msg' => '绑卡失败，出生日期与手机号码不匹配']);
        }
        if ($user['state'] != 1) {
            throw new AuthException(['msg' => '账号已经停用。请在挂失功能中启用账号或注销卡号，再重新绑定']);
        }
        $deleteRes = db('staff_card_t')
            ->whereOr('staff_id', $user['id'])
            ->whereOr('card_code', $card_code)
            ->delete();
        $data = ['staff_id' => $user['id'], 'card_code' => $card_code, 'state' => 1, 'create_time' => $date, 'update_time' => $date];
        $save = db('staff_card_t')
            ->data($data)
            ->insert();
        if ($save < 0) {
            throw  new  AuthException(['msg' => '绑卡失败']);
        }
        return $user['username'];
    }
}
