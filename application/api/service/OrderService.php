<?php
/**
 * Created by PhpStorm.
 * User: 明良
 * Date: 2019/9/5
 * Time: 11:14
 */

namespace app\api\service;


use app\api\model\CanteenAccountT;
use app\api\model\DinnerT;
use app\api\model\OrderDetailT;
use app\api\model\OrderingV;
use app\api\model\OrderT;
use app\lib\enum\CommonEnum;
use app\lib\enum\MenuEnum;
use app\lib\enum\OrderEnum;
use app\lib\enum\PayEnum;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;
use app\lib\exception\UpdateException;
use think\Db;
use think\Exception;

class OrderService extends BaseService
{

    public function personChoice($params)
    {
        try {
            Db::startTrans();
            $dinner_id = $params['dinner_id'];
            $dinner = $params['dinner'];
            $ordering_date = $params['ordering_date'];
            $count = $params['count'];
            $detail = json_decode($params['detail'], true);
            unset($params['detail']);

            $params['ordering_type'] = OrderEnum::ORDERING_CHOICE;
            $u_id = Token::getCurrentUid();

            //检测该餐次订餐时间是否允许
            $this->checkDinnerForPersonalChoice($dinner_id, $ordering_date);
            $canteen_id = Token::getCurrentTokenVar('current_canteen_id');
            $this->checkUserCanOrder($u_id, $dinner_id, $dinner, $ordering_date, $canteen_id, $count, $detail);
            $money = $this->getOrderingMoney($detail);
            $params['money'] = $money;
            $pay_way = $this->checkBalance($u_id, $canteen_id, $money);
            if (!$pay_way) {
                throw new  SaveException(['msg' => '余额不足，请先充值']);
            }
            //保存订单信息
            $params['order_num'] = makeOrderNo();
            $params['pay_way'] = $pay_way;
            $params['u_id'] = $u_id;
            $params['c_id'] = $canteen_id;
            $params['d_id'] = $dinner_id;
            $params['t_id'] = Token::getCurrentTokenVar('t_id');
            $params['pay'] = CommonEnum::STATE_IS_OK;
            $order = OrderT::create($params);
            if (!$order) {
                throw new SaveException(['msg' => '生成订单失败']);
            }
            $this->prefixDetail($detail, $order->id);
            if ($params['type'] == OrderEnum::EAT_OUTSIDER && !empty($params['address_id'])) {
                (new AddressService())->prefixAddressDefault($params['address_id']);
            }
            Db::commit();
            return [
                'id' => $order->id
            ];
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }

    }

    private function getOrderingMoney($detail)
    {
        $money = 0;
        foreach ($detail as $k => $v) {
            $foods = $v['foods'];
            foreach ($foods as $k2 => $v2) {
                $money += $v2['price'];
            }
        }
        return $money;
    }

    public
    function prefixDetail($detail, $o_id)
    {
        $data_list = [];
        foreach ($detail as $k => $v) {
            $foods = $v['foods'];
            foreach ($foods as $k2 => $v2) {
                $data = [
                    'm_id' => $v['menu_id'],
                    'f_id' => $v2['food_id'],
                    'price' => $v2['price'],
                    'count' => $v2['count'],
                    'o_id' => $o_id,
                    'state' => CommonEnum::STATE_IS_OK,
                ];
                array_push($data_list, $data);
            }

        }
        $res = (new OrderDetailT())->saveAll($data_list);
        if (!$res) {
            throw new SaveException(['msg' => '存储订餐明细失败']);
        }
    }

    public
    function checkBalance($u_id, $canteen_id, $money)
    {
        $balance = 10000;
        if ($balance >= $money) {
            return PayEnum::PAY_BALANCE;
        }
        //获取账户设置，检测是否可预支消费
        $canteenAccount = CanteenAccountT::where('c_id', $canteen_id)->find();
        if (!$canteenAccount) {
            return false;
        }

        if ($canteenAccount->type == OrderEnum::OVERDRAFT_NO) {
            return false;
        }
        if ($canteenAccount->limit_money < ($money - $balance)) {
            return false;
        }
        return PayEnum::PAY_OVERDRAFT;

    }

    public
    function checkUserCanOrder($u_id, $dinner_id, $dinner, $day, $canteen_id, $count, $detail)
    {
        //获取用户指定日期订餐信息
        $record = OrderingV::getRecordForDayOrdering($u_id, $day, $dinner);
        if ($record) {
            throw new SaveException(['msg' => '本餐次今日在' . $record->canteen . '已经预定，不能重复预定']);
        }
        //检测消费策略
        $this->checkConsumptionStrategy($canteen_id, $dinner_id, $count);

        //检测菜单数据是否合法并返回订单金额
        $this->checkMenu($dinner_id, $detail);
    }

