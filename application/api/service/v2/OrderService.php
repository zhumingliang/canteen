<?php


namespace app\api\service\v2;


use app\api\model\CanteenAccountT;
use app\api\model\OrderingV;
use app\api\model\OrderPrepareFoodT;
use app\api\model\OrderPrepareSubT;
use app\api\model\OrderPrepareT;
use app\api\model\PayT;
use app\api\service\CanteenService;
use app\api\service\Token;
use app\api\service\WalletService;
use app\lib\enum\CommonEnum;
use app\lib\enum\OrderEnum;
use app\lib\enum\PayEnum;
use app\lib\enum\StrategyEnum;
use app\lib\enum\UserEnum;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;
use app\lib\exception\UpdateException;
use think\Db;
use think\Exception;

class OrderService
{
    public function getOrderMoney($params)
    {

        try {
            Db::startTrans();
         /*   $canteenId = 300;//Token::getCurrentTokenVar('current_canteen_id');
            $phone = "13480155799";//Token::getCurrentTokenVar('phone');
            $companyId =135;// Token::getCurrentTokenVar('current_company_id');
            $staffId = 7494;//Token::getCurrentTokenVar('staff_id');
            $outsider = 2;//Token::getCurrentTokenVar('outsiders');*/

            $canteenId = Token::getCurrentTokenVar('current_canteen_id');
            $phone = Token::getCurrentTokenVar('phone');
            $companyId = Token::getCurrentTokenVar('current_company_id');
            $staffId = Token::getCurrentTokenVar('staff_id');
            $outsider = Token::getCurrentTokenVar('outsiders');
            $orderType = $params['type'];
            if (!empty($params['orders'])) {
                $orders = json_decode($params['orders'], true);
            } else {
                throw new ParameterException(['msg' => "订单参数异常"]);
            }
            //解析订单信息
            //生成预订单id
            $prepareId = QRcodeNUmber();
            $prepareOderList = [];
            $prepareOrderFoodList = [];
            foreach ($orders as $k => $v) {
                $orderingDate = $v['ordering_date'];
                $dayOrders = $v['order'];
                if (count($dayOrders)) {
                    foreach ($dayOrders as $k2 => $v2) {
                        $foods = $v2['foods'];
                        if (count($foods)) {
                            $prepareOrderId = QRcodeNUmber();
                            $money = 0;
                            foreach ($foods as $k3 => $v3) {
                                $money += $v3['price'] * $v3['count'];
                                array_push($prepareOrderFoodList, [
                                    'prepare_order_id' => $prepareOrderId,
                                    'food_id' => $v3['food_id'],
                                    'price' => $v3['price'],
                                    'name' => $v3['name'],
                                    'count' => $v3['count'],
                                    'm_id' => $v3['menu_id'],
                                    'prepare_id' => $prepareId,
                                ]);

                            }
                            array_push($prepareOderList, [
                                'prepare_id' => $prepareId,
                                'prepare_order_id' => $prepareOrderId,
                                'ordering_date' => $orderingDate,
                                'company_id' => $companyId,
                                'canteen_id' => $canteenId,
                                'staff_id' => $staffId,
                                'phone' => $phone,
                                'ordering_type' => OrderEnum::ORDERING_CHOICE,
                                'dinner_id' => $v2['dinner_id'],
                                'type' => $orderType,
                                'money' => $money,
                                'count' => 1,
                                'outsider' => $outsider
                            ]);

                        }

                    }
                }

            }


            $order = (new OrderPrepareT())->saveAll($prepareOderList);
            if (!$order) {
                throw new SaveException(['msg' => "保存订单失败"]);
            }
            $orderFoods = (new OrderPrepareFoodT())->saveAll($prepareOrderFoodList);
            if (!$orderFoods) {
                throw new SaveException(['msg' => "保存订单菜品失败"]);
            }
            Db::commit();
            return  $prepareId;
            //调用存储过程验证订单信息
            //传入参数：预订单id；
            //返回参数：错误code；错误描述
            if ($outsider == UserEnum::INSIDE) {
                //内部人员
                Db::query('call prepareOrder(:in_prepareId,:in_companyId,:in_canteenId,:in_staffId,@resCode,@resMessage,@balanceType)', [
                    'in_prepareId' => $prepareId,
                    'in_companyId' => $companyId,
                    'in_canteenId' => $canteenId,
                    'in_staffId' => $staffId
                ]);
                $resultSet = Db::query('select @resCode,@resMessage,@balanceType');
                $errorCode = $resultSet[0]['@resCode'];
                $resMessage = $resultSet[0]['@resMessage'];
                $balanceType = $resultSet[0]['@balanceType'];
                if ($errorCode < 0) {
                    if ($errorCode == -3) {
                        Db::rollback();
                        return [
                            'type' => 'balance',
                            'outsider' => $outsider,
                            'money' => $resMessage,
                            'money_type' => $balanceType
                        ];
                    } else {
                        throw new SaveException(['msg' => $resMessage]);
                    }
                }
            } else {
                //外部人员
                Db::query('call prepareOutsiderOrder(:in_prepareId,:in_companyId,:in_canteenId,@resCode,@resMessage)', [
                    'in_prepareId' => $prepareId,
                    'in_companyId' => $companyId,
                    'in_canteenId' => $canteenId
                ]);
                $resultSet = Db::query('select @resCode,@resMessage');
                $errorCode = $resultSet[0]['@resCode'];
                $resMessage = $resultSet[0]['@resMessage'];
                if ($errorCode < 0) {
                    throw new SaveException(['msg' => $resMessage]);
                }
            }
            Db::commit();
            //获取订单金额信息返回给前端
            return [
                'type' => 'order',
                'outsider' => $outsider,
                'prepare_id' => $prepareId,
                'order' => OrderPrepareT::orders($prepareId)
            ];
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    public function updatePrepareOrderCount($id, $count)
    {
        $prepareOrder = OrderPrepareT::order($id);
        if (!$prepareOrder) {
            throw new ParameterException(['msg' => '订单不存在']);

        }
        $outsider = $prepareOrder->outsider;
        $consumptionType = $prepareOrder->consumption_type;
        $fixed = $prepareOrder->fixed;
        $oldCount = $prepareOrder->count;
        if ($outsider == UserEnum::OUTSIDE) {
            return $this->updateOutsiderOrder($prepareOrder, $consumptionType, $oldCount, $count);
        }
        return $this->updateInsiderOrder($prepareOrder, $fixed, $consumptionType, $oldCount, $count);

    }

    private function updateInsiderOrder($order, $fixed, $consumptionType, $oldCount, $newCount)
    {

        if ($consumptionType == "one") {
            if ($newCount == $oldCount) {
                return [
                    'type' => "success",
                    'money' => $order->money + $order->sub_money
                ];
            }

            //检测订单修改数量是否合法
            if ($newCount > $oldCount) {
                $orderedCount = OrderingV::getOrderingCountByWithDinnerID($order->ordering_date, $order->dinner_id, $order->phone);
                $this->checkOrderCount($newCount, $oldCount, $orderedCount, $order->dinner_id, $order->canteen_id, $order->staff_type_id);
            }
            //计算价格
            $newMoney = $order->money / $oldCount * $newCount;
            $newMealMoney = $order->meal_money / $oldCount * $newCount;
            $newSubMoney = $order->sub_money / $oldCount * $newCount;
            $newMealSubMoney = $order->meal_sub_money / $oldCount * $newCount;
            $newNoMealMoney = $order->no_meal_money / $oldCount * $newCount;
            $newNoMealSubMoney = $order->no_meal_sub_money / $oldCount * $newCount;

            if ($newMoney > $order->money) {
                $prepareMoney = OrderPrepareT::ordersMoney($order->prepare_id);
                $checkMoney = $newMoney + $newSubMoney - $order->money - $order->sub_money + $prepareMoney;
                $check = $this->checkBalance($order->staff_id, $order->canteen_id, $checkMoney);
                if (!$check['check']) {
                    return [
                        'type' => "no_balance",
                        'money' => $check['money'],
                        'money_type' => $check['money_type']
                    ];
                }
            }
            $update = OrderPrepareT::update([
                'id' => $order->id,
                'money' => $newMoney,
                'sub_money' => $newSubMoney,
                'meal_money' => $newMealMoney,
                'meal_sub_money' => $newMealSubMoney,
                'no_meal_money' => $newNoMealMoney,
                'no_meal_sub_money' => $newNoMealSubMoney,
                'count' => $newCount
            ]);
            if (!$update) {
                throw new UpdateException(['msg' => "更新失败"]);
            }

            return [
                'type' => "success",
                'money' => $newMoney + $newSubMoney
            ];

        } else {
            if ($newCount < $oldCount) {
                $updateSub = OrderPrepareSubT::where('order_id', $order->id)
                    ->where('sort_code', '>', $newCount)
                    ->update(['state' => CommonEnum::STATE_IS_FAIL]);
                if (!$updateSub) {
                    throw new UpdateException(['msg' => '修改子订单数量']);
                }

                $checkMoney = OrderPrepareSubT::ordersMoney($order->id);

            } else if ($newCount > $oldCount) {
                $orderedCount = OrderingV::getOrderingCountByWithDinnerID($order->ordering_date, $order->dinner_id, $order->phone);
                $strategy = $this->checkOrderCount($newCount, $oldCount, $orderedCount, $order->dinner_id, $order->canteen_id, $order->staff_type_id);
                $increaseCount = $newCount - $oldCount;
                $orderedCount = $orderedCount + $oldCount;
                $strategyMoney = (new \app\api\service\OrderService())->checkConsumptionStrategyTimesMore($strategy, $increaseCount, $orderedCount);
                if ($fixed == CommonEnum::STATE_IS_FAIL) {
                    $foodsMoney = OrderPrepareFoodT::orderMoney($order->prepare_order_id);
                } else {
                    $foodsMoney = array_sum(array_column($strategyMoney, 'money'));
                }
                $addBalance = $foodsMoney * $increaseCount + array_sum(array_column($strategyMoney, 'sub_money'));
                $prepareMoney = OrderPrepareSubT::ordersMoney($order->id);
                $checkMoney = $addBalance + $prepareMoney;
                $check = $this->checkBalance($order->staff_id, $order->canteen_id, $checkMoney);
                if (!$check['check']) {
                    return [
                        'type' => "no_balance",
                        'money' => $check['balance'],
                        'money_type' => $check['money_type']
                    ];
                }


                //处理子订单
                $subOrderDataList = [];
                foreach ($strategyMoney as $k => $v) {
                    $money = $fixed == CommonEnum::STATE_IS_FAIL ? $foodsMoney : $v['money'];
                    array_push($subOrderDataList, [
                        'order_id' => $order->id,
                        'state' => CommonEnum::STATE_IS_OK,
                        'money' => $money,
                        'sub_money' => $v['sub_money'],
                        'meal_money' => $v['meal_money'],
                        'meal_sub_money' => $v['meal_sub_money'],
                        'no_meal_money' => $v['no_meal_money'],
                        'no_meal_sub_money' => $v['no_meal_sub_money'],
                        'count' => 1,
                        'consumption_type' => $v['consumption_type'],
                        'sort_code' => $v['number'],
                        'consumption_sort' => $v['number'],
                    ]);
                }
                $list = (new OrderPrepareSubT())->saveAll($subOrderDataList);
                if (!$list) {
                    throw new SaveException(['msg' => '生成子订单失败']);
                }
            } else {
                return [
                    'type' => "success",
                    'money' => OrderPrepareSubT::ordersMoney($order->id),
                    'orders' => OrderPrepareSubT::orders($order->id)
                ];
            }


            OrderPrepareT::update([
                'count' => $newCount,
                'money' => $checkMoney,
            ], [
                'id' => $order->id
            ]);
            return [
                'type' => "success",
                'money' => $checkMoney,
                'orders' => OrderPrepareSubT::orders($order->id)
            ];
        }

    }


    private function checkOrderCount($newCount, $oldCount, $orderedCount, $dinnerId, $canteenId, $staffTypeId)
    {
        //检测订单修改数量是否合法
        $checkCount = $orderedCount + $newCount;
        $strategy = (new CanteenService())->getStaffConsumptionStrategy($canteenId, $dinnerId, $staffTypeId);
        if (!$strategy) {
            throw new ParameterException(['msg' => '当前用户消费策略不存在']);
        }
        if ($checkCount > $strategy->ordered_count) {
            throw new UpdateException(['msg' => '超出最大订餐数量，不能预定']);
        }
        return $strategy;
    }

    public
    function checkBalance($staffId, $canteenId, $money)
    {
        $balance = (new WalletService())->getUserBalanceWithProcedure($staffId);
        if ($balance >= $money) {
            return [
                'check' => true
            ];
        }
        //获取账户设置，检测是否可预支消费
        $canteenAccount = CanteenAccountT::where('c_id', $canteenId)->find();
        if (!$canteenAccount || $canteenAccount->type == OrderEnum::OVERDRAFT_NO) {
            return [
                'check' => false,
                'money' => $balance,
                'money_type' => 'user_balance'
            ];
        }

        if ($canteenAccount->limit_money < ($money - $balance)) {
            return [
                'check' => false,
                'money' => $canteenAccount->limit_money + $balance,
                'money_type' => 'overdraw'
            ];
        }
        return [
            'check' => true
        ];

    }


    private function updateOutsiderOrder($order, $consumptionType, $oldCount, $newCount)
    {
        if ($consumptionType == "one") {

            $newMoney = $order->money / $oldCount * $newCount;
            $newSubMoney = $order->sub_money / $oldCount * $newCount;
            if ($newCount == $oldCount) {
                return [
                    'type' => "success",
                    'money' => $newMoney + $newSubMoney
                ];
            }
            OrderPrepareT::update([
                'count' => $newCount,
                'state' => $newCount ? CommonEnum::STATE_IS_OK : CommonEnum::STATE_IS_FAIL,
                'money' => $newMoney,
                'sub_money' => $newSubMoney
            ], [
                'id' => $order->id
            ]);
            return [
                'type' => "success",
                'money' => $newMoney + $newSubMoney
            ];
        } else {
            $dataList = [];
            if ($newCount > $oldCount) {
                $subOrder = OrderPrepareSubT::where('order_id', $order->id)
                    ->where('state', CommonEnum::STATE_IS_OK)
                    ->order('sort_code desc')
                    ->find();

                $sortCode = $subOrder->sort_code;
                $consumptionSort = $subOrder->consumption_sort;
                for ($i = 1; $i <= $newCount - $oldCount; $i++) {
                    array_push($dataList, [
                        'order_id' => $order->id,
                        'state' => CommonEnum::STATE_IS_OK,
                        'money' => $subOrder->money,
                        'meal_money' => $subOrder->meal_money,
                        'count' => 1,
                        'consumption_type' => 'ordering_meals',
                        'sort_code' => $sortCode + $i,
                        'consumption_sort' => $consumptionSort + $i,
                    ]);
                }
            } else if ($newCount < $oldCount) {
                $subOrders = OrderPrepareSubT::where('order_id', $order->id)
                    ->where('state', CommonEnum::STATE_IS_OK)
                    ->order('sort_code')
                    ->select();
                foreach ($subOrders as $k => $v) {
                    if ($v['sort_code'] > $newCount) {
                        array_push($dataList, [
                            'id' => $v['id'],
                            'state' => CommonEnum::STATE_IS_FAIL
                        ]);
                    }

                }
            } else {
                return [
                    'type' => "success",
                    'money' => OrderPrepareSubT::ordersMoney($order->id),
                    'orders' => OrderPrepareSubT::orders($order->id)
                ];
            }
            $save = (new OrderPrepareSubT())->saveAll($dataList);
            if (!$save) {
                throw new SaveException(['msg' => '修改子订单失败']);
            }
            $orderMoney = OrderPrepareSubT::ordersMoney($order->id);
            OrderPrepareT::update([
                'count' => $newCount,
                'money' => $orderMoney,
            ], [
                'id' => $order->id
            ]);
            return [
                'type' => "success",
                'money' => $orderMoney,
                'orders' => OrderPrepareSubT::orders($order->id)
            ];
        }
    }

    public function checkOrderMoney($params)
    {
        $canteenId = Token::getCurrentTokenVar('current_canteen_id');
        $companyId = Token::getCurrentTokenVar('current_company_id');
        $staffId = Token::getCurrentTokenVar('staff_id');
        $orderingDate = $params['ordering_date'];
        $orderMoney = $params['order_money'];
        $dinnerId = $params['dinner_id'];

        Db::query('call checkPrepareOrder(:in_companyId,:in_canteenId,:in_dinnerID,:in_staffId,:in_orderMoney,:in_orderingDate,@resCode,@resMessage,@balanceType,@fixedBalance)', [
            'in_companyId' => $companyId,
            'in_canteenId' => $canteenId,
            'in_dinnerID' => $dinnerId,
            'in_staffId' => $staffId,
            'in_orderMoney' => $orderMoney,
            'in_orderingDate' => $orderingDate,
        ]);
        $resultSet = Db::query('select @resCode,@resMessage,@balanceType,@fixedBalance');
        $errorCode = $resultSet[0]['@resCode'];
        $resMessage = $resultSet[0]['@resMessage'];
        $balanceType = $resultSet[0]['@balanceType'];
        $fixedBalance = $resultSet[0]['@fixedBalance'];
        if ($errorCode == 0) {
            return [
                'check' => CommonEnum::STATE_IS_OK,
                'fixedMoney' => $resMessage,
            ];
        }
        if ($errorCode == -3) {
            return [
                'check' => CommonEnum::STATE_IS_FAIL,
                'fixedType' => $balanceType,
                'fixedMoney' => $resMessage,
                'fixedBalance' => $fixedBalance,
            ];
        }
        throw new SaveException(['msg' => $resMessage]);

    }

    public function submitOrder($prepareId, $addressId, $deliveryFee, $remark)
    {
        $canteenId = Token::getCurrentTokenVar('current_canteen_id');
        $staffId = Token::getCurrentTokenVar('staff_id');

        $outsider =Token::getCurrentTokenVar('outsiders');
        try {
            Db::startTrans();
            Db::query('call submitPrepareOrder(:in_prepareId,:in_userCanteenId,:in_userStaffId,:in_addressId,:in_deliveryFee,:in_orderRemark,@resCode,@resMessage,@balanceType,@returnOrderMoney,@returnConsumptionType)', [
                'in_prepareId' => $prepareId,
                'in_userCanteenId' => $canteenId,
                'in_userStaffId' => $staffId,
                'in_addressId' => $addressId,
                'in_deliveryFee' => $deliveryFee,
                'in_orderRemark' => $remark
            ]);
            $resultSet = Db::query('select @resCode,@resMessage,@balanceType,@returnOrderMoney,@returnConsumptionType');
            $errorCode = $resultSet[0]['@resCode'];
            $resMessage = $resultSet[0]['@resMessage'];
            $balanceType = $resultSet[0]['@balanceType'];
            $returnOrderMoney = $resultSet[0]['@returnOrderMoney'];
            $returnConsumptionType = $resultSet[0]['@returnConsumptionType'];
            if ($errorCode < 0) {
                Db::rollback();
                if ($errorCode == -3) {
                    return [
                        'type' => 'balance',
                        'money' => $resMessage,
                        'money_type' => $balanceType
                    ];
                } else {
                    throw new SaveException(['msg' => $resMessage]);
                }
            }

            Db::commit();
            if ($outsider == UserEnum::OUTSIDE) {
                //生成微信支付订单
                $payMoney = $returnOrderMoney;
                if ($payMoney <= 0) {
                    throw new ParameterException(['msg' => '支付金额异常，为0']);

                }
                $companyId = Token::getCurrentTokenVar('current_company_id');
                $openid = Token::getCurrentOpenid();
                $u_id = Token::getCurrentUid();
                $username = Token::getCurrentTokenVar('nickName');
                $phone = Token::getCurrentPhone();

                $payOrder = $this->savePayOrder($prepareId, $companyId, $openid, $u_id, $payMoney, $phone, $username, $returnConsumptionType);
                return [
                    'type' => 'success',
                    'prepare_id' => $payOrder,
                ];
            }
            return [
                'type' => 'success',
                'prepare_id' => $prepareId,
            ];


        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }

    }


    public
    function savePayOrder($prepareId, $company_id, $openid, $u_id, $money, $phone, $username, $times = 'one')
    {
        $data = [
            'openid' => $openid,
            'company_id' => $company_id,
            'u_id' => $u_id,
            'order_num' => makeOrderNo(),
            'money' => $money,
            'status' => 'paid_fail',
            'method_id' => PayEnum::PAY_METHOD_WX,
            'prepare_id' => $prepareId,
            'type' => 'canteen',
            'phone' => $phone,
            'times' => $times,
            'username' => $username,
            'outsider' => UserEnum::OUTSIDE,
            'order_type' => 'pre'

        ];
        $order = PayT::create($data);
        if (!$order) {
            throw new SaveException();
        }
        return [
            'id' => $order->id
        ];
    }


}