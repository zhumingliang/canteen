<?php


namespace app\api\service\v2;


use app\api\model\OrderPrepareFoodT;
use app\api\model\OrderPrepareT;
use app\api\service\Token;
use app\lib\enum\OrderEnum;
use app\lib\enum\UserEnum;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;
use think\Db;
use think\Exception;

class OrderService
{
    public function getOrderMoney($params)
    {

        try {
            Db::startTrans();
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
                        return [
                            'type' => 'balance',
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


}