    //检测是否在订餐时间内
    public function checkDinnerForPersonalChoice($dinner_id, $ordering_date)
    {
        $dinner = DinnerT::dinnerInfo($dinner_id);
        if (!$dinner) {
            throw new ParameterException(['msg' => '指定餐次未设置']);
        }
        $type = $dinner->type;
        if ($type == 'week') {
            throw new ParameterException(['msg' => '当前餐次需批量订餐，请使用线上订餐功能订餐']);
        }
        $limit_time = $dinner->limit_time;
        $type_number = $dinner->type_number;
        $expiryDate = $this->prefixExpiryDate($ordering_date, [$type => $type_number]);
        if (strtotime($limit_time) > strtotime($expiryDate)) {
            throw  new  SaveException(['msg' => '超出订餐时间']);
        }
    }

    //检测是否在订餐时间内
    /*  public function checkDinnerForOnline($dinner, $ordering_date)
      {
          // $dinner = DinnerT::dinnerInfo($dinner_id);
          if (!$dinner) {
              throw new ParameterException(['msg' => '指定餐次未设置']);
          }
          $type = $dinner->type;
          $limit_time = $dinner->limit_time;
          $type_number = $dinner->type_number;
          if ($type == 'week') {

          }

          $expiryDate = $this->prefixExpiryDate($ordering_date, [$type => $type_number]);
          if (strtotime($limit_time) > strtotime($expiryDate)) {
              throw  new  SaveException(['msg' => '超出订餐时间']);
          }
      }*/

    private
    function checkConsumptionStrategy($canteen_id, $dinner_id, $count)
    {
        $phone = Token::getCurrentPhone();
        $t_id = (new UserService())->getUserStaffTypeByPhone($phone);
        //获取指定用户消费策略
        $strategies = (new CanteenService())->getStaffConsumptionStrategy($canteen_id, $dinner_id, $t_id);
        if (!$strategies) {
            throw new SaveException(['msg' => '饭堂消费策略没有设置']);
        }
        if ($count > $strategies->ordered_count) {
            throw new SaveException(['msg' => '订餐数量超过最大订餐数量，最大订餐数量为：' . $strategies->ordered_count]);
        }
        return true;

    }

    private
    function checkMenu($dinner_id, $detail)
    {
        if (empty($detail)) {
            throw new ParameterException(['菜品明细数据格式不对']);
        }
        //获取餐次下所有菜品类别
        $menus = (new MenuService())->dinnerMenus($dinner_id);
        if (!count($menus)) {
            throw new ParameterException(['msg' => '指定餐次未设置菜单信息']);
        }

        foreach ($detail as $k => $v) {
            $menu_id = $v['menu_id'];
            $menu = $this->getMenuInfo($menus, $menu_id);
            if (empty($menu)) {
                throw new ParameterException(['msg' => '菜品类别id错误']);
            }
            if (($menu['status'] == MenuEnum::FIXED) && ($menu['count'] < count($v['foods']))) {
                throw new SaveException(['msg' => '选菜失败,菜品类别：<' . $menu['category'] . '> 选菜数量超过最大值：' . $menu['count']]);
            }
        }
    }

    private
    function getMenuInfo($menus, $menu_id)
    {
        $menu = [];
        foreach ($menus as $k => $v) {
            if ($v->id == $menu_id) {
                $menu['status'] = $v->status;
                $menu['count'] = $v->count;
                $menu['category'] = $v->category;
                break;
            }

        }
        return $menu;
    }

    /**
     * 线上订餐
     */
    public function orderingOnline($detail)
    {
        try {
            Db::startTrans();
            $detail = json_decode($detail, true);
            if (empty($detail)) {
                throw new ParameterException(['msg' => '订餐数据格式错误']);
            }
            $u_id = Token::getCurrentUid();
            $canteen_id = Token::getCurrentTokenVar('current_canteen_id');
            $data = $this->prefixOnlineOrderingData($u_id, $canteen_id, $detail);
            $money = $data['all_money'];
            $pay_way = $this->checkBalance($u_id, $canteen_id, $money);
            $list = $this->prefixPayWay($pay_way, $data['list']);
            $ordering = (new OrderT())->saveAll($list);
            if (!$ordering) {
                throw  new SaveException();
            }
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }

    }

