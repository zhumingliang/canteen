<?php
/**
 * Created by PhpStorm.
 * User: 明良
 * Date: 2019/9/5
 * Time: 11:14
 */

namespace app\api\service;


use app\api\model\CanteenAccountT;
use app\api\model\ChoiceDetailT;
use app\api\model\DinnerT;
use app\api\model\OnlineOrderingT;
use app\api\model\OrderingV;
use app\api\model\PersonalChoiceT;
use app\lib\enum\CommonEnum;
use app\lib\enum\MenuEnum;
use app\lib\enum\OrderEnum;
use app\lib\enum\PayEnum;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;
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
            $money = $this->getOrderingMoney($detail);
            $params['money'] = $money;
            $u_id = Token::getCurrentUid();
            //检测订餐时间是否允许
            $this->checkDinner($dinner_id, $ordering_date);

            $canteen_id = Token::getCurrentTokenVar('current_canteen_id');
            $this->checkUserCanOrder($u_id, $dinner_id, $dinner, $ordering_date, $canteen_id, $count, $detail);
            $pay_way = $this->checkBalance($u_id, $canteen_id, $money);
            if (!$pay_way) {
                throw new  SaveException(['msg' => '余额不足，请先充值']);
            }
            //保存订单信息
            $params['order_num'] = makeOrderNo();
            $params['pay_way'] = $pay_way;
            $params['u_id'] = $u_id;
            $params['c_id'] = $canteen_id;
            $params['d_id'] = 6;
            $params['pay'] = CommonEnum::STATE_IS_OK;
            $order = PersonalChoiceT::create($params);
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
                    'f_id' => $v2['food_id'],
                    'price' => $v2['price'],
                    'count' => $v2['count'],
                    'o_id' => $o_id,
                    'state' => CommonEnum::STATE_IS_OK,
                ];
                array_push($data_list, $data);
            }

        }
        $res = (new ChoiceDetailT())->saveAll($data_list);
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
        //检测菜单数据是否合法
        $this->checkMenu($dinner_id, $detail);

    }

    //检测是否在订餐时间内
    public function checkDinner($dinner_id, $ordering_date)
    {
        $dinner = DinnerT::dinnerInfo($dinner_id);
        if (!$dinner) {
            throw new ParameterException(['msg' => '指定餐次未设置']);
        }
        $limit_time = $dinner->limit_time;
        $type = $dinner->type;
        $type_number = $dinner->type_number;
        $expiryDate = $this->prefixExpiryDate($ordering_date, [$type => $type_number]);
        if (strtotime($limit_time) > strtotime($expiryDate)) {
            throw  new  SaveException(['msg' => '超出订餐时间']);
        }
    }

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
            $u_id = 3;//Token::getCurrentUid();
            $canteen_id = 1;//Token::getCurrentTokenVar('current_canteen_id');
            $data = $this->prefixOnlineOrderingData($u_id, $canteen_id, $detail);
            print_r($data);
            return 1;
            $ordering = OnlineOrderingT::create($data);
            if (!$ordering) {
                throw  new SaveException();
            }
            //Db::commit();

        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }

    }

    private function prefixOnlineOrderingData($u_id, $canteen_id, $detail)
    {
        $data_list = [];
        foreach ($detail as $k => $v) {
            $ordering_data = $v['ordering'];
            if (!empty($ordering_data)) {
                foreach ($ordering_data as $k2 => $v2) {
                    $data = [];
                    $data['u_id'] = $u_id;
                    $data['c_id'] = $canteen_id;
                    $data['d_id'] = $v['d_id'];
                    $data['ordering_date'] = $v2['ordering_date'];
                    $data['count'] = $v2['count'];
                    $data['order_num'] = makeOrderNo();
                    $data['money'] = 0;
                    $data['pay_way'] = '';
                    $data['pay'] = CommonEnum::STATE_IS_OK;
                    array_push($data_list, $data);
                }

            }

        }
        return $data_list;

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
        $canteen_id = 1;//Token::getCurrentTokenVar('current_canteen_id');
        $t_id = 1;// Token::getCurrentTokenVar('t_id');
        $dinner = (new CanteenService())->getDinners(6);
        $strategies = (new CanteenService())->staffStrategy(1, $t_id);
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


}

