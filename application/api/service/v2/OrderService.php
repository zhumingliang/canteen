<?php


namespace app\api\service\v2;


use app\api\model\OrderPrepareFoodT;
use app\api\model\OrderPrepareT;
use app\api\service\Token;
use app\lib\enum\OrderEnum;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;
use think\Db;
use think\Exception;

class OrderService
{
    public function getOrderMoney($params)
    {

        try {

            $canteenId = 1;// Token::getCurrentTokenVar('current_canteen_id');
            $phone = "18956225230";//Token::getCurrentTokenVar('phone');
            $companyId = 1;//Token::getCurrentTokenVar('current_company_id');
            $staffId = 1;//Token::getCurrentTokenVar('staff_id');
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
            $prepareOderFoodList = [];
            foreach ($orders as $k => $v) {
                $orderingDate = $v['ordering_date'];
                $dayOrders = $v['order'];
                if (count($dayOrders)) {
                    foreach ($dayOrders as $k2 => $v2) {
                        $foods = $v2['foods'];
                        if (count($foods)) {
                            $prepareOrderId = QRcodeNUmber();
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
                                'type' => $orderType
                            ]);
                            foreach ($foods as $k3 => $v3) {
                                array_push($prepareOderFoodList, [
                                    'prepare_order_id' => $prepareOrderId,
                                    'food_id' => $v3['food_id'],
                                    'price' => $v3['price'],
                                    'name' => $v3['name'],
                                    'count' => $v3['count'],
                                    'm_id' => $v3['menu_id'],
                                ]);

                            }

                        }

                    }
                }

            }

            Db::startTrans();
            $order = (new OrderPrepareT())->saveAll($prepareOderList);
            if (!$order) {
                throw new SaveException(['msg' => "保存订单失败"]);
            }
            $orderFoods = (new OrderPrepareFoodT())->saveAll($prepareOderFoodList);
            if (!$orderFoods) {
                throw new SaveException(['msg' => "保存订单菜品失败"]);
            }
            //调用存储过程验证订单信息
            //传入参数：预订单id；
            //返回参数：错误code；错误描述
            $resCode = 0;
            $resMessage = "";
            $returnBalance = 0;
            $resultSet = Db::query('call prepareOrder(:in_prepareId,:out_resCode,:out_resMessage,:returnBalance)', [
                'in_prepareId' => [$prepareId, \PDO::PARAM_INT],
                'out_resCode' => [$resCode, \PDO::PARAM_INPUT_OUTPUT],
                'out_resMessage' => [$resMessage, \PDO::PARAM_INPUT_OUTPUT],
                'out_returnBalance' => [$returnBalance, \PDO::PARAM_INPUT_OUTPUT],
            ]);
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }
    }


}