    private function prefixPayWay($pay_way, $list)
    {
        foreach ($list as $k => $v) {
            $list[$k]['pay_way'] = $pay_way;
        }
        return $list;
    }

    /**
     * 处理线上订餐信息
     * 计算订单总价格
     */
    private function prefixOnlineOrderingData($u_id, $canteen_id, $detail)
    {

        $data_list = [];
        $all_money = 0;
        $t_id = Token::getCurrentTokenVar('current_canteen_id');
        //获取用户所有有效订餐信息
        $records = OrderingV::getUserOrdering($u_id);
        foreach ($detail as $k => $v) {
            //检测该餐次是否在订餐时间范围内

            $ordering_data = $v['ordering'];
            $money = $this->getStrategyMoneyForOrderingOnline($canteen_id, $v['d_id'], $t_id);
            if (!empty($ordering_data)) {
                foreach ($ordering_data as $k2 => $v2) {
                    //检测是否重复订餐
                    $this->checkDinnerOrdered($v2['ordering_date'], $v['d_id'], $records);
                    $data = [];
                    $data['u_id'] = $u_id;
                    $data['c_id'] = $canteen_id;
                    $data['d_id'] = $v['d_id'];
                    $data['t_id'] = Token::getCurrentTokenVar('t_id');;
                    $data['ordering_date'] = $v2['ordering_date'];
                    $data['count'] = $v2['count'];
                    $data['order_num'] = makeOrderNo();
                    $data['ordering_type'] = OrderEnum::ORDERING_ONLINE;
                    $data['money'] = $money;
                    $data['pay_way'] = '';
                    $data['pay'] = CommonEnum::STATE_IS_OK;
                    array_push($data_list, $data);
                    $all_money += $money;
                }

            }

        }
        return [
            'all_money' => $all_money,
            'list' => $data_list
        ];

    }

    public function checkDinnerOrdered($ordering_date, $dinner_id, $records)
    {
        if (empty($records)) {
            return true;
        }
        foreach ($records as $k => $v) {
            if (strtotime($ordering_date) == strtotime($v['ordering_date']) && $dinner_id == $v['d_id']) {
                throw new SaveException(['msg' => '订餐失败，' . '日期：' . $ordering_date . ';餐次：' . $v['dinner'] . ';已在饭堂：' . $v['canteen'] . '预定']);
                break;
            }
        }

    }

    /**
     * 获取消费策略中订餐消费默认金额
     */
    private function getStrategyMoneyForOrderingOnline($c_id, $d_id, $t_id)
    {
        $money = 0;
        $strategy = (new CanteenService())->getStaffConsumptionStrategy($c_id, $d_id, $t_id);
        $strategy = $strategy->toArray();
        $detail = $strategy['detail'];
        if (empty($detail)) {
            throw  new ParameterException(['msg' => '消费策略未设置或参数格式错误']);
        }
        foreach ($detail as $k => $v) {
            $info = $v['strategy'];
            if (empty($info)) {
                throw  new ParameterException(['msg' => '消费策略设置出错']);
            }
            foreach ($info as $k2 => $v2) {
                if ($info['status'] = 'ordering_meals') {
                    $money = $v2['money'];
                    break;
                }
            }

            if ($money) {
                break;
            }

        }
        return $money;
    }

    /**
     * 获取用户的订餐信息
     * 今天及今天以后订餐信息
     */
    public function userOrdering()
    {
        $u_id = Token::getCurrentUid();
        $orderings = OrderingV::userOrdering($u_id);
        return $orderings;


    }

    /**
     * 线上订餐获取初始化信息
     * 1.餐次信息及订餐时间限制
     * 2.消费策略
     */
    public function infoForOnline()
    {
        $canteen_id = Token::getCurrentTokenVar('current_canteen_id');
        $t_id = Token::getCurrentTokenVar('t_id');
        $dinner = (new CanteenService())->getDinners($canteen_id);
        $strategies = (new CanteenService())->staffStrategy($canteen_id, $t_id);
        foreach ($dinner as $k => $v) {
            foreach ($strategies as $k2 => $v2) {
                if ($v['id'] = $v2['d_id']) {
                    $dinner[$k]['ordered_count'] = $v2['ordered_count'];
                    unset($strategies[$k2]);
                }

            }
        }
        return $dinner;
    }

    /**
     * 取消订单
     */
    public function orderCancel($id)
    {
        //检测取消订餐操作是否可以执行
        $order = OrderT::where('id', $id)->find();
        if (!$order) {
            throw new ParameterException(['msg' => '指定订餐信息不存在']);
        }
        $this->checkOrderCanHandel($order->d_id);
        $order->state = CommonEnum::STATE_IS_FAIL;
        $res = $order->save();
        if (!$res) {
            throw new SaveException();
        }
    }

