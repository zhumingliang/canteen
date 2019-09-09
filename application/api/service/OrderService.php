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
            $this->checkUserCanOrder($u_id, $dinner_id, $ordering_date, $canteen_id, $count, $detail);
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
    function checkUserCanOrder($u_id, $dinner_id, $day, $canteen_id, $count, $detail)
    {
        //检测当前时间是否可以订餐
        $this->checkDinner($dinner_id);
        //获取用户指定日期订餐信息
        $record = OrderingV::getRecordForDayOrdering($u_id, $day, $dinner_id);
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

}

