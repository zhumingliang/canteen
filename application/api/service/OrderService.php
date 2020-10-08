<?php
/**
 * Created by PhpStorm.
 * User: 明良
 * Date: 2019/9/5
 * Time: 11:14
 */

namespace app\api\service;


use app\api\model\CanteenAccountT;
use app\api\model\ConsumptionRecordsV;
use app\api\model\DinnerStatisticV;
use app\api\model\DinnerT;
use app\api\model\FoodsStatisticV;
use app\api\model\OrderDetailT;
use app\api\model\OrderHandelT;
use app\api\model\OrderingV;
use app\api\model\OrderParentT;
use app\api\model\OrderSubT;
use app\api\model\OrderT;
use app\api\model\OrderUsersStatisticV;
use app\api\model\OutConfigT;
use app\api\model\PayT;
use app\api\model\RechargeSupplementT;
use app\api\model\ShopOrderingV;
use app\api\model\ShopOrderT;
use app\api\model\SubFoodT;
use app\api\model\UserBalanceV;
use app\api\model\WxRefundT;
use app\lib\enum\CommonEnum;
use app\lib\enum\MenuEnum;
use app\lib\enum\OrderEnum;
use app\lib\enum\PayEnum;
use app\lib\enum\StrategyEnum;
use app\lib\enum\UserEnum;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;
use app\lib\exception\UpdateException;
use app\lib\Num;
use think\Db;
use think\Exception;
use think\Model;


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
            $params['ordering_type'] = OrderEnum::ORDERING_CHOICE;
            $u_id = Token::getCurrentUid();
            $canteen_id = Token::getCurrentTokenVar('current_canteen_id');
            $company_id = Token::getCurrentTokenVar('current_company_id');
            $phone = Token::getCurrentPhone();
            $staff = (new UserService())->getUserCompanyInfo($phone, $company_id);
            $this->checkEatingOutsider($params['type'], $params['address_id']);
            //获取餐次信息
            $dinner = DinnerT::dinnerInfo($dinner_id);
            //检测该餐次订餐时间是否允许
            $this->checkDinnerForPersonalChoice($dinner, $ordering_date);
            $delivery_fee = $this->checkUserOutsider($params['type'], $canteen_id);
            $consumptionType = $this->getConsumptionType($phone, $company_id, $canteen_id, $dinner_id);
            //检测用户是否可以订餐并返回订单金额
            $strategyMoney = $this->checkUserCanOrder($dinner, $ordering_date, $canteen_id, $count, $detail, 'person_choice', $consumptionType);
            $orderMoney = $strategyMoney['strategyMoney'];

            if ($consumptionType == StrategyEnum::CONSUMPTION_TIMES_ONE) {
                $orderId = $this->handleConsumptionTimesOne($u_id, $ordering_date, $company_id, $canteen_id,
                    $count, $detail, $delivery_fee, $dinner, $params, $staff, $phone, $orderMoney);
            } elseif ($consumptionType == StrategyEnum::CONSUMPTION_TIMES_MORE) {
                $orderId = $this->handleConsumptionTimesMore($u_id, $ordering_date, $company_id, $canteen_id,
                    $count, $detail, $delivery_fee, $dinner, $params, $staff, $phone, $orderMoney);

            } else {
                throw new ParameterException(['msg' => '消费策略扣费模式异常']);
            }
            if ($params['type'] == OrderEnum::EAT_OUTSIDER && !empty($params['address_id'])) {
                (new AddressService())->prefixAddressDefault($params['address_id']);
            }
            Db::commit();
            return [
                'id' => $orderId
            ];
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }

    }


    private function handleConsumptionTimesOne($u_id, $ordering_date, $company_id, $canteen_id,
                                               $count, $detail, $delivery_fee,
                                               $dinner, $params, $staff, $phone, $orderMoney)
    {

        $checkMoney = $orderMoney['money'] + $orderMoney['sub_money'] + $delivery_fee;
        $pay_way = $this->checkBalance($u_id, $canteen_id, $checkMoney);
        if (!$pay_way) {
            throw new SaveException(['errorCode' => 49000, 'msg' => '余额不足']);
        }

        //保存订单信息
        $params['order_num'] = makeOrderNo();
        $params['pay_way'] = $pay_way;
        $params['u_id'] = $u_id;
        $params['c_id'] = $canteen_id;
        $params['d_id'] = $dinner->id;
        $params['pay'] = PayEnum::PAY_SUCCESS;
        $params['delivery_fee'] = $delivery_fee;
        $params['outsider'] = UserEnum::INSIDE;
        $params['money'] = $orderMoney['money'];
        $params['sub_money'] = $orderMoney['sub_money'];
        $params['consumption_type'] = $orderMoney['consumption_type'];
        $params['meal_money'] = $orderMoney['meal_money'];
        $params['meal_sub_money'] = $orderMoney['meal_sub_money'];
        $params['no_meal_money'] = $orderMoney['no_meal_money'];
        $params['no_meal_sub_money'] = $orderMoney['no_meal_sub_money'];
        $params['company_id'] = $company_id;
        $params['ordering_date'] = $ordering_date;
        $params['count'] = $count;
        $params['staff_type_id'] = $staff->t_id;
        $params['department_id'] = $staff->d_id;
        $params['staff_id'] = $staff->id;
        $params['phone'] = $phone;
        $params['fixed'] = $dinner->fixed;
        $params['state'] = CommonEnum::STATE_IS_OK;
        $params['receive'] = Token::getCurrentTokenVar('outsiders');
        $order = OrderT::create($params);
        if (!$order) {
            throw new SaveException(['msg' => '生成订单失败']);
        }
        $this->prefixDetail($detail, $order->id);
        return $order->id;
    }


    private function handleConsumptionTimesMore($u_id, $ordering_date, $company_id,
                                                $canteen_id, $count,
                                                $detail, $delivery_fee,
                                                $dinner, $params, $staff, $phone, $orderMoney)
    {
        $money = 0;
        foreach ($orderMoney as $k => $v) {
            $money += ($v['money'] + $v['sub_money']);
        }
        $checkMoney = $money + $delivery_fee;
        $pay_way = $this->checkBalance($u_id, $canteen_id, $checkMoney, $company_id, $phone);
        if (!$pay_way) {
            throw new SaveException(['errorCode' => 49000, 'msg' => '余额不足']);
        }
        $orderData = [
            'u_id' => $u_id,
            'fixed' => $dinner->fixed,
            'dinner_id' => $dinner->id,
            'canteen_id' => $canteen_id,
            'money' => $money,
            'sub_money' => 0,
            'phone' => $phone,
            'count' => $count,
            'type' => $params['type'],
            'ordering_date' => $ordering_date,
            'state' => CommonEnum::STATE_IS_OK,
            'order_num' => makeOrderNo(),
            'address_id' => $params['address_id'],
            'pay' => PayEnum::PAY_SUCCESS,
            'pay_way' => $pay_way,
            'delivery_fee' => $delivery_fee,
            'ordering_type' => 'personal_choice',
            'staff_type_id' => $staff->t_id,
            'department_id' => $staff->d_id,
            'staff_id' => $staff->id,
            'company_id' => $company_id,
            'booking' => CommonEnum::STATE_IS_OK,
            'remark' => $params['remark'],
            'outsider' => UserEnum::INSIDE
        ];
        $orderParent = OrderParentT::create($orderData);
        if (!$orderParent) {
            throw new SaveException(['msg' => '新增总订单失败']);
        }
        $orderId = $orderParent->id;
        //处理订单菜品信息
        $this->prefixDetail($detail, $orderId, 'more');
        //处理子订单信息
        $subOrderDataList = [];
        foreach ($orderMoney as $k => $v) {
            $data = [
                'order_id' => $orderId,
                'ordering_date' => $ordering_date,
                'consumption_sort' => $v['number'],
                'count' => 1,
                'order_sort' => $v['order_sort'],
                'money' => $v['money'],
                'order_num' => makeOrderNo(),
                'sub_money' => $v['sub_money'],
                'consumption_type' => $v['consumption_type'],
                'meal_money' => $v['meal_money'],
                'meal_sub_money' => $v['meal_sub_money'],
                'no_meal_money' => $v['no_meal_money'],
                'no_meal_sub_money' => $v['no_meal_sub_money'],
                'ordering_type' => 'personal_choice',
            ];
            array_push($subOrderDataList, $data);
        }
        $list = (new OrderSubT())->saveAll($subOrderDataList);
        if (!$list) {
            throw new SaveException(['msg' => '新增子订单失败']);
        }
        return $orderId;
    }


    private function checkOrderParentExits($ordering_date, $canteen_id, $dinner_id, $phone)
    {
        $order = OrderParentT::orderInfo($ordering_date, $canteen_id, $dinner_id, $phone);
        if (!$order) {
            return 0;
        }
        return $order->id;

    }

    public
    function getOrderMoney($params)
    {
        $dinner_id = $params['dinner_id'];
        $ordering_date = $params['ordering_date'];
        $count = $params['count'];
        $detail = json_decode($params['detail'], true);
        $dinner = DinnerT::dinnerInfo($dinner_id);
        $canteen_id = Token::getCurrentTokenVar('current_canteen_id');
        $phone = Token::getCurrentTokenVar('phone');
        $company_id = Token::getCurrentTokenVar('current_company_id');
        $consumptionType = $this->getConsumptionType($phone, $company_id, $canteen_id, $dinner_id);
        //检测用户是否可以订餐并返回订单金额
        $orderMoney = $this->checkUserCanOrder($dinner, $ordering_date, $canteen_id, $count, $detail, 'person_choice', $consumptionType);
        //$orderMoney = $strategyMoney['strategyMoney'];
        $delivery_fee = $this->checkUserOutsider($params['type'], $canteen_id);
        // $orderMoney = $this->checkUserCanOrder($dinner, $ordering_date, $canteen_id, $count, $detail, "person_choice", $ordering_type);
        $orderMoney['delivery_fee'] = $delivery_fee;
        return $orderMoney;

    }

    public
    function personChoiceOutsider($params)
    {
        try {
            Db::startTrans();
            $dinner_id = $params['dinner_id'];
            $ordering_date = $params['ordering_date'];
            $type = $params['type'];
            $count = $params['count'];
            $address_id = empty($params['address_id']) ? 0 : $params['address_id'];
            $remark = empty($params['remark']) ? 0 : $params['remark'];
            $openid = Token::getCurrentOpenid();
            $username = Token::getCurrentTokenVar('nickName');
            $detail = json_decode($params['detail'], true);
            unset($params['detail']);
            $params['ordering_type'] = OrderEnum::ORDERING_CHOICE;
            $u_id = Token::getCurrentUid();
            $canteen_id = Token::getCurrentTokenVar('current_canteen_id');
            $company_id = Token::getCurrentTokenVar('current_company_id');
            $phone = Token::getCurrentPhone();
            //检测配送费用
            $delivery_fee = $this->checkUserOutsider($params['type'], $canteen_id);

            //获取餐次信息
            $dinner = DinnerT::dinnerInfo($dinner_id);
            //检测该餐次订餐时间是否允许
            $this->checkDinnerForPersonalChoice($dinner, $ordering_date);
            $consumptionType = $this->getConsumptionType($phone, $company_id, $canteen_id, $dinner_id);
            //获取订单金额
            $orderMoney = $this->checkOutsiderOrderMoney($dinner_id, $detail);

            if ($consumptionType == StrategyEnum::CONSUMPTION_TIMES_ONE) {
                $orderId = $this->handleOutsiderConsumptionTimesOne($u_id, $type,
                    $company_id, $canteen_id, $phone, $dinner_id,
                    $dinner->fixed, $delivery_fee, $orderMoney, $count, $detail, $params);
            } else {
                $orderId = $this->handleOutsiderConsumptionTimesMore($u_id, $dinner_id, $canteen_id, $phone, $count, $type
                    , $ordering_date, $delivery_fee, $company_id, $address_id, $remark, $orderMoney, $detail);

            }
            if ($params['type'] == OrderEnum::EAT_OUTSIDER && !empty($params['address_id'])) {
                (new AddressService())->prefixAddressDefault($params['address_id']);
            }
            //生成微信支付订单
            $payMoney = $orderMoney * $count + $delivery_fee;
            $payOrder = $this->savePayOrder($orderId, $company_id, $openid, $u_id, $payMoney, $phone, $username, 'more');
            Db::commit();
            return $payOrder;
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }

    }

    private function handleOutsiderConsumptionTimesMore($u_id, $dinnerId, $canteen_id, $phone, $count, $type
        , $ordering_date, $delivery_fee, $company_id, $address_id, $remark, $orderMoney, $detail)
    {
        $orderData = [
            'u_id' => $u_id,
            'd_id' => $dinnerId,
            'c_id' => $canteen_id,
            'phone' => $phone,
            'money' => $orderMoney * $count,
            'count' => $count,
            'type' => $type,
            'ordering_date' => $ordering_date,
            'state' => CommonEnum::STATE_IS_OK,
            'order_num' => makeOrderNo(),
            'address_id' => $address_id,
            'pay' => PayEnum::PAY_FAIL,
            'pay_way' => PayEnum::PAY_WEIXIN,
            'delivery_fee' => $delivery_fee,
            'ordering_type' => 'personal_choice',
            'company_id' => $company_id,
            'booking' => CommonEnum::STATE_IS_OK,
            'remark' => $remark,
            'outsider' => UserEnum::OUTSIDE
        ];
        $orderParent = OrderParentT::create($orderData);
        if (!$orderParent) {
            throw new SaveException(['msg' => '新增总订单失败']);
        }
        $orderId = $orderParent->id;
        $subOrderDataList = [];
        //处理子订单
        for ($i = 0; $i < $count; $i++) {
            $data = [
                'order_id' => $orderId,
                'ordering_date' => $ordering_date,
                'consumption_sort' => $i + 1,
                'order_sort' => $i + 1,
                'money' => $orderMoney,
                'sub_money' => 0,
                'consumption_type' => 'ordering_meals',
                'meal_money' => $orderMoney,
                'meal_sub_money' => 0,
                'no_meal_money' => 0,
                'no_meal_sub_money' => 0,
                'ordering_type' => 'personal_choice',
            ];
            array_push($subOrderDataList, $data);
        }
        $list = (new OrderSubT())->saveAll($subOrderDataList);
        if (!$list) {
            throw new SaveException(['msg' => '新增子订单失败']);
        }
        $this->prefixDetail($detail, $orderId, 'more');
        return $orderId;
    }

    private function handleOutsiderConsumptionTimesOne($u_id, $type, $company_id, $canteen_id, $phone, $dinner_id,
                                                       $orderMoneyFixed, $delivery_fee
        , $orderMoney, $count, $detail, $params)
    {
        //保存订单信息
        $params['order_num'] = makeOrderNo();
        $params['type'] = $type;
        $params['pay_way'] = PayEnum::PAY_WEIXIN;;
        $params['u_id'] = $u_id;
        $params['c_id'] = $canteen_id;
        $params['d_id'] = $dinner_id;
        $params['pay'] = PayEnum::PAY_FAIL;
        $params['delivery_fee'] = $delivery_fee;
        $params['outsider'] = UserEnum::OUTSIDE;
        $params['money'] = $orderMoney * $count;
        $params['sub_money'] = 0;
        $params['consumption_type'] = 'ordering_meals';
        $params['meal_money'] = $orderMoney;
        $params['meal_sub_money'] = 0;
        $params['no_meal_money'] = 0;
        $params['no_meal_sub_money'] = 0;
        $params['pay'] = PayEnum::PAY_FAIL;
        $params['company_id'] = $company_id;
        $params['phone'] = $phone;
        $params['fixed'] = $orderMoneyFixed;
        $params['state'] = CommonEnum::STATE_IS_OK;
        $params['receive'] = CommonEnum::STATE_IS_FAIL;
        $order = OrderT::create($params);
        if (!$order) {
            throw new SaveException(['msg' => '生成订单失败']);
        }
        $orderId = $order->id;
        $this->prefixDetail($detail, $orderId, 'one');
        return $orderId;
    }

    public
    function savePayOrder($order_id, $company_id, $openid, $u_id, $money, $phone, $username, $times = 'one')
    {
        $data = [
            'openid' => $openid,
            'company_id' => $company_id,
            'u_id' => $u_id,
            'order_num' => makeOrderNo(),
            'money' => $money,
            'status' => 'paid_fail',
            'method_id' => PayEnum::PAY_METHOD_WX,
            'order_id' => $order_id,
            'type' => 'canteen',
            'phone' => $phone,
            'times' => $times,
            'username' => $username,
            'outsider' => UserEnum::OUTSIDE

        ];
        $order = PayT::create($data);
        if (!$order) {
            throw new SaveException();
        }
        return [
            'id' => $order->id
        ];
    }


    private
    function checkUserOutsider($type, $canteen_id)
    {

        $outsiders = Token::getCurrentTokenVar('outsiders');
        if ($type == OrderEnum::EAT_OUTSIDER) {
            $outConfig = OutConfigT::where('canteen_id', $canteen_id)
                ->find();
            if ($outConfig) {
                return $outsiders == UserEnum::INSIDE ? $outConfig->in_fee : $outConfig->out_fee;

            }

        }
        return 0;
    }

    public
    function prefixDetail($detail, $o_id, $consumptionTimes = 'one')
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
                    'name' => $v2['name'],
                    'o_id' => $o_id,
                    'state' => CommonEnum::STATE_IS_OK,
                ];
                array_push($data_list, $data);
            }
        }
        if ($consumptionTimes == 'one') {
            $res = (new OrderDetailT())->saveAll($data_list);
        } else {
            $res = (new SubFoodT())->saveAll($data_list);
        }
        if (!$res) {
            throw new SaveException(['msg' => '存储订餐明细失败']);
        }
    }

    public
    function checkBalance($u_id, $canteen_id, $money, $company_id = '', $phone = '')
    {
        $company_id = empty($company_id) ? Token::getCurrentTokenVar('current_company_id') : $company_id;
        $phone = empty($phone) ? Token::getCurrentTokenVar('phone') : $phone;
        $balance = (new WalletService())->getUserBalance($company_id, $phone);
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
    function checkBalanceTest($canteen_id, $money, $company_id, $phone)
    {
        $balance = (new WalletService())->getUserBalance($company_id, $phone);
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
    function checkUserCanOrder($dinner, $day, $canteen_id, $count,
                               $detail,
                               $ordering_type = "person_choice", $consumptionType = 1)
    {
        $phone = Token::getCurrentPhone();
        $company_id = Token::getCurrentTokenVar('current_company_id');
        //获取用户指定日期订餐数量
        $orders = OrderingV::getRecordForDayOrderingByPhone($day, $dinner->name, $phone);
        $orderedCount = array_sum(array_column($orders, 'count'));
        $this->checkOrderedAnotherCanteen($canteen_id, $orders);
        if ($consumptionType == StrategyEnum::CONSUMPTION_TIMES_ONE) {
            $consumptionCount = count($orders);
        } else {
            $consumptionCount = $orderedCount;
        }

        //检测消费策略
        $t_id = (new UserService())->getUserStaffTypeByPhone($phone, $company_id);
        //获取指定用户消费策略
        $strategies = (new CanteenService())->getStaffConsumptionStrategy($canteen_id, $dinner->id, $t_id);
        if (!$strategies) {
            throw new ParameterException(['msg' => '消费策略设置异常']);
        }

        if ($orderedCount + $count > $strategies->ordered_count) {
            throw new UpdateException(['msg' => '超出最大订餐数量，不能预定']);
        }

        $consumptionType = $strategies->consumption_type;
        //检测打卡模式：一次性消费/逐次消费
        if ($consumptionType == StrategyEnum::CONSUMPTION_TIMES_ONE) {
            $strategyMoney = $this->checkConsumptionStrategy($strategies, $count, $consumptionCount);
        } else {
            $strategyMoney = $this->checkConsumptionStrategyTimesMore($strategies, $count, $consumptionCount, $ordering_type);
        }
        $orderMoneyFixed = $dinner->fixed;
        if ($ordering_type == "person_choice") {
            //检测菜单数据是否合法并返回订单金额
            $detailMoney = $this->checkMenu($dinner->id, $detail);
            if ($consumptionType == StrategyEnum::CONSUMPTION_TIMES_ONE) {
                if ($orderMoneyFixed == CommonEnum::STATE_IS_FAIL) {
                    $strategyMoney['money'] = $detailMoney * $count;
                    if ($strategyMoney['meal_sub_money'] > $strategyMoney['no_meal_sub_money']) {
                        $strategyMoney['sub_money'] = $strategyMoney['meal_sub_money'];
                        $strategyMoney['consumption_type'] = 'ordering_meals';
                    } else {
                        $strategyMoney['sub_money'] = $strategyMoney['no_meal_sub_money'];
                        $strategyMoney['consumption_type'] = 'no_meals_ordered';
                    }
                }
            } else {
                if ($orderMoneyFixed == CommonEnum::STATE_IS_FAIL) {
                    foreach ($strategyMoney as $k => $v) {
                        $strategyMoney[$k]['meal_money'] = $detailMoney;
                        $strategyMoney[$k]['money'] = $detailMoney;
                        if ($v['meal_sub_money'] > $v['no_meal_sub_money']) {
                            $strategyMoney[$k]['sub_money'] = $v['meal_sub_money'];
                            $strategyMoney[$k]['consumption_type'] = 'ordering_meals';
                        } else {
                            $strategyMoney[$k]['sub_money'] = $v['no_meal_sub_money'];
                            $strategyMoney[$k]['consumption_type'] = 'no_meals_ordered';
                        }
                    }
                }
            }
        }
        $times = $consumptionType > StrategyEnum::CONSUMPTION_TIMES_ONE ? 'more' : 'one';
        return [
            'times' => $times,
            'consumption_count' => $consumptionCount,
            'strategyMoney' => $strategyMoney
        ];
    }


    public
    function checkUserCanOrderMore($dinner, $day, $canteen_id, $count, $detail, $ordering_type = "person_choice")
    {
        $phone = Token::getCurrentPhone();
        $company_id = Token::getCurrentTokenVar('current_company_id');
        //获取用户指定日期订餐数量
        $orders = OrderingV::getRecordForDayOrderingByPhone($day, $dinner->name, $phone);
        $consumptionCount = $this->checkOrderedAnotherCanteen($canteen_id, $orders);
        //检测消费策略
        $t_id = (new UserService())->getUserStaffTypeByPhone($phone, $company_id);
        //获取指定用户消费策略
        $strategies = (new CanteenService())->getStaffConsumptionStrategy($canteen_id, $dinner->id, $t_id);
        if (!$strategies) {
            throw new ParameterException(['msg' => '消费策略设置异常']);
        }
        $consumptionType = $strategies->consumption_type;
        //检测打卡模式：一次性消费/逐次消费
        if ($consumptionType == StrategyEnum::CONSUMPTION_TIMES_ONE) {
            $strategyMoney = $this->checkConsumptionStrategy($strategies, $count, $consumptionCount);
        } else {
            $strategyMoney = $this->checkConsumptionStrategyTimesMore($strategies, $count, $consumptionCount);
        }
        $orderMoneyFixed = $dinner->fixed;
        if ($ordering_type == "person_choice") {
            //检测菜单数据是否合法并返回订单金额
            $detailMoney = $this->checkMenu($dinner->id, $detail);
            if ($orderMoneyFixed == CommonEnum::STATE_IS_FAIL) {
                if ($consumptionType == StrategyEnum::CONSUMPTION_TIMES_ONE) {
                    $strategyMoney['money'] = $detailMoney * $count;
                } else {
                    foreach ($strategyMoney as $k => $v) {
                        $strategyMoney[$k]['money'] = $detailMoney;
                    }
                }
            }
        }
        $times = $consumptionType > StrategyEnum::CONSUMPTION_TIMES_ONE ? 'more' : 'one';
        $strategyMoney['times'] = $times;
        $strategyMoney['consumption_count'] = $consumptionCount;
        return $strategyMoney;
    }


    public
    function checkUserCanOnlineOrderMore($strategies, $phone, $dinner, $day, $canteen_id, $count)
    {
        //获取用户指定日期订餐数量
        $orders = OrderingV::getRecordForDayOrderingByPhone($day, $dinner->name, $phone);
        $this->checkOrderedAnotherCanteen($canteen_id, $orders);
        $consumptionCount = array_sum(array_column($orders, 'count'));
        $strategyMoney = $this->checkConsumptionStrategyTimesMore($strategies, $count, $consumptionCount);
        return $strategyMoney;

    }


    private
    function getConsumptionType($phone, $company_id, $canteen_id, $dinner_id)
    {
        $t_id = (new UserService())->getUserStaffTypeByPhone($phone, $company_id);
        //获取指定用户消费策略
        $strategies = (new CanteenService())->getStaffConsumptionStrategy($canteen_id, $dinner_id, $t_id);
        if (!$strategies) {
            throw new ParameterException(['msg' => '消费策略设置异常']);
        }
        $consumptionType = $strategies->consumption_type;
        return $consumptionType;
    }

    private
    function checkOrderedAnotherCanteen($canteen_id, $orders)
    {
        $count = count($orders);
        if ($count) {
            foreach ($orders as $k => $v) {
                if ($canteen_id != $v['c_id']) {
                    throw  new ParameterException(['msg' => '当前餐次已经在饭堂：' . $v['canteen'] . '中预定']);
                }
            }
        }
        return $count;

    }


    public
    function checkOutsiderOrderMoney($dinner_id, $detail)
    {

        $detailMoney = $this->checkMenu($dinner_id, $detail);
        return $detailMoney;

    }

    public
    function checkUserCanOrderForOnline($canteen_id, $dinner, $day, $count, $strategies, $phone = '')
    {

        //检测是否可以订餐
        $this->checkOrderCanHandel($dinner->id, $day, $dinner);
        //获取用户指定日期订餐数量
        if (empty($phone)) {
            $phone = Token::getCurrentPhone();
        }
        $orders = OrderingV::getRecordForDayOrderingByPhone($day, $dinner->name, $phone);
        $consumptionCount = $this->checkOrderedAnotherCanteen($canteen_id, $orders);
        //获取指定用户消费策略
        $strategyMoney = $this->checkConsumptionStrategy($strategies, $count, $consumptionCount);
        return $strategyMoney;

    }


//检测是否在订餐时间内
    public
    function checkDinnerForPersonalChoice($dinner, $ordering_date)
    {
        if (!$dinner) {
            throw new ParameterException(['msg' => '指定餐次未设置']);
        }
        $type = $dinner->type;
        if ($type == 'week') {
            throw new ParameterException(['msg' => '当前餐次需批量订餐，请使用线上订餐功能订餐']);
        }
        $limit_time = $dinner->limit_time;
        $type_number = $dinner->type_number;
        $limit_time = $ordering_date . ' ' . $limit_time;
        $expiryDate = $this->prefixExpiryDate($limit_time, [$type => $type_number], '-');
        if (time() > strtotime($expiryDate)) {
            throw  new  SaveException(['msg' => '超出订餐时间']);
        }
    }


    private
    function checkConsumptionStrategy($strategies, $orderCount, $consumptionCount)
    {
        if ($orderCount > $strategies->ordered_count) {
            throw new SaveException(['msg' => '订餐数量超过最大订餐数量，最大订餐数量为：' . $strategies->ordered_count]);
        }
        if ($consumptionCount >= $strategies->consumption_count) {
            throw new SaveException(['msg' => '消费次数已达到上限，最大消费次数为：' . $strategies->consumption_count]);
        }
        $detail = $strategies->detail;
        if (empty($detail)) {
            throw new ParameterException(['msg' => "消费策略设置异常"]);
        }
        //获取消费策略中：订餐未就餐的标准金额和附加金额
        $returnMoney = [];
        $no_meal_money = 0;
        $no_meal_sub_money = 0;
        $meal_money = 0;
        $meal_sub_money = 0;
        foreach ($detail as $k => $v) {
            if (($consumptionCount + 1) == $v['number']) {
                $strategy = $v['strategy'];
                foreach ($strategy as $k2 => $v2) {
                    if ($v2['status'] == "no_meals_ordered") {
                        $no_meal_money = $v2['money'] * $orderCount;
                        $no_meal_sub_money = $v2['sub_money'] * $orderCount;
                    } else if ($v2['status'] == "ordering_meals") {
                        $meal_money = $v2['money'] * $orderCount;
                        $meal_sub_money = $v2['sub_money'] * $orderCount;
                    }
                }

                $returnMoney['meal_money'] = $meal_money;
                $returnMoney['meal_sub_money'] = $meal_sub_money;
                $returnMoney['no_meal_money'] = $no_meal_money;
                $returnMoney['no_meal_sub_money'] = $no_meal_sub_money;
                if (($no_meal_money + $no_meal_sub_money) >= ($meal_money + $meal_sub_money)) {
                    $returnMoney['consumption_type'] = 'no_meals_ordered';
                    $returnMoney['money'] = $no_meal_money;
                    $returnMoney['sub_money'] = $no_meal_sub_money;
                } else {
                    $returnMoney['consumption_type'] = 'ordering_meals';
                    $returnMoney['money'] = $meal_money;
                    $returnMoney['sub_money'] = $meal_sub_money;
                }
                break;
            }
        }

        return $returnMoney;
    }

    private
    function checkConsumptionStrategyTimesMore($strategies, $orderCount, $consumptionCount)
    {
        if ($orderCount > $strategies->ordered_count) {
            throw new SaveException(['msg' => '订餐数量超过最大订餐数量，最大订餐数量为：' . $strategies->ordered_count]);
        }
        if ($consumptionCount >= $strategies->consumption_count) {
            throw new SaveException(['msg' => '消费次数已达到上限，最大消费次数为：' . $strategies->consumption_count]);
        }
        $detail = $strategies->detail;
        if (empty($detail)) {
            throw new ParameterException(['msg' => "消费策略设置异常"]);
        }
        //获取消费策略中：订餐未就餐的标准金额和附加金额
        $returnMoneyList = [];
        $i = 1;
        foreach ($detail as $k => $v) {
            $returnMoney = [];
            $no_meal_money = 0;
            $no_meal_sub_money = 0;
            $meal_money = 0;
            $meal_sub_money = 0;
            if ($i > $orderCount) {
                break;
            }

            if (($consumptionCount + $i) == $v['number']) {
                $strategy = $v['strategy'];
                foreach ($strategy as $k2 => $v2) {
                    if ($v2['status'] == "no_meals_ordered") {
                        $no_meal_money = $v2['money'];
                        $no_meal_sub_money = $v2['sub_money'];
                    } else if ($v2['status'] == "ordering_meals") {
                        $meal_money = $v2['money'];
                        $meal_sub_money = $v2['sub_money'];
                    }
                }
                $returnMoney['number'] = $consumptionCount + $i;
                $returnMoney['order_sort'] = $i;
                $returnMoney['meal_money'] = $meal_money;
                $returnMoney['meal_sub_money'] = $meal_sub_money;
                $returnMoney['no_meal_money'] = $no_meal_money;
                $returnMoney['no_meal_sub_money'] = $no_meal_sub_money;

                if (($no_meal_money + $no_meal_sub_money) >= ($meal_money + $meal_sub_money)) {
                    $returnMoney['consumption_type'] = 'no_meals_ordered';
                    $returnMoney['money'] = $no_meal_money;
                    $returnMoney['sub_money'] = $no_meal_sub_money;
                } else {
                    $returnMoney['consumption_type'] = 'ordering_meals';
                    $returnMoney['money'] = $meal_money;
                    $returnMoney['sub_money'] = $meal_sub_money;
                }
                array_push($returnMoneyList, $returnMoney);
                $i++;
            }

        }
        return $returnMoneyList;
    }

    private
    function checkMenu($dinner_id, $detail)
    {
        if (empty($detail)) {
            throw new ParameterException(['msg' => '菜品明细数据格式不对']);
        }
        //获取餐次下所有菜品类别
        $menus = (new MenuService())->dinnerMenus($dinner_id);
        if (!count($menus)) {
            throw new ParameterException(['msg' => '指定餐次未设置菜单信息']);
        }


        $detailMoney = 0;
        foreach ($detail as $k => $v) {
            $menu_id = $v['menu_id'];
            $menu = $this->getMenuInfo($menus, $menu_id);
            if (empty($menu)) {
                throw new ParameterException(['msg' => '菜品类别id错误']);
            }
            if (($menu['status'] == MenuEnum::FIXED) && ($menu['count'] < count($v['foods']))) {
                throw new SaveException(['msg' => '选菜失败,菜品类别：<' . $menu['category'] . '> 选菜数量超过最大值：' . $menu['count']]);
            }

            $foods = $v['foods'];
            foreach ($foods as $k2 => $v2) {
                $detailMoney += $v2['price'] * $v2['count'];
            }
        }
        return $detailMoney;
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
    public
    function orderingOnline($address_id, $type, $detail)
    {
        try {
            Db::startTrans();
            $this->checkEatingOutsider($type, $address_id);
            $detail = json_decode($detail, true);
            if (empty($detail)) {
                throw new ParameterException(['msg' => '订餐数据格式错误']);
            }
            $u_id = Token::getCurrentUid();
            $canteen_id = Token::getCurrentTokenVar('current_canteen_id');
            $company_id = Token::getCurrentTokenVar('current_company_id');
            $phone = Token::getCurrentPhone();
            $staff = (new UserService())->getUserCompanyInfo($phone, $company_id);
            $staff_type_id = $staff->t_id;
            $department_id = $staff->d_id;
            $staff_id = $staff->id;
            $delivery_fee = $this->checkUserOutsider($type, $canteen_id);
            //获取饭堂消费策略设置-检测消费模式
            $strategies = (new CanteenService())->getStaffAllConsumptionStrategy($canteen_id, $staff_type_id);
            if (empty($strategies)) {
                throw new ParameterException(['msg' => '消费策略未设置']);
            }
            $consumptionType = $strategies[0]['consumption_type'];
            if ($consumptionType == StrategyEnum::CONSUMPTION_TIMES_ONE) {
                $this->handleOnlineConsumptionTimesOne($address_id, $type, $u_id, $canteen_id, $detail, $delivery_fee, $strategies,
                    $company_id, $phone, $staff_type_id, $department_id, $staff_id);
            } else if ($consumptionType == StrategyEnum::CONSUMPTION_TIMES_MORE) {
                $this->handleOnlineConsumptionTimesMore($detail, $strategies, $company_id, $canteen_id, $phone, $u_id,
                    $type, $address_id, $delivery_fee, $staff_type_id, $department_id,
                    $staff_id, '');
            } else {
                throw new ParameterException(['msg' => "消费策略中扣费类型异常"]);
            }
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }

    }

    private function handleOnlineConsumptionTimesOne($address_id, $type, $u_id, $canteen_id, $detail, $delivery_fee, $strategies, $company_id, $phone, $staff_type_id, $department_id, $staff_id)
    {
        $data = $this->prefixOnlineOrderingData($address_id, $type, $u_id, $canteen_id, $detail, $delivery_fee, $strategies, $company_id, $phone, $staff_type_id, $department_id, $staff_id);
        $money = $data['all_money'];
        $pay_way = $this->checkBalance($u_id, $canteen_id, $money, $company_id, $phone);
        if (!$pay_way) {
            throw new SaveException(['errorCode' => 49000, 'msg' => '余额不足']);
        }
        $list = $this->prefixPayWay($pay_way, $data['list']);
        $ordering = (new OrderT())->saveAll($list);
        if (!$ordering) {
            throw  new SaveException();
        }
    }

    private function handleOnlineConsumptionTimesMore($detail, $strategies, $company_id, $canteen_id, $phone, $u_id,
                                                      $type, $address_id, $delivery_fee, $staff_type_id, $department_id,
                                                      $staff_id, $remark)
    {

        $prefixData = $this->prefixOrderMoneyConsumptionTimesMore($detail, $canteen_id, $strategies, $phone);
        $detail = $prefixData['detail'];
        $allMoney = $prefixData['allMoney'];
        $pay_way = $this->checkBalance($u_id, $canteen_id, $allMoney);
        if (!$pay_way) {
            throw new SaveException(['errorCode' => 49000, 'msg' => '余额不足']);
        }
        foreach ($detail as $k => $v) {
            $ordering_data = $v['ordering'];
            $dinner_id = $v['d_id'];
            //检测该餐次是否在订餐时间范围内
            if (!empty($ordering_data)) {
                foreach ($ordering_data as $k2 => $v2) {

                    $orderId = $this->checkOrderParentExits($v2['ordering_date'], $canteen_id, $dinner_id, $phone);
                    $this->checkOrderCanHandel($v['d_id'], $v2['ordering_date']);

                    if (!$orderId) {
                        //处理总订单：1.检测订餐日期餐次是否已经订餐；2.生成订单
                        $orderData = [
                            'u_id' => $u_id,
                            'dinner_id' => $dinner_id,
                            'canteen_id' => $canteen_id,
                            'phone' => $phone,
                            'count' => $v2['count'],
                            'type' => $type,
                            'ordering_date' => $v2['ordering_date'],
                            'state' => CommonEnum::STATE_IS_OK,
                            'order_num' => makeOrderNo(),
                            'address_id' => $address_id,
                            'pay' => PayEnum::PAY_SUCCESS,
                            'delivery_fee' => $delivery_fee,
                            'ordering_type' => OrderEnum::ORDERING_ONLINE,
                            'staff_type_id' => $staff_type_id,
                            'department_id' => $department_id,
                            'staff_id' => $staff_id,
                            'company_id' => $company_id,
                            'booking' => CommonEnum::STATE_IS_OK,
                            'remark' => $remark,
                            'outsider' => UserEnum::INSIDE,
                            'fixed' => CommonEnum::STATE_IS_OK
                        ];
                        $orderParent = OrderParentT::create($orderData);
                        if (!$orderParent) {
                            throw new SaveException(['msg' => '新增总订单失败']);
                        }
                        $orderId = $orderParent->id;

                    }
                    //处理子订餐
                    $orderMoney = $v2['orderMoney'];
                    $subOrderDataList = [];
                    foreach ($orderMoney as $k => $v) {
                        $data = [
                            'order_id' => $orderId,
                            'ordering_date' => $v2['ordering_date'],
                            'consumption_sort' => $v['number'],
                            'order_sort' => $v['number'],
                            'count' => 1,
                            'money' => $v['money'],
                            'ordering_type' => OrderEnum::ORDERING_ONLINE,
                            'order_num' => makeOrderNo(),
                            'sub_money' => $v['sub_money'],
                            'consumption_type' => $v['consumption_type'],
                            'meal_money' => $v['meal_money'],
                            'meal_sub_money' => $v['meal_sub_money'],
                            'no_meal_money' => $v['no_meal_money'],
                            'no_meal_sub_money' => $v['no_meal_sub_money'],
                        ];
                        $allMoney += ($v['money'] + $v['sub_money']);
                        array_push($subOrderDataList, $data);
                    }
                    $list = (new OrderSubT())->saveAll($subOrderDataList);
                    if (!$list) {
                        throw new SaveException(['msg' => '新增子订单失败']);
                    }
                    //处理总订单金额
                    $this->updateParentOrderMoney($orderId);
                }
            }

        }
    }

    private function prefixOrderMoneyConsumptionTimesMore($detail, $canteen_id, $strategies, $phone)
    {
        $allMoney = 0;
        foreach ($detail as $k => $v) {
            $dinner_id = $v['d_id'];
            $ordering_data = $v['ordering'];
            $dinner = DinnerT::dinnerInfo($dinner_id);
            $strategy = $this->getDinnerConsumptionStrategy($strategies, $dinner_id);
            if (!empty($ordering_data)) {
                foreach ($ordering_data as $k2 => $v2) {
                    //检测是否可以订餐
                    $orderMoney = $this->checkUserCanOnlineOrderMore($strategy, $phone, $dinner,
                        $v2['ordering_date'],
                        $canteen_id, $v2['count']);
                    $detail[$k]['ordering'][$k2]['orderMoney'] = $orderMoney;
                    foreach ($orderMoney as $k => $v) {
                        $allMoney += ($v['money'] + $v['sub_money']);
                    }
                }
            }
        }
        return [
            'detail' => $detail,
            'allMoney' => $allMoney
        ];
    }

    public
    function checkEatingOutsider($type, $address_id)
    {

        if ($type == OrderEnum::EAT_OUTSIDER && (!Num::isPositiveInteger($address_id))) {
            throw new ParameterException(['msg' => '外卖订单，没有选择地址']);
        }
    }

    private
    function prefixPayWay($pay_way, $list)
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
    private
    function prefixOnlineOrderingData($address_id, $type, $u_id, $canteen_id, $detail, $delivery_fee, $strategies, $company_id, $phone, $staff_type_id, $department_id, $staff_id)
    {

        $data_list = [];
        $all_money = 0;

        foreach ($detail as $k => $v) {
            $ordering_data = $v['ordering'];
            $dinner = DinnerT::dinnerInfo($v['d_id']);
            //检测该餐次是否在订餐时间范围内
            $strategy = $this->getDinnerConsumptionStrategy($strategies, $v['d_id']);
            if (empty($strategy)) {
                throw new ParameterException(['msg' => '消费策略不存在']);
            }
            if (!empty($ordering_data)) {
                foreach ($ordering_data as $k2 => $v2) {
                    $checkOrder = $this->checkUserCanOrderForOnline($canteen_id, $dinner,
                        $v2['ordering_date'],
                        $v2['count'], $strategy, $phone);
                    $data = [];
                    $data['u_id'] = $u_id;
                    $data['c_id'] = $canteen_id;
                    $data['d_id'] = $v['d_id'];
                    $data['address_id'] = $address_id;
                    $data['delivery_fee'] = $delivery_fee;
                    $data['type'] = $type;
                    $data['staff_type_id'] = $staff_type_id;
                    $data['department_id'] = $department_id;
                    $data['staff_id'] = $staff_id;
                    $data['company_id'] = $company_id;
                    $data['ordering_date'] = $v2['ordering_date'];
                    $data['count'] = $v2['count'];
                    $data['order_num'] = makeOrderNo();
                    $data['ordering_type'] = OrderEnum::ORDERING_ONLINE;
                    $data['money'] = $checkOrder['money'];
                    $data['sub_money'] = $checkOrder['sub_money'];
                    $data['consumption_type'] = $checkOrder['consumption_type'];
                    $data['meal_money'] = $checkOrder['meal_money'];
                    $data['meal_sub_money'] = $checkOrder['meal_sub_money'];
                    $data['no_meal_money'] = $checkOrder['no_meal_money'];
                    $data['no_meal_sub_money'] = $checkOrder['no_meal_sub_money'];
                    $data['pay_way'] = '';
                    $data['phone'] = $phone;
                    $data['fixed'] = $dinner->fixed;
                    $data['pay'] = 'paid';;
                    $params['state'] = CommonEnum::STATE_IS_OK;
                    $params['receive'] = CommonEnum::STATE_IS_OK;
                    array_push($data_list, $data);
                    $all_money += $data['money'] + $data['sub_money'];
                }

            }

        }
        return [
            'all_money' => $all_money,
            'list' => $data_list
        ];

    }

    private
    function getDinnerConsumptionStrategy($strategies, $dinnerId)
    {
        foreach ($strategies as $k => $v) {
            if ($dinnerId == $v['d_id']) {
                return $strategies[$k];
            }
        }
        return [];

    }

    public
    function checkDinnerOrdered($ordering_date, $dinner_id, $records)
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
    private
    function getStrategyMoneyForOrderingOnline($c_id, $d_id, $t_id)
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
    public
    function userOrdering($consumption_time)
    {
        $phone = Token::getCurrentPhone();
        $orderings = OrderingV::userOrdering($phone, $consumption_time);
        return $orderings;


    }

    /**
     * 线上订餐获取初始化信息
     * 1.餐次信息及订餐时间限制
     * 2.消费策略
     */
    public
    function infoForOnline()
    {
        $canteen_id = Token::getCurrentTokenVar('current_canteen_id');
        $company_id = Token::getCurrentTokenVar('current_company_id');
        $phone = Token::getCurrentPhone();
        $t_id = (new UserService())->getUserStaffTypeByPhone($phone, $company_id);
        $dinner = (new CanteenService())->getDinners($canteen_id);
        $strategies = (new CanteenService())->staffStrategy($canteen_id, $t_id);
        foreach ($dinner as $k => $v) {
            foreach ($strategies as $k2 => $v2) {
                if ($v['id'] == $v2['d_id']) {
                    $dinner[$k]['ordered_count'] = $v2['ordered_count'];
                    $dinner[$k]['consumption_type'] = $v2['consumption_type'];
                    unset($strategies[$k2]);
                }

            }
        }
        return $dinner;
    }

    /**
     * 取消订单
     */
    public
    function orderCancel($id, $consumptionType)
    {
        try {
            Db::startTrans();
            //检测取消订餐操作是否可以执行
            if ($consumptionType == "one") {
                $this->cancelConsumptionTimesOneOrder($id);

            } else {
                $moreIdArr = explode(',', $id);
                $this->cancelParentConsumptionTimeMore($moreIdArr);
            }
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }

    }

    private function cancelConsumptionTimesOneOrder($id)
    {
        $order = OrderT::where('id', $id)->find();
        if (!$order) {
            throw new ParameterException(['msg' => '指定订餐信息不存在']);
        }
        if ($order->used == CommonEnum::STATE_IS_OK) {
            throw new ParameterException(['msg' => '订单已消费，不能取消']);
        }
        $outsider = $order->outsider;
        if ($order->type == OrderEnum::EAT_OUTSIDER
            && $order->receive == CommonEnum::STATE_IS_OK) {
            throw new UpdateException(['msg' => '商家已经接单，不能取消']);
        }
        if ($outsider == UserEnum::INSIDE) {
            $this->checkOrderCanHandel($order->d_id, $order->ordering_date);
        } else {
            //撤回订单
            $this->refundWxOrder($id);
        }
        $userType = Token::getCurrentTokenVar('type');
        if ($userType == "cms") {
            $order->state = OrderEnum::STATE_SHOP_REFUSE;
        } else if ($userType == "official") {
            $order->state = OrderEnum::STATUS_CANCEL;
        }
        $res = $order->save();
        if (!$res) {
            throw new SaveException();
        }
    }

    private function cancelConsumptionTimesMoreOrder($id)
    {
        $order = OrderSubT::where('id', $id)->find();
        if (!$order) {
            throw new ParameterException(['msg' => '指定订餐信息不存在']);
        }
        if ($order->used == CommonEnum::STATE_IS_OK) {
            throw new ParameterException(['msg' => '订单已消费，不能取消']);
        }

        //获取总订单
        $orderParent = OrderParentT::where('id', $order->order_id)
            ->find();
        $outsider = $orderParent->outsider;
        if ($orderParent->type == OrderEnum::EAT_OUTSIDER
            && $order->receive == CommonEnum::STATE_IS_OK) {
            throw new UpdateException(['msg' => '商家已经接单，不能取消']);
        }

        //获取所有子订单
        $subOrders = OrderSubT::where('order_id', $orderParent->id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->select();
        $subIsLast = $this->checkSubIsLast($subOrders);
        if ($outsider == UserEnum::INSIDE) {
            $this->checkOrderCanHandel($orderParent->dinner_id, $orderParent->ordering_date);
        } else {
            //外来人员微信支付--撤回订单
            //检测定单是不是最后一个子订单：如果为最后一个子订单，修改总订单状态，退款加上配送费
            $refundMoney = $order->money + $order->sub_monney;
            if ($subIsLast) {
                $refundMoney += $orderParent->delivery_fee;
            }
            $this->refundWxSubOrder($id, $refundMoney);
        }

        $orderParent->money = $orderParent->money - $order->money - $order->sub_monney;
        if ($subIsLast) {
            $orderParent->state = OrderEnum::STATUS_CANCEL;
        }
        $orderParent->count = $orderParent->count - 1;
        $orderParent->save();

        //更新其它订单排序
        $strategy = (new CanteenService())->getStaffConsumptionStrategy($orderParent->canteen_id, $orderParent->dinner_id, $orderParent->staff_type_id);
        $this->prefixOrderSortWhenUpdateOrder($strategy, $orderParent->dinner_id, $orderParent->phone, $orderParent->ordering_date, $id);
    }

    public function sortSubOrder($subOrders, $subId)
    {
        $subList = [];
        $userType = Token::getCurrentTokenVar('type');
        if ($userType == "cms") {
            $state = OrderEnum::STATE_SHOP_REFUSE;
        } else if ($userType == "official") {
            $state = OrderEnum::STATUS_CANCEL;
        }
        array_push($subList, [
            'id' => $subId,
            'state' => $state
        ]);
        if (count($subOrders) > 1) {
            $check = false;
            $money = 0;
            $sub_money = 0;
            $meal_money = 0;
            $meal_sub_money = 0;
            $no_meal_money = 0;
            $no_meal_sub_money = 0;
            $order_sort = 0;
            foreach ($subOrders as $k => $v) {
                if (!$check) {
                    if ($v['id'] == $subId) {
                        $money = $v['money'];
                        $sub_money = $v['sub_money'];
                        $meal_money = $v['meal_money'];
                        $meal_sub_money = $v['meal_sub_money'];
                        $no_meal_money = $v['no_meal_money'];
                        $no_meal_sub_money = $v['no_meal_sub_money'];
                        $order_sort = $v['order_sort'];
                        $check = true;
                    }
                    continue;
                } else {
                    array_push($subList, [
                        'id' => $v['id'],
                        'money' => $money,
                        'sub_money' => $sub_money,
                        'meal_money' => $meal_money,
                        'meal_sub_money' => $meal_sub_money,
                        'no_meal_money' => $no_meal_money,
                        'no_meal_sub_money' => $no_meal_sub_money,
                        'order_sort' => $order_sort,
                    ]);
                    $money = $v['money'];
                    $sub_money = $v['sub_money'];
                    $meal_money = $v['meal_money'];
                    $meal_sub_money = $v['meal_sub_money'];
                    $no_meal_money = $v['no_meal_money'];
                    $no_meal_sub_money = $v['no_meal_sub_money'];
                    $order_sort = $v['order_sort'];
                }

            }
            return $subList;

        }

    }

    private
    function checkSubIsLast($subOrders)
    {
        if (empty($subOrders)) {
            throw new ParameterException(['msg' => '无可取消订单']);
        }
        if (count($subOrders) == 1) {
            return true;
        }
        return false;
    }

    public
    function orderCancelManager($one_ids, $more_ids)
    {

        try {
            Db::startTrans();
            $oneIdArr = explode(',', $one_ids);
            $moreIdArr = explode(',', $more_ids);
            if (count($oneIdArr)) {
                $this->cancelConsumptionTimeOne($oneIdArr);
            }
            if (count($moreIdArr)) {
                $this->cancelParentConsumptionTimeMore($moreIdArr);
            }
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }


    }

    private
    function cancelConsumptionTimeOne($oneIdArr)
    {
        if (empty($oneIdArr)) {
            return true;
        }
        foreach ($oneIdArr as $k => $v) {
            if (!strlen($v)) {
                continue;
            }
            $order = OrderT::get($v);
            if (!$order) {
                throw new ParameterException(['msg' => "订单号：" . $v . "不存在"]);
            }
            //判断是否使用
            if ($order->used == CommonEnum::STATE_IS_OK) {
                throw new ParameterException(['msg' => '订单已消费，不能取消']);
            }
            //判断是不是微信支付订餐
            if ($order->pay_way == PayEnum::PAY_WEIXIN) {
                //撤回订单
                $this->refundWxOrder($v);
            }
            $res = OrderT::update(['state' => OrderEnum::STATUS_CANCEL], ['id' => $v]);
            if (!$res) {
                throw new UpdateException();
            }

        }
    }

    private
    function cancelParentConsumptionTimeMore($moreIdArr)
    {
        if (!count($moreIdArr)) {
            return true;
        }
        foreach ($moreIdArr as $k => $v) {
            if (!strlen($v)) {
                continue;
            }
            $order = OrderParentT::get($v);
            if (empty($order)) {
                throw new ParameterException(['msg' => "订单号：" . $v . "不存在"]);
            }
            //判断是否使用
            if ($order->used == CommonEnum::STATE_IS_OK) {
                throw new ParameterException(['msg' => '订单已消费，不能取消']);
            }
            //检查订单是否可以操作
            $this->checkOrderCanHandel($order->dinner_id, $order->ordering_date);

            //判断是不是微信支付订餐
            if ($order->pay_way == PayEnum::PAY_WEIXIN) {
                //撤回订单
                $this->refundWxOrder($v, 'more');
            }
            $res = OrderParentT::update(['state' => OrderEnum::STATUS_CANCEL], ['id' => $v]);
            if (!$res) {
                throw new UpdateException(['msg' => '取消总订单失败']);
            }
            //修改子订单状态
            $subUpdate = OrderSubT::update(['state' => OrderEnum::STATUS_CANCEL], ['order_id' => $v]);
            if (!$subUpdate) {
                throw new UpdateException(['msg' => '取消子订单失败']);
            }
            //更新其它订单排序
            $strategy = (new CanteenService())->getStaffConsumptionStrategy($order->canteen_id, $order->dinner_id, $order->staff_type_id);
            $this->prefixOrderSortWhenUpdateOrder($strategy, $order->dinner_id, $order->phone, $order->ordering_date, $v);
        }
    }


    public
    function refundWxOrder($order_id, $consumptionType = 'one')
    {
        $payOrder = PayT::where('order_id', $order_id)
            ->where('times', $consumptionType)
            ->find();
        if (!$payOrder) {
            throw new UpdateException(['msg' => '取消订单失败，订单不存在']);
        }
        if ($payOrder->method_id != PayEnum::PAY_METHOD_WX) {
            throw new UpdateException(['msg' => '取消订单失败，非微信支付订单']);
        }
        $company_id = $payOrder->company_id;
        $money = $payOrder->money;
        $order_number = $payOrder->order_num;
        $refund_order_number = makeOrderNo();
        $refund = WxRefundT::create([
            'order_number' => $order_number,
            'refund_order_number' => $refund_order_number,
            'money' => $money,
            'res' => CommonEnum::STATE_IS_FAIL
        ]);
        if (!$refund) {
            throw new UpdateException(['msg' => '取消订单失败']);
        }
        $refundRes = (new WeiXinPayService())->refundOrder($company_id, $order_number, $refund_order_number, $money, $money);
        $refund->res = $refundRes['res'];
        $refund->return_msg = $refundRes['return_msg'];
        $refund->save();
        if ($refundRes['res'] == CommonEnum::STATE_IS_FAIL) {
            throw new UpdateException(['msg' => '取消订单失败', "失败原因：" . $refundRes['return_msg']]);
        }
        //处理逻辑
        $payOrder->refund_money = $money;
        $payOrder->refund = CommonEnum::STATE_IS_OK;
        $payOrder->save();

    }

    public
    function refundWxSubOrder($order_id, $refundMoney)
    {
        $payOrder = PayT::where('order_id', $order_id)
            ->where('times', 'more')
            ->find();
        if (!$payOrder) {
            throw new UpdateException(['msg' => '取消订单失败，订单不存在']);
        }
        if ($payOrder->method_id != PayEnum::PAY_METHOD_WX) {
            throw new UpdateException(['msg' => '取消订单失败，非微信支付订单']);
        }
        $company_id = $payOrder->company_id;
        $money = $refundMoney;
        $order_number = $payOrder->order_num;
        $refund_order_number = makeOrderNo();
        $refund = WxRefundT::create([
            'order_number' => $order_number,
            'refund_order_number' => $refund_order_number,
            'money' => $money,
            'res' => CommonEnum::STATE_IS_FAIL
        ]);
        if (!$refund) {
            throw new UpdateException(['msg' => '取消订单失败']);
        }
        $refundRes = (new WeiXinPayService())->refundOrder($company_id, $order_number, $refund_order_number, $money, $money);
        $refund->res = $refundRes['res'];
        $refund->return_msg = $refundRes['return_msg'];
        $refund->save();
        if ($refundRes['res'] == CommonEnum::STATE_IS_FAIL) {
            throw new UpdateException(['msg' => '取消订单失败', "失败原因：" . $refundRes['return_msg']]);
        }
        //处理逻辑
        $payOrder->refund_money = $payOrder->refund_money + $refundMoney;
        $payOrder->refund = CommonEnum::STATE_IS_OK;
        $payOrder->save();

    }


    public
    function checkOrderCanHandel($d_id, $ordering_date, $dinner = [])
    {
        //获取餐次设置
        if (empty($dinner)) {
            $dinner = DinnerT::dinnerInfo($d_id);
        }
        $type = $dinner->type;
        $limit_time = $dinner->limit_time;
        $type_number = $dinner->type_number;
        if ($type == 'day') {
            $expiryDate = $this->prefixExpiryDateForOrder($ordering_date, $type_number, '-');
            if (time() > strtotime($expiryDate . ' ' . $limit_time)) {
                throw  new  SaveException(['msg' => '订餐操作时间已截止']);
            }
        } else if ($type == 'week') {
            $ordering_date_week = date('W', strtotime($ordering_date));
            $now_week = date('W', time());
            if ($ordering_date_week <= $now_week) {
                throw  new  SaveException(['msg' => '订餐操作时间已截止']);
            }
            if (($ordering_date_week - $now_week) === 1) {
                if ($type_number == 0) {
                    //星期天
                    if (strtotime($limit_time) < time()) {
                        throw  new  SaveException(['msg' => '订餐操作时间已截止']);
                    }
                } else {
                    //周一到周六
                    if (date('w', time()) > $type_number) {
                        throw  new  SaveException(['msg' => '订餐操作时间已截止']);
                    } else if (date('w', time()) == $type_number && strtotime($limit_time) < time()) {
                        throw  new  SaveException(['msg' => '订餐操作时间已截止']);
                    }
                }
            }

        }
        return true;
    }

    private
    function checkOrderCanHandelToDetail($d_id, $ordering_date, $order_type)
    {
        //获取餐次设置
        $dinner = DinnerT::dinnerInfo($d_id);
        $type = $dinner->type;
        $limit_time = $dinner->limit_time;
        $type_number = $dinner->type_number;
        $meal_time_begin = $dinner->meal_time_begin;
        $meal_time_end = $dinner->meal_time_end;
        $handel = CommonEnum::STATE_IS_OK;
        $showConfirm = CommonEnum::STATE_IS_FAIL;
        if (time() >= strtotime($ordering_date . ' ' . $meal_time_begin) &&
            time() <= strtotime($ordering_date . ' ' . $meal_time_end)) {
            $showConfirm = CommonEnum::STATE_IS_OK;
        }
        if ($order_type == OrderEnum::EAT_OUTSIDER) {
            $showConfirm = CommonEnum::STATE_IS_FAIL;
        }
        if ($type == 'day') {
            $expiryDate = $this->prefixExpiryDateForOrder($ordering_date, $type_number, '-');
            if (time() > strtotime($expiryDate . ' ' . $limit_time)) {
                $handel = CommonEnum::STATE_IS_FAIL;
            }
        } else if ($type == 'week') {
            $ordering_date_week = date('W', strtotime($ordering_date));
            $now_week = date('W', time());
            if ($ordering_date_week <= $now_week) {
                $handel = CommonEnum::STATE_IS_FAIL;
            }
            if (($ordering_date_week - $now_week) === 1) {
                if ($type_number == 0) {
                    //星期天
                    if (strtotime($limit_time) < time()) {
                        $handel = CommonEnum::STATE_IS_FAIL;
                    }
                } else {
                    //周一到周六
                    if (date('w', time()) > $type_number) {
                        $handel = CommonEnum::STATE_IS_FAIL;
                    } else if (date('w', time()) == $type_number && strtotime($limit_time) < time()) {
                        $handel = CommonEnum::STATE_IS_FAIL;
                        2;
                    }
                }
            }

        }
        return [
            'handel' => $handel,
            'showConfirm' => $showConfirm
        ];
    }


    /**
     * 修改订餐数量
     */
    public
    function changeOrderCountToConsumptionMore($id, $updateCount)
    {

        try {
            Db::startTrans();
            $order = OrderParentT::where('id', $id)->find();
            if (!$order) {
                throw new ParameterException(['msg' => '指定订餐信息不存在']);
            }
            //检测订单是否可操作
            $this->checkConsumptionTimesOrderCanUpdate($id);
            $this->checkOrderCanHandel($order->dinner_id, $order->ordering_date);
            //检测订单修改数量是否合法
            $strategy = (new CanteenService())->getStaffConsumptionStrategy($order->canteen_id, $order->dinner_id, $order->staff_type_id);
            $orderCount = $order->count;
            if ($updateCount > $orderCount) {
                $orderedCount = OrderingV::getOrderingCountByWithDinnerID($order->ordering_date, $order->dinner_id, $order->phone);
                $checkCount = $orderedCount - $order->count + $updateCount;
                if (!$strategy) {
                    throw new ParameterException(['msg' => '当前用户消费策略不存在']);
                }
                if ($checkCount > $strategy->ordered_count) {
                    throw new UpdateException(['msg' => '超出最大订餐数量，不能预定']);
                }

                /**
                 * 增加子订单数量
                 * 1.获取增加部分消费策略金额
                 * 2.判断是否固定金额消费
                 * 3.生成子订单
                 */
                $updateMoney = 0;
                if ($order->fixed == CommonEnum::STATE_IS_FAIL) {
                    //获取菜品金额
                    $updateMoney = $this->getOrderFoodsMoney($id);
                }
                $this->handleIncreaseSubOrder($strategy, $id, $order->ordering_date, $order->fixed, $order->canteen_id,
                    $order->dinner_id, $order->phone, $updateCount - $orderCount, $updateMoney, $orderedCount);


            } elseif ($updateCount < $orderCount) {
                //减少子订单数量
                $updateSub = OrderSubT::where('order_id', $id)
                    ->where('order_sort', '>', $updateCount)
                    ->update(['state' => CommonEnum::STATE_IS_FAIL]);
                if (!$updateSub) {
                    throw new UpdateException(['msg' => '修改子订单数量失败']);

                }
            }
            $order->count = $updateCount;
            $order->update_time = date('Y-m-d H:i:s');
            $res = $order->save();
            if (!$res) {
                throw new UpdateException();
            }
            //$this->updateParentOrderMoney($id);
            //更新其它订单排序
            $this->prefixOrderSortWhenUpdateOrder($strategy, $order->dinner_id, $order->phone, $order->ordering_date);
            Db::commit();
        } catch
        (Exception $e) {
            Db::rollback();
            throw $e;
        }

    }

    private function getOrderFoodsMoney($orderId)
    {
        $subFoods = SubFoodT::where('o_id', $orderId)->where('state', CommonEnum::STATE_IS_OK)->field('count*price as money')
            ->select()->toArray();
        return array_sum(array_column($subFoods, 'money'));

    }

    /**
     * 修改订餐数量
     */
    public
    function changeOrderCount($id, $count)
    {
        $order = OrderT::where('id', $id)->find();
        if (!$order) {
            throw new ParameterException(['msg' => '指定订餐信息不存在']);
        }
        if ($order->used == CommonEnum::STATE_IS_OK) {
            throw  new  SaveException(['msg' => '订餐已消费，不能修改订单']);
        }
        if ($order->type == OrderEnum::EAT_OUTSIDER && $order->receive == CommonEnum::STATE_IS_OK) {
            throw  new  SaveException(['msg' => '订餐已被确认，不能修改订单']);
        }
        //检测订单是否可操作
        $this->checkOrderCanHandel($order->d_id, $order->ordering_date);
        //检测订单修改数量是否合法
        if ($count > $order->count) {
            $orderedCount = OrderingV::getOrderingCountByWithDinnerID($order->ordering_date, $order->d_id, $order->phone);
            $checkCount = $orderedCount - $order->count + $count;
            $strategy = (new CanteenService())->getStaffConsumptionStrategy($order->c_id, $order->d_id, $order->staff_type_id);
            if (!$strategy) {
                throw new ParameterException(['msg' => '当前用户消费策略不存在']);
            }
            if ($checkCount > $strategy->ordered_count) {
                throw new UpdateException(['msg' => '超出最大订餐数量，不能预定']);
            }
        }

        $old_money = $order->money;
        $old_sub_money = $order->sub_money;
        $old_meal_money = $order->meal_money;
        $old_meal_sub_money = $order->meal_sub_money;
        $old_no_meal_money = $order->no_meal_money;
        $old_no_meal_sub_money = $order->no_meal_sub_money;
        $old_count = $order->count;
        $new_money = ($old_money / $old_count) * $count;
        $new_sub_money = ($old_sub_money / $old_count) * $count;
        $new_no_meal_money = ($old_no_meal_money / $old_count) * $count;
        $new_no_meal_sub_money = ($old_no_meal_sub_money / $old_count) * $count;
        $new_meal_money = ($old_meal_money / $old_count) * $count;
        $new_meal_sub_money = ($old_meal_sub_money / $old_count) * $count;
        //检测订单金额是否合法
        $check_res = $this->checkBalance($order->u_id, $order->c_id, ($new_money + $new_sub_money - $old_money - $old_sub_money));
        if (!$check_res) {
            throw new UpdateException(['msg' => '当前用户可消费余额不足']);
        }
        //修改数量
        $order->count = $count;
        //处理订单金额
        $order->money = $new_money;
        $order->no_meal_money = $new_no_meal_money;
        $order->meal_money = $new_meal_money;
        //处理订单附加金额
        $order->sub_money = $new_sub_money;
        $order->no_meal_sub_money = $new_no_meal_sub_money;
        $order->meal_sub_money = $new_meal_sub_money;
        //处理消费方式
        $order->pay_way = $check_res;
        $order->update_time = date('Y-m-d H:i:s');
        if (!($order->save())) {
            throw new UpdateException();
        }
    }


    /**
     * 检测逐次消费模式订单是否可以操作
     * @param $orderID
     */
    private
    function checkConsumptionTimesOrderCanUpdate($orderID)
    {
        $usedOrderCount = OrderSubT::usedOrders($orderID);
        if ($usedOrderCount) {
            throw new ParameterException(['msg' => '订单已消费不能修改']);
        }
    }


//一次性扣费消费模式下-修改订单
    public
    function changeOrderFoods($params)
    {
        try {
            Db::startTrans();
            $id = $params['id'];
            $detail = json_decode($params['detail'], true);
            $order = OrderT::where('id', $id)->find();
            if ($order->used == CommonEnum::STATE_IS_OK) {
                throw  new  SaveException(['msg' => '订餐已消费，不能修改订单']);
            }
            if ($order->type == OrderEnum::EAT_OUTSIDER && $order->receive == CommonEnum::STATE_IS_OK) {
                throw  new  SaveException(['msg' => '订餐已被确认，不能修改订单']);
            }

            //检测订单是否可操作
            $old_count = $order->count;
              $this->checkOrderCanHandel($order->d_id, $order->ordering_date);
            if (!empty($params['count']) && ($params['count'] != $old_count)) {
                //检测订单修改数量是否合法
                $updateCount = $params['count'];
                if ($updateCount > $old_count) {
                    $orderedCount = OrderingV::getOrderingCountByWithDinnerID($order->ordering_date, $order->d_id, $order->phone);
                    $checkCount = $orderedCount - $old_count + $updateCount;
                    $strategy = (new CanteenService())->getStaffConsumptionStrategy($order->c_id, $order->d_id, $order->staff_type_id);
                    if (!$strategy) {
                        throw new ParameterException(['msg' => '当前用户消费策略不存在']);
                    }
                    if ($checkCount > $strategy->ordered_count) {
                        throw new UpdateException(['msg' => '超出最大订餐数量，不能预定']);
                    }
                }

            } else {
                $updateCount = $old_count;
            }
            $check_money = $this->checkOrderUpdateMoney($id, $order->u_id, $order->c_id,
                $order->d_id, $order->pay_way, $order->money, $order->sub_money, $order->meal_money, $order->meal_sub_money, $order->count,
                $updateCount, $detail);
            $order->pay_way = $check_money['pay_way'];
            $order->meal_money = $check_money['new_meal_money'];
            $order->meal_sub_money = $check_money['new_meal_sub_money'];

            $old_no_meal_money = $order->no_meal_money;
            $old_no_meal_sub_money = $order->no_meal_sub_money;

            $new_no_meal_money = $old_no_meal_money / $old_count * $updateCount;
            $new_no_meal_sub_money = $old_no_meal_sub_money / $old_count * $updateCount;

            if ($order->fixed == CommonEnum::STATE_IS_OK) {
                if ($new_no_meal_money + $new_no_meal_sub_money
                    < $check_money['new_money'] + $check_money['new_sub_money']) {
                    $order->money = $check_money['new_money'];
                    $order->sub_money = $check_money['new_sub_money'];
                    $order->consumption_type = "ordering_meals";
                } else {
                    $order->money = $new_no_meal_money;
                    $order->sub_money = $new_no_meal_sub_money;
                    $order->consumption_type = "no_meals_ordered";
                }
            } else {
                $order->money = $check_money['new_money'];
                $order->sub_money = $check_money['new_sub_money'];
            }

            $order->count = $updateCount;
            $order->update_time = date('Y-m-d H:i:s');
            $res = $order->save();
            if (!$res) {
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

//逐次扣费消费模式下-修改订单
    public
    function changeOrderFoodsToConsumptionMore($params)
    {
        try {
            Db::startTrans();
            $id = $params['id'];
            $detail = json_decode($params['detail'], true);
            $order = OrderParentT::where('id', $id)->find();
            if (!$order) {
                throw new ParameterException(['msg' => '订单不存在']);
            }
            //检测订单是否可操作
            $this->checkOrderCanHandel($order->dinner_id, $order->ordering_date);
            //处理菜品信息
            $updateFoodsMoney = 0;
            $orderMoneyFixed = $order->fixed;
            if (!empty($detail)) {
                $updateFoodsMoney = $this->checkChangeFoods($id, $order->dinner_id, $detail);
            } else {
                if ($orderMoneyFixed == CommonEnum::STATE_IS_FAIL) {
                    $updateFoodsMoney = $this->getOrderFoodsMoney($id);
                }
            }

            $strategy = (new CanteenService())->getStaffConsumptionStrategy($order->canteen_id, $order->dinner_id, $order->staff_type_id);
            if (!$strategy) {
                throw new ParameterException(['msg' => '当前用户消费策略不存在']);
            }
            //检测订单数量
            $orderCount = $order->count;
            $updateCount = $params['count'];
            //订单数量没有变化，修改订单金额
            if (empty($updateCount) || ($updateCount == $orderCount)) {
                if (!$updateFoodsMoney) {
                    throw new ParameterException(['msg' => '数据异常：数量未变且修改后菜品金额为零']);
                }
                if ($orderMoneyFixed == CommonEnum::STATE_IS_FAIL) {
                    //动态金额模式-修改子订单对应金额
                    $updateSub = OrderSubT::update(['money' => $updateFoodsMoney], ['order_id' => $id]);
                    if (!$updateSub) {
                        throw new UpdateException(['msg' => '修改子订单金额失败']);
                    }
                }
            } else {

                //检测订单修改数量是否合法
                if ($updateCount > $orderCount) {
                    $orderedCount = OrderingV::getOrderingCountByWithDinnerID($order->ordering_date, $order->dinner_id, $order->phone);
                    $checkCount = $orderedCount - $orderCount + $updateCount;
                    if ($checkCount > $strategy->ordered_count) {
                        throw new UpdateException(['msg' => '超出最大订餐数量，不能预定']);
                    }
                    /**
                     * 增加子订单数量
                     * 1.获取增加部分消费策略金额
                     * 2.判断是否固定金额消费
                     * 3.生成子订单
                     */
                    $this->handleIncreaseSubOrder($strategy, $id, $order->ordering_date, $orderMoneyFixed, $order->canteen_id,
                        $order->dinner_id, $order->phone, $updateCount - $orderCount, $updateFoodsMoney, $orderedCount);


                } else {
                    if ($orderMoneyFixed == CommonEnum::STATE_IS_FAIL) {
                        $updateSub = OrderSubT::update(['money' => $updateFoodsMoney], ['order_id' => $id]);
                        if (!$updateSub) {
                            throw new UpdateException(['msg' => '修改子订单金额失败']);
                        }
                    }
                    $updateSub = OrderSubT::where('order_id', $id)
                        ->where('order_sort', '>', $updateCount)
                        ->update(['state' => CommonEnum::STATE_IS_FAIL]);
                    if (!$updateSub) {
                        throw new UpdateException(['msg' => '修改子订单数量']);
                    }
                }
            }
            $order->count = $updateCount;
            $order->update_time = date('Y-m-d H:i:s');
            $res = $order->save();
            if (!$res) {
                throw new UpdateException();
            }
            //处理订单明细
            if (!empty($detail)) {
                $this->prefixUpdateOrderDetail($id, $detail, 'more');
            }
            //更新其它订单排序
            $this->prefixOrderSortWhenUpdateOrder($strategy, $order->dinner_id, $order->phone, $order->ordering_date);
            Db::commit();
        } catch
        (Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    private
    function updateParentOrderMoney($orderId)
    {
        //修改总订单金额
        $parentMoney = OrderSubT::getOrderMoney($orderId);
        $parentMoney = array_sum(array_column($parentMoney, 'money'));
        $updateParentRes = OrderParentT::update(['money' => $parentMoney], ['id' => $orderId]);
        if (!$updateParentRes) {
            throw  new UpdateException(['msg' => '修改总订单失败']);
        }

    }

    /**
     * 处理逐次消费中，增加订餐数量情况
     */
    private
    function handleIncreaseSubOrder($strategy, $orderId, $ordering_date, $orderMoneyFixed, $canteen_id,
                                    $dinner_id, $phone, $increaseCount, $updateFoodsMoney, $consumptionCount)
    {
        $subOrderDataList = [];
        $dinner = DinnerT::dinnerInfo($dinner_id);
        $orders = OrderingV::getRecordForDayOrderingByPhone($ordering_date, $dinner->name, $phone);
        $this->checkOrderedAnotherCanteen($canteen_id, $orders);
        $strategyMoney = $this->checkConsumptionStrategyTimesMore($strategy, $increaseCount, $consumptionCount);
        if ($orderMoneyFixed == CommonEnum::STATE_IS_FAIL) {
            //1.处理之前已经下单的金额
            OrderSubT::update(['money' => $updateFoodsMoney], ['order_id' => $orderId]);
        }
        foreach ($strategyMoney as $k => $v) {
            $money = $orderMoneyFixed == CommonEnum::STATE_IS_FAIL ? $updateFoodsMoney : $v['money'];
            $data = [
                'order_id' => $orderId,
                'ordering_date' => $ordering_date,
                'consumption_sort' => $v['number'],
                'order_sort' => $v['number'],
                'order_num' => makeOrderNo(),
                'count' => 1,
                'money' => $money,
                'sub_money' => $v['sub_money'],
                'consumption_type' => $v['consumption_type'],
                'meal_money' => $v['meal_money'],
                'meal_sub_money' => $v['meal_sub_money'],
                'no_meal_money' => $v['no_meal_money'],
                'no_meal_sub_money' => $v['no_meal_sub_money'],
                'ordering_type' => 'personal_choice',
            ];
            array_push($subOrderDataList, $data);
        }
        $list = (new OrderSubT())->saveAll($subOrderDataList);
        if (!$list) {
            throw new SaveException(['msg' => '生成子订单失败']);
        }

    }

    /**
     * 检查个人选菜修改菜品信息是否合法并返回单份菜品金额
     */
    private
    function checkChangeFoods($orderId, $dinnerId, $updateFoods)
    {
        //获取餐次下所有菜品类别
        $menus = (new MenuService())->dinnerMenus($dinnerId);
        if (!count($menus)) {
            throw new ParameterException(['msg' => '指定餐次未设置菜单信息']);
        }
        $oldFoods = SubFoodT::detail($orderId);
        $check_data = [];
        if (!empty($updateFoods)) {
            foreach ($updateFoods as $k => $v) {
                if ($k == 0) {
                    $check_data = $oldFoods;
                }
                $menu_id = $v['menu_id'];
                $add_foods = $v['add_foods'];
                $update_foods = $v['update_foods'];
                $cancel_foods = $v['cancel_foods'];
                $check_data = $this->checkOrderDetailUpdate($update_foods, $check_data);
                $check_data = $this->checkOrderDetailCancel($cancel_foods, $check_data);
                $check_data = $this->checkOrderDetailAdd($menu_id, $add_foods, $check_data);
                $menu = $this->getMenuInfo($menus, $menu_id);
                if (empty($menu)) {
                    throw new ParameterException(['msg' => '菜品类别id错误']);
                }
                $newMenuCount = $this->getMenuCount($menu_id, $check_data);
                if (($menu['status'] == MenuEnum::FIXED) && ($menu['count'] < $newMenuCount)) {
                    throw new SaveException(['msg' => '选菜失败,菜品类别：<' . $menu['category'] . '> 选菜数量超过最大值：' . $menu['count']]);
                }
            }
        }

        if (!count($check_data)) {
            return 0;
        }
        $updateMoney = 0;
        foreach ($check_data as $k3 => $v3) {
            $updateMoney += $v3['price'] * $v3['count'];
        }
        return $updateMoney;
    }

    private
    function prefixUpdateOrderDetail($o_id, $new_detail, $consumptionTimes = 'one')
    {
        $data_list = [];
        foreach ($new_detail as $k => $v) {
            $menu_id = $v['menu_id'];
            $add_foods = empty($v['add_foods']) ? [] : $v['add_foods'];
            $update_foods = empty($v['update_foods']) ? [] : $v['update_foods'];
            $cancel_foods = empty($v['cancel_foods']) ? '' : $v['cancel_foods'];
            if (!empty($add_foods)) {
                foreach ($add_foods as $k2 => $v2) {
                    $data = [
                        'm_id' => $menu_id,
                        'f_id' => $v2['food_id'],
                        'price' => $v2['price'],
                        'count' => $v2['count'],
                        'name' => $v2['name'],
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
        if ($consumptionTimes == 'one') {
            $res = (new OrderDetailT())->saveAll($data_list);
        } else {
            $res = (new SubFoodT())->saveAll($data_list);

        }
        if (!$res) {
            throw new UpdateException(['msg' => '更新订单明细失败']);
        }
    }

    private
    function checkOrderUpdateMoney($o_id, $u_id, $canteen_id, $dinner_id, $pay_way,
                                   $old_money, $old_sub_money, $old_meal_money,
                                   $old_meal_sub_money,
                                   $old_count, $count, $new_detail)
    {
        //获取餐次下所有菜品类别
        $menus = (new MenuService())->dinnerMenus($dinner_id);
        if (!count($menus)) {
            throw new ParameterException(['msg' => '指定餐次未设置菜单信息']);
        }
        $dinner = DinnerT::dinnerInfo($dinner_id);
        $fixed = $dinner->fixed;
        $old_detail = OrderDetailT::detail($o_id);
        $check_data = [];

        if ($fixed == CommonEnum::STATE_IS_OK) {
            $new_money = $old_money / $old_count * $count;
            $new_meal_money = $old_meal_money / $old_count * $count;
        } else {
            if (!empty($new_detail)) {
                $new_money = 0;
                foreach ($new_detail as $k => $v) {
                    if ($k == 0) {
                        $check_data = $old_detail;
                    }
                    $menu_id = $v['menu_id'];
                    $add_foods = $v['add_foods'];
                    $update_foods = $v['update_foods'];
                    $cancel_foods = $v['cancel_foods'];
                    $check_data = $this->checkOrderDetailUpdate($update_foods, $check_data);
                    $check_data = $this->checkOrderDetailCancel($cancel_foods, $check_data);
                    $check_data = $this->checkOrderDetailAdd($menu_id, $add_foods, $check_data);
                    $menu = $this->getMenuInfo($menus, $menu_id);
                    if (empty($menu)) {
                        throw new ParameterException(['msg' => '菜品类别id错误']);
                    }
                    $newMenuCount = $this->getMenuCount($menu_id, $check_data);
                    if (($menu['status'] == MenuEnum::FIXED) && ($menu['count'] < $newMenuCount)) {
                        throw new SaveException(['msg' => '选菜失败,菜品类别：<' . $menu['category'] . '> 选菜数量超过最大值：' . $menu['count']]);
                    }
                }
                if (count($check_data)) {
                    foreach ($check_data as $k3 => $v3) {
                        $new_money += $v3['price'] * $v3['count'];
                    }
                }
                $new_money = $new_money * $count;
                $new_meal_money = $old_meal_money / $old_count * $count;
            } else {
                $new_money = $old_money / $old_count * $count;
                $new_meal_money = $old_meal_money / $old_count * $count;

            }

        }

        $new_sub_money = $old_sub_money / $old_count * $count;
        $new_meal_sub_money = $old_meal_sub_money / $old_count * $count;
        if ($new_money > $old_money) {
            $pay_way = $this->checkBalance($u_id, $canteen_id, $new_money + $new_sub_money - $old_money - $old_sub_money);
        }
        return [
            'new_money' => $new_money,
            'new_sub_money' => $new_sub_money,
            'new_meal_money' => $new_meal_money,
            'new_meal_sub_money' => $new_meal_sub_money,
            'pay_way' => $pay_way
        ];
    }

    private
    function getMenuCount($menuId, $detail)
    {
        LogService::save(json_encode($detail));
        $count = 0;
        if (!count($detail)) {
            return 0;
        }
        foreach ($detail as $k => $v) {
            if ($v['m_id'] == $menuId) {
                $count += 1;
            }
        }
        return $count;

    }

    private
    function checkOrderDetailCancel($cancel_foods, $check_data)
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

    private
    function checkOrderDetailUpdate($update_foods, $check_date)
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

    private
    function checkOrderDetailAdd($menu_id, $add_foods, $check_data)
    {
        if (empty($add_foods)) {
            return $check_data;
        }
        foreach ($add_foods as $k => $v) {
            $data = [
                'm_id' => $menu_id,
                'f_id' => $v['food_id'],
                'price' => $v['price'],
                'count' => $v['count'],
            ];
            array_push($check_data, $data);
        }
        return $check_data;
    }

    public
    function personalChoiceInfo($id, $consumptionType)
    {
        if ($consumptionType == 'one') {
            $info = OrderT:: personalChoiceInfo($id);
        } else {
            $info = OrderParentT:: personalChoiceInfo($id);
        }
        return $info;
    }

    public
    function userOrders($type, $id, $page, $size)
    {
        // $u_id = Token::getCurrentUid();
        $phone = Token::getCurrentPhone();
        if ($type == OrderEnum::USER_ORDER_SHOP) {
            $orders = ShopOrderingV::userOrderings($phone, $id, $page, $size);
        } else {
            $orders = OrderingV::userOrderings($phone, $type, $id, $page, $size);
        }
        return $orders;
    }

    public
    function orderDetail($consumptionType, $type, $id)
    {
        //$u_id = Token::getCurrentUid();
        if ($type == OrderEnum::USER_ORDER_SHOP) {
            $order = ShopOrderT::orderInfo($id);
        } else {
            if ($consumptionType == "one") {
                $order = OrderT::orderDetail($id);
            } else {
                if (Token::getCurrentTokenVar('type') == 'cms') {
                    $order = $this->getOrderDetailConsumptionTimeMore($id);
                } else {
                    $order = $this->InfoToConsumptionTimesMore($id);

                }
            }
            if (!$order) {
                throw new ParameterException(['msg' => '订单不存在']);
            }
            $check = $this->checkOrderCanHandelToDetail($order['dinner_id'], $order['ordering_date'], $order['order_type']);
            $order['handel'] = $check['handel'];
            $order['consumption_type'] = $consumptionType;
            $order['showConfirm'] = $check['showConfirm'];
        }
        return $order;
    }

    private function getOrderDetailConsumptionTimeMore($orderId)
    {
        $subOrder = OrderSubT::where('id', $orderId)->find();
        if (!$subOrder) {
            return false;
        }
        $parentOrder = OrderParentT::detail($subOrder->order_id);
        $parentOrder['money'] = $subOrder['money'];
        $parentOrder['sub_money'] = $subOrder['sub_money'];
        $parentOrder['meal_money'] = $subOrder['meal_money'];
        $parentOrder['meal_sub_money'] = $subOrder['meal_sub_money'];
        $parentOrder['no_meal_money'] = $subOrder['no_meal_money'];
        $parentOrder['no_meal_sub_money'] = $subOrder['no_meal_sub_money'];
        $parentOrder['used_time'] = $subOrder['used_time'];
        $parentOrder['sort_code'] = $subOrder['sort_code'];
        $parentOrder['wx_confirm'] = $subOrder['wx_confirm'];
        $parentOrder['count'] = $subOrder['count'];
        $parentOrder['state'] = $subOrder['state'];
        $parentOrder['used'] = $subOrder['used'];
        return $parentOrder;
    }


//用户查询消费记录
    public
    function consumptionRecords($consumption_time, $page, $size)
    {
        $phone = Token::getCurrentPhone();
        $canteen_id = Token::getCurrentTokenVar('current_canteen_id');
        $company_id = Token::getCurrentTokenVar('current_company_id');
        $records = ConsumptionRecordsV::recordsByPhone($phone, $canteen_id, $company_id, $consumption_time, $page, $size);
        $records['data'] = $this->prefixConsumptionRecords($records['data']);
        $consumptionMoney = ConsumptionRecordsV::monthConsumptionMoneyByPhone($phone, $consumption_time);
        return [
            'balance' => $this->getUserBalance($canteen_id, $company_id, $phone),
            'consumptionMoney' => $consumptionMoney,
            'records' => $records
        ];
    }

    private
    function prefixConsumptionRecords($data)
    {

        if (count($data)) {
            foreach ($data as $k => $v) {
                if ($v['order_type'] == "canteen") {
                    if ($v['used'] == CommonEnum::STATE_IS_FAIL) {
                        $data[$k]['used_type'] = "订餐未就餐";
                    } else {
                        if ($v['booking'] == CommonEnum::STATE_IS_OK) {
                            $data[$k]['used_type'] = "订餐就餐";

                        } else {
                            $data[$k]['used_type'] = "未订餐就餐";
                        }
                    }
                    $eatingType = $v['eating_type'] == OrderEnum::EAT_CANTEEN ? '堂食' : '外卖';
                    $v['location'] = $v['location'] . '-' . $eatingType;

                } else if ($v['order_type'] == "recharge") {
                    if ($v['supplement_type'] == CommonEnum::STATE_IS_OK) {
                        $data[$k]['used_type'] = "系统补充";
                    } else {
                        $data[$k]['used_type'] = "系统补扣";

                    }

                } else if ($v['order_type'] == "shop") {
                    $data[$k]['used_type'] = $v['money'] < 0 ? "小卖部消费" : "小卖部退款";
                }
            }
        }
        return $data;
    }

    public
    function getUserBalance($canteen_id, $company_id, $phone)
    {

        $canteenAccount = CanteenAccountT::where('c_id', $canteen_id)
            ->find();
        if (!$canteenAccount) {
            throw new ParameterException(['msg' => '该用户归属饭堂设置异常']);
        }
        $hidden = $canteenAccount->type;
        $all = 0;
        $effective = 0;
        if ($hidden == CommonEnum::STATE_IS_FAIL) {
            //不可透支消费，返回用户在该企业余额
            $money = UserBalanceV::userBalanceGroupByEffective($company_id, $phone);
            foreach ($money as $k => $v) {
                $all += $v['money'];
                if ($v['effective'] == CommonEnum::STATE_IS_OK) {
                    $effective = $v['money'];
                }
            }

        }
        return [
            'hidden' => $hidden,
            'all_money' => $all,
            'effective_money' => $effective
        ];

    }

    public
    function recordsDetail($order_type, $order_id, $consumptionType)
    {
        if ($order_type == "shop") {
            $order = ShopOrderT::orderInfo($order_id);
        } else if ($order_type == "canteen") {
            $order = $this->orderStatisticDetailInfo($order_id, $consumptionType);
        } else if ($order_type == "recharge") {
            $order = RechargeSupplementT::orderDetail($order_id);
        }
        return $order;
    }

    public
    function managerOrders($canteen_id, $consumption_time, $key)
    {
        //获取饭堂餐次信息
        $dinner = (new CanteenService())->getDinnerNames($canteen_id);
        if (!$dinner) {
            throw new ParameterException(['msg' => '参数异常，该饭堂未设置餐次信息']);
        }
        //获取饭堂订餐信息
        $orderInfo = OrderUsersStatisticV::statisticToOfficial($canteen_id, $consumption_time, $key);
        foreach ($dinner as $k => $v) {
            $all = 0;
            $used = 0;
            $noOrdering = 0;
            $orderingNoMeal = 0;
            if (!empty($orderInfo)) {
                foreach ($orderInfo as $k2 => $v2) {
                    if ($v['id'] == $v2['d_id']) {
                        $all += $v2['count'];
                        if ($v2['used'] == CommonEnum::STATE_IS_OK) {
                            if ($v2['booking'] == CommonEnum::STATE_IS_OK) {
                                $used += $v2['count'];
                            } else if ($v2['booking'] == CommonEnum::STATE_IS_FAIL) {
                                $noOrdering += $v2['count'];
                            }
                        } else if ($v2['used'] == CommonEnum::STATE_IS_FAIL) {
                            $orderingNoMeal += $v2['count'];
                        }
                        unset($orderInfo[$k2]);
                    }

                }
            }
            $dinner[$k]['all'] = $all;
            $dinner[$k]['used'] = $used;
            $dinner[$k]['noOrdering'] = $noOrdering;
            $dinner[$k]['orderingNoMeal'] = $orderingNoMeal;
        }
        return $dinner;

    }

//微信端总订餐查询-点击订餐数量，获取菜品统计信息
    public
    function managerDinnerStatistic($dinner_id, $consumption_time, $page, $size)
    {
        $statistic = DinnerStatisticV::managerDinnerStatistic($dinner_id, $consumption_time, $page, $size);
        if ($statistic->isEmpty()) {
            return [
                'haveFoods' => CommonEnum::STATE_IS_FAIL,
                // 'statistic' => $this->orderUsersStatistic($dinner_id, $consumption_time, 'all', 1, 20)
            ];
        }
        return [
            'haveFoods' => CommonEnum::STATE_IS_OK,
            'statistic' => $statistic
        ];
    }

    public
    function orderUsersStatistic($canteen_id, $dinner_id, $consumption_time, $consumption_type, $key, $page, $size)
    {
        $statistic = OrderUsersStatisticV::orderUsers($canteen_id, $dinner_id, $consumption_time, $consumption_type, $key, $page, $size);
        $statistic['data'] = $this->prefixUsersStatisticStatus($statistic['data']);
        return $statistic;
    }

    private function prefixUsersStatisticStatus($data)
    {
        if (count($data)) {
            foreach ($data as $k => $v) {
                if ($v["booking"] == CommonEnum::STATE_IS_OK) {
                    if ($v["used"] == CommonEnum::STATE_IS_OK) {
                        $data[$k]['status'] = "订餐就餐";
                    } else {
                        $data[$k]['status'] = "订餐未就餐";
                    }
                } else {
                    if ($v["used"] == CommonEnum::STATE_IS_OK) {
                        $data[$k]['status'] = "未订餐就餐";
                    }
                }
            }
            return $data;

        }
        return $data;


    }

    public
    function foodUsersStatistic($dinner_id, $food_id, $consumption_time, $page, $size)
    {
        $statistic = FoodsStatisticV::foodUsersStatistic($dinner_id, $food_id, $consumption_time, $page, $size);
        return $statistic;
    }

    public
    function handelOrderedNoMeal($dinner_id, $consumption_time)
    {
        $dinner = DinnerT::where('id', $dinner_id)->find();
        if (!$dinner) {
            throw new ParameterException(['msg' => '餐次信息不存在']);
        }
        $canteen_id = $dinner->c_id;
        $checkCleanTime = false;
        $cleanTime = '';
        $dinnerEndTime = $consumption_time . ' ' . $dinner->meal_time_end;
        $account = CanteenAccountT::where('c_id', $canteen_id)->find();
        if ($account) {
            if ($account->type = CommonEnum::STATE_IS_OK) {
                $cleanTime = date('Y-m', strtotime($consumption_time)) . '-' . $account->clean_day . ' ' . $account->clean_time;
            }
        }
        if (strtotime($dinnerEndTime) > time()) {
            throw new UpdateException(['msg' => '就餐时间未结束，，不能一键扣费操作，请在' . $dinnerEndTime .
                '(消费策略时间)后进行操作']);
        }
        if ($checkCleanTime && strtotime($cleanTime) < time()) {
            throw new UpdateException(['msg' => '超出系统扣费时间，不能一键扣费']);
        }
        //将订餐未就餐改为订餐就餐信息进行缓存
        $list = OrderT::orderUsersNoUsed($dinner_id, $consumption_time);
        $dataList = [];
        foreach ($list as $k => $v) {
            $dataList[] = [
                'order_id' => $v->id,
                'state' => CommonEnum::STATE_IS_FAIL
            ];
        }
        if (count($dataList)) {
            $res = (new OrderHandelT())->saveAll($dataList);
            if (!$res) {
                throw new UpdateException();
            }
        }
    }

    public
    function orderStateHandel()
    {
        try {
            // Db::startTrans();
            $orders = OrderHandelT::where('state', CommonEnum::STATE_IS_FAIL)
                ->limit(0, 5)->select();
            if (!$orders->isEmpty()) {
                foreach ($orders as $k => $v) {
                    $this->prefixOrderState($v->order_id);
                }

            }
            //Db::commit();
        } catch (Exception $e) {
            //Db::rollback();
            LogService::save($e->getMessage());
        }

    }

    private
    function prefixOrderState($order_id)
    {
        $order = OrderT::where('id', $order_id)->find();
        if ($order->consumption_type == "no_meals_ordered") {
            //获取餐次信息
            $dinner = DinnerT::dinnerInfo($order->d_id);
            //获取消费策略
            $strategies = (new CanteenService())->getStaffConsumptionStrategy($order->c_id, $order->d_id, $order->staff_type_id);
            $detail = json_decode($strategies->detail, true);
            if (empty($detail)) {
                throw new ParameterException();
                LogService::save('消费策略设置异常，订单id：' . $order_id);
            }
            $fixed = $dinner->fixed;
            $number = $this->getOrderNumber($order->id, $order->c_id, $order->u_id, $order->d_id, $order->ordering_date);
            $count = $order->count;
            $money = 0;
            $sub_money = 0;
            $checkType = false;
            foreach ($detail as $k => $v) {
                if ($number == $v['number']) {
                    $strategy = $v['strategy'];
                    foreach ($strategy as $k2 => $v2) {
                        if ($v2['status'] == 'ordering_meals') {
                            $money = $v2['money'];
                            $sub_money = $v2['sub_money'];
                            $checkType = true;
                        }

                    }
                }
            }
            if (!$checkType) {
                LogService::save('未找到消费策略，订单id：' . $order_id);
                throw new UpdateException(['msg' => '未找到消费策略，订单id：' . $order_id]);
            }
            if ($fixed == CommonEnum::STATE_IS_OK) {
                $order->money = $money;
                $order->sub_money = $sub_money;
            } else {
                $order->sub_money = $sub_money;
            }
        }

        $order->used = CommonEnum::STATE_IS_OK;
        $order->remark = "消费状态由未就餐改为就餐";
        $res = $order->save();
        if (!$res) {
            LogService::save('更新订单失败，订单id：' . $order_id);
            throw new UpdateException(['msg' => '更新订单失败，订单id：' . $order_id]);
        }
    }

//获取本餐是饭堂第几次消费
    public
    function getOrderNumber($order_id, $canteen_id, $u_id, $dinner_id, $ordering_date)
    {
        $phone = Token::getCurrentPhone();
        $orders = OrderT::where('phone', $phone)
            ->where('c_id', $canteen_id)
            ->where('d_id', $dinner_id)
            ->whereBetweenTime('ordering_date', $ordering_date)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->order('id')
            ->select()->toArray();
        $number = 1;
        if ($orders > 1) {
            foreach ($orders as $k => $v) {
                if ($v['id'] == $order_id) {
                    $number = $k + 1;
                    break;
                }

            }

        }
        return $number;
    }

    public
    function used($order_id, $consumptionType)
    {
        try {
            Db::startTrans();
            if ($consumptionType == "one") {
                $order = OrderT::get($order_id);
                if ($order->consumption_type == 'no_meals_ordered' && ($order->fixed == CommonEnum::STATE_IS_OK || $order->ordering_type == "online")) {
                    $order->money = $order->meal_money;
                }
                $order->sub_money = $order->meal_sub_money;
                $order->used = CommonEnum::STATE_IS_OK;
                $order->used_time = date('Y-m-d H:i:s');
                $res = $order->save();
                if (!$res) {
                    throw new UpdateException();
                }
            } else {
                $allMoney = 0;
                $subList = [];
                $subOrder = OrderSubT::where('order_id', $order_id)
                    ->where('state', CommonEnum::STATE_IS_OK)
                    ->select();
                $parentOrder = OrderParentT::get($order_id);
                $usedTime = date('Y-m-d H:i:s');
                foreach ($subOrder as $k => $v) {
                    $mealMoney = $v['money'];
                    if ($v['consumption_type'] == 'no_meals_ordered' && ($parentOrder->fixed == CommonEnum::STATE_IS_OK || $parentOrder->ordering_type == "online")) {
                        $mealMoney = $v['meal_money'];
                    }
                    $allMoney += ($mealMoney + $v['meal_sub_money']);
                    array_push($subList, [
                        'id' => $v['id'],
                        'money' => $mealMoney,
                        'sub_money' => $v['meal_sub_money'],
                        'used' => CommonEnum::STATE_IS_OK,
                        'used_time' => $usedTime
                    ]);
                }
                $updateSub = (new OrderSubT())->saveAll($subList);
                if (!$updateSub) {
                    throw new UpdateException(['msg' => "更新子订单失败"]);
                }
                $parentOrder->money = $allMoney;
                $parentOrder->used = CommonEnum::STATE_IS_OK;
                $parentOrder->used_time = $usedTime;
                $parentOrder->all_used = CommonEnum::STATE_IS_OK;
                $updateParent = $parentOrder->save();
                if (!$updateParent) {
                    throw new UpdateException(['msg' => "更新子订单失败"]);
                }

            }

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }


    }

    public
    function infoForPersonChoiceOnline($day)
    {
        $canteen_id = Token::getCurrentTokenVar('current_canteen_id');
        $company_id = Token::getCurrentTokenVar('current_company_id');
        $outsider = Token::getCurrentTokenVar('outsiders');
        $phone = Token::getCurrentPhone();
        $dinner = DinnerT::canteenDinnerMenus($canteen_id);
        if ($outsider == UserEnum::OUTSIDE) {
            return $dinner;
        }
        $t_id = (new UserService())->getUserStaffTypeByPhone($phone, $company_id);
        $strategies = (new CanteenService())->staffStrategy($canteen_id, $t_id);
        foreach ($dinner as $k => $v) {
            $dinner[$k]['ordering_count'] = OrderingV::getOrderingCountByPhone($day, $v["name"], $phone);
            foreach ($strategies as $k2 => $v2) {
                if ($v['id'] == $v2['d_id']) {
                    $dinner[$k]['ordered_count'] = $v2['ordered_count'];
                    unset($strategies[$k2]);
                }
            }
        }
        return $dinner;
    }

    public
    function changeOrderAddress($order_id, $address_id, $consumption_type, $remark)
    {
        if ($consumption_type == "one") {
            $order = OrderT::where('id', $order_id)->find();

        } else if ($consumption_type == "more") {
            $order = OrderParentT::where('id', $order_id)->find();
        } else {
            throw new ParameterException(['msg' => "消费类型异常"]);
        }
        if (!$order) {
            throw new ParameterException(['msg' => '订单不存在']);
        }
        if ($order->receive == CommonEnum::STATE_IS_OK) {
            throw new UpdateException(['msg' => "订单已经接单，不能修改地址"]);
        }
        $order->address_id = $address_id;
        $order->remark = $remark;
        $res = $order->save();
        if (!$res) {
            throw new UpdateException(['msg' => "修改地址失败"]);

        }

    }

    public
    function usersStatisticInfo($orderIds, $consumptionType)
    {
        $orders = OrderT::usersStatisticInfo($orderIds);


        /*  if ($consumptionType == "one") {
              return $this->InfoToConsumptionTimesOne($orderIds);
          } else if ($consumptionType == "more") {
              return $this->InfoToConsumptionTimesMore($orderIds);
          }*/
        return $orders;
    }

    public
    function orderStatisticDetailInfo($orderId, $consumptionType)
    {
        if ($consumptionType == 'one') {
            $info = $this->InfoToConsumptionTimesOne($orderId);
        } else if ($consumptionType == 'more') {
            $info = $this->InfoToConsumptionTimesMore($orderId);
        }
        $info['consumptionType'] = $consumptionType;
        return $info;
    }

    private
    function InfoToConsumptionTimesOne($orderId)
    {
        $order = OrderT::infoToStatisticDetail($orderId);
        if (!$order) {
            throw new ParameterException(['msg' => "订单不存在"]);
        }
        $count = $order->count;
        $money = $order->money / $count;
        $sub_money = $order->sub_money / $count;
        $dinner = $order->dinner;
        $data['id'] = $order->id;
        $data['create_time'] = $order->create_time;
        $data['ordering_type'] = $order->ordering_type;
        $data['type'] = $order->type;
        $data['fixed'] = $order->fixed;
        $data['money'] = $order->money;
        $data['sub_money'] = $order->sub_money;
        $data['count'] = $order->count;
        $data['delivery_fee'] = $order->delivery_fee;
        $data['ordering_date'] = $order->ordering_date;
        $data['meal_time_end'] = $dinner['meal_time_end'];
        $data['remark'] = $order->remark;
        $status = $this->getOrderStatus($order->state, $order->used, $order->ordering_date, $dinner['meal_time_end']);
        $consumptionStatus = $this->getConsumptionStatus($order->booking, $order->used);
        $dataList = [];
        for ($i = 1; $i <= $count; $i++) {
            $detail = [
                'number' => $i,
                'order_id' => $orderId,
                'money' => $money,
                'sub_money' => $sub_money,
                'wx_confirm' => $order->wx_confirm,
                'sort_code' => $order->sort_code,
                'consumption_status' => $consumptionStatus,
                'status' => $status
            ];
            array_push($dataList, $detail);
        }
        $data['foods'] = $order->foods;
        $data['sub'] = $dataList;
        return $data;
    }

    private
    function InfoToConsumptionTimesMore($orderId)
    {
        $order = OrderParentT::infoToStatisticDetail($orderId);
        if (!$order) {
            throw new ParameterException(['msg' => "订单不存在"]);
        }
        $data['id'] = $order->id;
        $dinner = $order->dinner;
        $booking = $order->booking;
        $sub = $order->sub;
        $data['type'] = $order->type;
        $data['fixed'] = $order->fixed;
        $data['order_type'] = $order->type;
        $data['dinner_id'] = $order->dinner_id;
        $data['canteen_id'] = $order->canteen_id;
        $data['create_time'] = $order->create_time;
        $data['ordering_type'] = $order->ordering_type;
        $data['count'] = $order->count;
        $data['delivery_fee'] = $order->delivery_fee;
        $data['ordering_date'] = $order->ordering_date;
        $data['remark'] = $order->remark;
        $data['receive'] = $order->receive;
        $data['meal_time_end'] = $dinner['meal_time_end'];
        $dataList = [];
        foreach ($sub as $k => $v) {
            $status = $this->getOrderStatus($v['state'], $v['used'], $order->ordering_date, $dinner['meal_time_end']);
            $consumptionStatus = $this->getConsumptionStatus($booking, $v['used']);
            $detail = [
                'number' => $v['consumption_sort'],
                'order_id' => $v['id'],
                'money' => round($v['money'], 2),
                'sub_money' => round($v['sub_money'], 2),
                'wx_confirm' => $v['wx_confirm'],
                'sort_code' => $v['sort_code'],
                'consumption_status' => $consumptionStatus,
                'status' => $status
            ];
            array_push($dataList, $detail);
        }
        $data['sub'] = $dataList;
        $data['foods'] = $order->foods;
        $data['address'] = $order->address;
        return $data;
    }

    private
    function getOrderStatus($state, $used, $ordering_date, $meal_time_end)
    {
        if ($state != CommonEnum::STATE_IS_OK) {
            return 2;//已取消
        } else {
            $expiryDate = $ordering_date . ' ' . $meal_time_end;
            if (time() > strtotime($expiryDate)) {
                return 3;//已结算
            } else {
                if ($used == CommonEnum::STATE_IS_FAIL) {
                    return 1;//可取消
                } else {
                    return 3;
                }
            }
        }
    }

    private
    function getConsumptionStatus($booking, $used)
    {
        if ($used == CommonEnum::STATE_IS_FAIL) {
            return "订餐未就餐";
        } else {
            if ($booking == CommonEnum::STATE_IS_OK) {
                return "订餐就餐";

            } else {
                return "未订餐就餐";
            }

        }
    }

    public function consumptionTimesMoreInfoForPrinter($orderID)
    {
        $sub = OrderSubT::infoForPrinter($orderID);
        $parentID = $sub->order_id;
        $parent = OrderParentT::infoToPrintDetail($parentID);
        $parent['confirm_time'] = $sub->confirm_time;
        $parent['money'] = $sub->money;
        $parent['sub_money'] = $sub->sub_money;
        $parent['qrcode_url'] = $sub->qrcode_url;
        $parent['count'] = $sub->count;
        $parent['sort_code'] = $sub->sort_code;
        return $parent;
    }

    public function prefixOrderSortWhenUpdateOrder($strategy, $dinnerId, $phone, $orderingDate, $orderID = 0)
    {
        //1.获取用户所有订单
        $orders = OrderingV::getOrderingByWithDinnerID($orderingDate, $dinnerId, $phone, $orderID);
        if (!count($orders)) {
            return true;
        }
        $detail = $strategy->detail;
        if (empty($detail)) {
            throw new ParameterException(['msg' => "消费策略设置异常"]);
        }
        $consumptionCount = 1;
        $updateParentOrderData = [];
        $updateSubOrderData = [];
        foreach ($orders as $k => $v) {
            $parentMoney = 0;
            $parentId = $v['id'];
            $orderType = $v['ordering_type'];
            $orderFixed = $v['fixed'];
            if ($v['used'] == CommonEnum::STATE_IS_OK) {
                throw  new ParameterException(['msg' => '订单已经消费不能修改']);
            }
            $foodMoney = 0;
            if ($orderType == OrderEnum::ORDERING_CHOICE && $orderFixed == CommonEnum::STATE_IS_FAIL) {
                //个人选菜且动态消费-获取订单菜品金额
                $foods = SubFoodT::detailMoney($parentId);
                $foodMoney = array_sum(array_column($foods, 'money'));
            }
            //获取子订单
            $subOrders = OrderSubT::where('order_id', $parentId)
                ->where('state', CommonEnum::STATE_IS_OK)
                ->order('id')
                ->select();
            foreach ($subOrders as $k2 => $v2) {
                //获取消费策略中：订餐未就餐的标准金额和附加金额
                $no_meal_money = 0;
                $no_meal_sub_money = 0;
                $meal_money = 0;
                $meal_sub_money = 0;
                foreach ($detail as $k3 => $v3) {
                    $returnMoney = [];
                    if ($consumptionCount == $v3['number']) {
                        $strategy = $v3['strategy'];
                        foreach ($strategy as $k4 => $v4) {
                            if ($v4['status'] == "no_meals_ordered") {
                                $no_meal_money = $v4['money'];
                                $no_meal_sub_money = $v4['sub_money'];
                            } else if ($v4['status'] == "ordering_meals") {
                                $meal_money = $v4['money'];
                                $meal_sub_money = $v4['sub_money'];
                            }
                        }
                        $returnMoney['meal_money'] = $meal_money;
                        $returnMoney['meal_sub_money'] = $meal_sub_money;
                        $returnMoney['no_meal_money'] = $no_meal_money;
                        $returnMoney['no_meal_sub_money'] = $no_meal_sub_money;
                        if (($no_meal_money + $no_meal_sub_money) >= ($meal_money + $meal_sub_money)) {
                            $returnMoney['consumption_type'] = 'no_meals_ordered';
                            $returnMoney['money'] = $no_meal_money;
                            $returnMoney['sub_money'] = $no_meal_sub_money;
                        } else {
                            $returnMoney['consumption_type'] = 'ordering_meals';
                            $returnMoney['money'] = $meal_money;
                            $returnMoney['sub_money'] = $meal_sub_money;
                        }
                        if ($orderType == OrderEnum::ORDERING_CHOICE && $orderFixed == CommonEnum::STATE_IS_FAIL) {
                            $returnMoney['money'] = $foodMoney;
                            $returnMoney['meal_money'] = $foodMoney;
                            $returnMoney['meal_sub_money'] = $meal_sub_money > $no_meal_sub_money ? $meal_sub_money : $no_meal_sub_money;
                        }
                        $returnMoney['consumption_sort'] = $consumptionCount;
                        $returnMoney['id'] = $v2['id'];
                        $returnMoney['order_sort'] = $k2 + 1;
                        $parentMoney += ($returnMoney['money'] + $returnMoney['sub_money']);
                        array_push($updateSubOrderData, $returnMoney);

                    }
                }
                $consumptionCount++;

            }
            array_push($updateParentOrderData, [
                'id' => $parentId,
                'money' => $parentMoney
            ]);

        }
        // print_r($updateSubOrderData);
        $parent = (new OrderParentT())->saveAll($updateParentOrderData);
        if (!$parent) {
            throw new UpdateException(['msg' => '更新总订单失败']);
        }
        $sub = (new OrderSubT())->saveAll($updateSubOrderData);
        if (!$sub) {
            throw new UpdateException(['msg' => '更新子订单失败']);
        }
    }

}