    private function checkOrderCanHandel($d_id)
    {
        //获取餐次设置
        $dinner = DinnerT::dinnerInfo($d_id);
        $type = $dinner->type;
        $limit_time = $dinner->limit_time;
        $type_number = $dinner->type_number;
        if ($type == 'day') {
            $expiryDate = $this->prefixExpiryDateForOrder($dinner->ordering_date, $type_number, '-');
            if (strtotime(date('Y-m-d H:i:s', time())) > strtotime($expiryDate . ' ' . $limit_time)) {
                throw  new  SaveException(['msg' => '当前时间不可操作订单']);
            }
        } else if ($type == 'week') {
            $ordering_date_week = date('W', strtotime($dinner->ordering_date));
            $now_week = date('W', time());
            if ($ordering_date_week <= $now_week) {
                throw  new  SaveException(['msg' => '当前时间不可操作订单']);
            }
            if (($ordering_date_week - $now_week) === 1) {
                if ($type_number == 0) {
                    //星期天
                    if (strtotime($limit_time) < time()) {
                        throw  new  SaveException(['msg' => '当前时间不可操作订单']);
                    }
                } else {
                    //周一到周六
                    if (date('w', time()) > $type_number) {
                        throw  new  SaveException(['msg' => '当前时间不可操作订单']);
                    } else if (date('w', time()) == $type_number && strtotime($limit_time) < time()) {
                        throw  new  SaveException(['msg' => '当前时间不可操作订单']);
                    }
                }
            }

        }
        return true;
    }

    /**
     * 修改订餐数量
     */
    public function changeOrderCount($id, $count)
    {
        $order = OrderT::where('id', $id)->find();
        if (!$order) {
            throw new ParameterException(['msg' => '指定订餐信息不存在']);
        }
        //检测订单是否可操作
        $this->checkOrderCanHandel($order->d_id);
        //检测订单修改数量是否合法
        $strategy = (new CanteenService())->getStaffConsumptionStrategy($order->c_id, $order->d_id, $order->t_id);
        if (!$strategy) {
            throw new ParameterException(['msg' => '当前用户消费策略不存在']);
        }
        if ($count > $strategy->ordered_count) {
            throw new UpdateException(['msg' => '当前用户消费策略不存在']);
        }
        $old_money = $order->money;
        $old_count = $order->count;
        $new_money = ($old_money / $old_count) * $count;
        //检测订单金额是否合法
        $check_res = $this->checkBalance($order->u_id, $order->c_id, ($new_money - $old_money));
        if (!$check_res) {
            throw new UpdateException(['msg' => '当前用户可消费余额不足']);
        }
        //修改数量
        $order->count = $count;
        //处理订单金额
        $order->money = $new_money;
        //处理消费方式
        $order->pay_way = $check_res;
        if (!($order->save())) {
            throw new UpdateException();
        }
    }

    public function changeOrderFoods($params)
    {
        try {
            Db::startTrans();
            $id = $params['id'];
            $detail = json_decode($params['detail'], true);
            if (empty($detail)) {
                throw new ParameterException(['msg' => '订单明细为空或者数据格式错误']);
            }
            $order = OrderT::where('id', $id)->find();
            //检测订单是否可操作
            $count = $order->count;
            $this->checkOrderCanHandel($order->d_id);
            if (!empty($params['count']) && ($params['count'] != $count)) {
                //检测订单修改数量是否合法
                $count = $params['count'];
                $strategy = (new CanteenService())->getStaffConsumptionStrategy($order->c_id, $order->d_id, $order->t_id);
                if (!$strategy) {
                    throw new ParameterException(['msg' => '当前用户消费策略不存在']);
                }
                if ($count > $strategy->ordered_count) {
                    throw new UpdateException(['msg' => '当前用户消费策略不存在']);
                }
            }


            $check_money = $this->checkOrderUpdateMoney($id, $order->u_id, $order->c_id,
                $order->d_id, $order->pay_way, $order->money, $count, $detail);
            $order->pay_way = $check_money['pay_way'];
            $order->money = $check_money['new_money'];
            if (!($order->save())) {
                throw new UpdateException();
            }
            //处理订单明细
            $this->prefixUpdateOrderDetail($id, $detail);
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    private function prefixUpdateOrderDetail($o_id, $new_detail)
    {
        $data_list = [];
        foreach ($new_detail as $k => $v) {
            $menu_id = $v['menu_id'];
            $add_foods = $v['add_foods'];
            $update_foods = $v['update_foods'];
            $cancel_foods = $v['cancel_foods'];
            if (!empty($add_foods)) {
                foreach ($add_foods as $k2 => $v2) {
                    $data = [
                        'm_id' => $menu_id,
                        'f_id' => $v2['food_id'],
                        'price' => $v2['price'],
                        'count' => $v2['count'],
                        'o_id' => $o_id,
                        'state' => CommonEnum::STATE_IS_OK,
                    ];
                    array_push($data_list, $data);
                }
            }

            if (!empty($update_foods)) {
                foreach ($update_foods as $k3 => $v3) {
                    $data = [
                        'id' => $v3['detail_id'],
                        'count' => $v3['count'],
                    ];
                    array_push($data_list, $data);
                }
            }

            if (strlen($cancel_foods)) {
                $cancel_arr = explode(',', $cancel_foods);
                foreach ($cancel_arr as $k4 => $v4) {
                    $data = [
                        'id' => $v4,
                        'state' => CommonEnum::STATE_IS_FAIL,
                    ];
                    array_push($data_list, $data);
                }
            }


        }
        $res = (new OrderDetailT())->saveAll($data_list);
        if (!$res) {
            throw new UpdateException(['msg' => '更新订单明细失败']);
        }

    }

    private function checkOrderUpdateMoney($o_id, $u_id, $canteen_id, $dinner_id, $pay_way,
                                           $old_money, $count, $new_detail)
    {
        //获取餐次下所有菜品类别
        $menus = (new MenuService())->dinnerMenus($dinner_id);
        if (!count($menus)) {
            throw new ParameterException(['msg' => '指定餐次未设置菜单信息']);
        }

        $new_money = 0;
        foreach ($new_detail as $k => $v) {
            $menu_id = $v['menu_id'];
            $add_foods = $v['add_foods'];
            $update_foods = $v['update_foods'];
            $cancel_foods = $v['cancel_foods'];

            $old_detail = OrderDetailT::orderDetail($o_id, $menu_id);
            $check_data = $this->checkOrderDetailUpdate($update_foods, $old_detail);
            $check_data = $this->checkOrderDetailCancel($cancel_foods, $check_data);
            $check_data = $this->checkOrderDetailAdd($add_foods, $check_data);
            $menu = $this->getMenuInfo($menus, $menu_id);
            if (empty($menu)) {
                throw new ParameterException(['msg' => '菜品类别id错误']);
            }
            if (($menu['status'] == MenuEnum::FIXED) && ($menu['count'] < count($check_data))) {
                throw new SaveException(['msg' => '选菜失败,菜品类别：<' . $menu['category'] . '> 选菜数量超过最大值：' . $menu['count']]);
            }

            foreach ($check_data as $k3 => $v3) {
                $new_money += $v3['price'] * $v3['count'];
            }
        }
        $new_money = $new_money * $count;
        if ($new_money > $old_money) {
            $pay_way = $this->checkBalance($u_id, $canteen_id, $new_money - $old_money);
        }
        return [
            'new_money' => $new_money,
            'pay_way' => $pay_way
        ];
    }

    private function checkOrderDetailCancel($cancel_foods, $check_data)
    {

        if (strlen($cancel_foods)) {
            $cancel_arr = explode(',', $cancel_foods);
            foreach ($check_data as $k => $v) {
                if (in_array($v['id'], $cancel_arr)) {
                    unset($check_data[$k]);
                }
            }
        }
        return $check_data;
    }

    private function checkOrderDetailUpdate($update_foods, $check_date)
    {
        if (empty($update_foods)) {
            return $check_date;
        }
        foreach ($check_date as $k => $v) {
            foreach ($update_foods as $k2 => $v2) {
                if ($v['id'] == $v2['detail_id']) {
                    $check_date[$k]['count'] = $v2['count'];
                }
            }
        }
        return $check_date;
    }

    private function checkOrderDetailAdd($add_foods, $check_data)
    {
        if (empty($add_foods)) {
            return $check_data;
        }
        foreach ($add_foods as $k => $v) {
            $data = [
                'f_id' => $v['food_id'],
                'price' => $v['price'],
                'count' => $v['count'],
            ];
            array_push($check_data, $data);
        }
        return $check_data;
    }

    public function personalChoiceInfo($id)
    {
        $info = OrderT:: personalChoiceInfo($id);
        return $info;
    }
}
