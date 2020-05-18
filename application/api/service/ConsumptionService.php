<?php


namespace app\api\service;


use app\api\model\CanteenAccountT;
use app\api\model\CompanyStaffT;
use app\api\model\DinnerT;
use app\api\model\MachineT;
use app\api\model\OrderingV;
use app\api\model\OrderT;
use app\api\model\ShopOrderQrcodeT;
use app\api\model\ShopOrderT;
use app\api\model\StaffQrcodeT;
use app\api\model\UserT;
use app\lib\enum\CommonEnum;
use app\lib\enum\OrderEnum;
use app\lib\enum\PayEnum;
use app\lib\exception\AuthException;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;
use app\lib\exception\UpdateException;
use GatewayClient\Gateway;
use think\Db;
use think\Exception;
use zml\tp_tools\Redis;

class ConsumptionService
{
    public function staff($type, $code)
    {
        try {
            Db::startTrans();
            $company_id = Token::getCurrentTokenVar('company_id');
            $belong_id = Token::getCurrentTokenVar('belong_id');
            $res = array();
            if ($type == 'canteen') {
                // $res = $this->handelCanteen($code, $company_id, $staff_id, $belong_id);
                $res = $this->handelCanteenByProcedure($code, $company_id, $belong_id);
            } else if ($type == 'shop') {
                $res = $this->handelShop($code);
            }
            Db::commit();
            return $res;
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    public function consumptionWithFace($face_time, $face_id, $phone)
    {
        //检测人脸识别机是否合法
        $machine = $this->checkMachine($face_id);
        $return_data = $this->handelCanteenByFaceProcedure($phone, $face_time, $machine['company_id'], $machine['belong_id']);
        Gateway::sendToUid($machine['id'], json_encode($return_data));
        return $return_data;
    }

    private function checkMachine($face_id)
    {
        $machine = MachineT::where('face_id', $face_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->where('machine_type', 'canteen')
            ->find();
        if (empty($machine)) {
            throw new AuthException([
                'msg' => '人脸识别机不合法'
            ]);
        }
        //检测消费机是否在线
        if (!Gateway::isUidOnline($machine['id'])) {
            throw new AuthException(['msg' => '消费机掉线']);
        }
        return $machine;

    }

    public function handelShop($code)
    {
        $shopCode = ShopOrderQrcodeT::where('code', $code)
            ->find();
        if (!$shopCode) {
            throw new ParameterException(['errorCode' => 12001, 'msg' => '提货码不存在']);
        }
        if (time() > strtotime($shopCode->end_time)) {
            throw new ParameterException(['errorCode' => 12002, 'msg' => '提货码已过期']);
        }
        if ($shopCode->state == CommonEnum::STATE_IS_OK) {
            throw new ParameterException(['errorCode' => 12003, 'msg' => '提货码已经使用，不能重复使用']);
        }
        $order_id = $shopCode->o_id;
        $order = ShopOrderT::orderInfoForMachine($order_id);
        if ($order->state != CommonEnum::STATE_IS_OK) {
            throw new ParameterException(['errorCode' => 12004, 'msg' => '订单状态异常']);
        }
        if ($order->used == CommonEnum::STATE_IS_OK) {
            throw new ParameterException(['errorCode' => 12005, 'msg' => '订单已提货，不能重复提货']);
        }
        $order->used = CommonEnum::STATE_IS_OK;
        $shopCode->state = CommonEnum::STATE_IS_OK;
        $order->used_time = date('Y-m-d H:i:s');
        $res = $order->save();
        if (!$res) {
            throw new UpdateException(['msg' => '修改订单状态失败']);
        }
        $shopCodeRes = $shopCode->save();
        if (!$shopCodeRes) {
            throw new UpdateException(['msg' => '修改提货吗状态失败']);
        }
        //获取用户信息
        $staff = $this->getStaffInfo($order->staff_id);
        return [
            'money' => $order->money,
            'department' => $staff['department'],
            'username' => $staff['username'],
            'products' => $order->foods
        ];

    }

    private function getStaffInfo($staff_id)
    {
        $staff = CompanyStaffT::staffWithDepartment($staff_id);
        if (!$staff) {
            throw new ParameterException(['msg' => '当前用户不存在']);
        }
        $user = UserT::where('phone', $staff->phone)->find();
        return [
            'staff_type_id' => $staff->t_id,
            'department_id' => $staff->d_id,
            'department' => $staff->department->name,
            'u_id' => $user->id,
            'phone' => $staff->phone,
            'username' => $staff->username
        ];
    }


    private function handelCanteenByFaceProcedure($phone, $face_time, $company_id, $canteen_id)
    {
        Db::query('call canteenConsumptionFace(:in_companyID,:in_canteenID,:in_faceTime,:in_phone,
                @currentOrderID,@currentConsumptionType,@resCode,@resMessage,@returnBalance,
                @returnDinner,@returnDepartment,@returnUsername,@returnPrice,@returnMoney)',
            [
                'in_companyID' => $company_id,
                'in_canteenID' => $canteen_id,
                'in_faceTime' => $face_time,
                'in_phone' => $phone,
            ]);
        $resultSet = Db::query('select @currentOrderID,@currentConsumptionType,
        @resCode,@resMessage,@returnBalance,@returnDinner,
        @returnDepartment,@returnUsername,@returnPrice,@returnMoney');
        $errorCode = $resultSet[0]['@resCode'];
        $resMessage = $resultSet[0]['@resMessage'];
        $consumptionType = $resultSet[0]['@currentConsumptionType'];
        $orderID = $resultSet[0]['@currentOrderID'];
        $balance = $resultSet[0]['@returnBalance'];
        $dinner = $resultSet[0]['@returnDinner'];
        $department = $resultSet[0]['@returnDepartment'];
        $username = $resultSet[0]['@returnUsername'];
        $price = $resultSet[0]['@returnPrice'];
        $money = $resultSet[0]['@returnMoney'];
        if ($errorCode != 0) {
            // throw  new SaveException(['errorCode' => $errorCode, 'msg' => $resMessage]);
            return [
                'errorCode' => $errorCode,
                'msg' => $resMessage,
                'type' => 'canteen',
                'data' => [
                    'username' => $username
                ]
            ];
        }
        $order = OrderT::infoToCanteenMachine($orderID);
        $order['remark'] = $consumptionType == 1 ? "订餐消费" : "未订餐消费";
        //获取订单信息返回
        return [
            'errorCode' => $errorCode,
            'msg' => $resMessage,
            'type' => 'canteen',
            'data' => [
                'create_time' => date('Y-m-d H:i:s'),
                'dinner' => $dinner,
                'price' => $price,
                'money' => $money,
                'department' => $department,
                'username' => $username,
                'type' => $consumptionType,
                'balance' => $balance,
                'remark' => $consumptionType == 1 ? "订餐消费" : "未订餐消费",
                'products' => $order['foods']
            ]
        ];
    }

    private function handelCanteenByProcedure($code, $company_id, $canteen_id)
    {
        Db::query('call canteenConsumption(:in_companyID,:in_canteenID,:in_Qrcode,
                @currentOrderID,@currentConsumptionType,@resCode,@resMessage,@returnBalance,
                @returnDinner,@returnDepartment,@returnUsername,@returnPrice,@returnMoney)',
            [
                'in_companyID' => $company_id,
                'in_canteenID' => $canteen_id,
                'in_Qrcode' => $code
            ]);
        $resultSet = Db::query('select @currentOrderID,@currentConsumptionType,@resCode,@resMessage,@returnBalance,@returnDinner,@returnDepartment,@returnUsername,@returnPrice,@returnMoney');
        $errorCode = $resultSet[0]['@resCode'];
        $resMessage = $resultSet[0]['@resMessage'];
        $consumptionType = $resultSet[0]['@currentConsumptionType'];
        $orderID = $resultSet[0]['@currentOrderID'];
        $balance = $resultSet[0]['@returnBalance'];
        $dinner = $resultSet[0]['@returnDinner'];
        $department = $resultSet[0]['@returnDepartment'];
        $username = $resultSet[0]['@returnUsername'];
        $price = $resultSet[0]['@returnPrice'];
        $money = $resultSet[0]['@returnMoney'];
        if ($errorCode != 0) {
            throw  new SaveException(['errorCode' => $errorCode, 'msg' => $resMessage]);
        }
        $order = OrderT::infoToCanteenMachine($orderID);
        $order['remark'] = $consumptionType == 1 ? "订餐消费" : "未订餐消费";
        //获取订单信息返回
        return [
            'dinner' => $dinner,
            'price' => $price,
            'money' => $money,
            'department' => $department,
            'username' => $username,
            'type' => $consumptionType,
            'balance' => $balance,
            'remark' => $consumptionType == 1 ? "订餐消费" : "未订餐消费",
            'products' => $order['foods']
        ];
    }


    private function handelCanteen($code, $company_id, $staff_id, $canteen_id)
    {
        //获取用户信息
        $staff = $this->getStaffInfo($staff_id);
        $staff_type_id = $staff['staff_type_id'];
        $department_id = $staff['department_id'];
        //获取当前餐次
        $dinner = $this->getNowDinner($canteen_id);
        //检测用户二维码是否过期
        $this->checkCanteenQRCode($staff_id, $code);
        //检测该用户是否有订单
        $order = $this->checkCanteenOrder($canteen_id, $staff_id, $dinner['id'], $dinner['fixed']);
        if (!$order) {
            //没有订餐信息进行未就餐订餐处理
            $order = $this->prefixNoOrdering($staff['u_id'], date('Y-m-d'), $dinner['id'], $dinner['name'], $canteen_id, $staff_type_id, $company_id, $staff['phone'], $staff_id, $department_id);
            $remark = "未订餐消费";
            $foods = array();
            $type = 2;
        } else {
            $foods = $order->foods;
            $remark = "订餐消费";
            $type = 1;
        }
        return [
            'dinner' => $dinner['name'],
            'price' => $order->money,
            'money' => $order->money + $order->sub_money,
            'department' => $staff['department'],
            'username' => $staff['username'],
            'type' => $type,
            'balance' => (new WalletService())->getUserBalance($company_id, $staff['phone']),
            'remark' => $remark,
            'products' => $foods
        ];
    }

    private function checkCanteenQRCode($staff_id, $code)
    {
        $QRCode = StaffQrcodeT::where('s_id', $staff_id)
            ->where('code', $code)
            ->find();
        if (empty($QRCode)) {
            throw new ParameterException(['errorCode' => 11008, 'msg' => '电子饭卡不存在']);
        }
        if (strtotime($QRCode->expiry_date) < time()) {
            throw new ParameterException(['errorCode' => 11009, 'msg' => '电子饭卡已过期']);
        }
    }

    private function checkCanteenOrder($canteen_id, $staff_id, $dinner_id, $dinner_fixed)
    {
        $order = OrderT::infoToMachine($canteen_id, $staff_id, $dinner_id);
        if ($order) {
            //修改订单状态：改为已经就餐
            $order->used = CommonEnum::STATE_IS_OK;
            $order->used_time = date('Y-m-d H:i:s');
            //检测订餐扣费方式是否未订餐未就餐
            //订餐未就餐则改为订餐就餐，并将相应的金额修正
            if ($order->consumption_type == 'no_meals_ordered') {
                $order->consumption_type = 'ordering_meals';
                $order->sub_money = $order->meal_sub_money;
                if ($dinner_fixed == CommonEnum::STATE_IS_FAIL) {
                    $order->money = $order->meal_money;
                }
            }
            $res = $order->save();
            if (!$res) {
                throw new UpdateException(['msg' => '处理订单状态失败']);
            }
        }
        return $order;
    }


    private function prefixNoOrdering($u_id, $day, $dinner_id, $dinner, $canteen_id, $staff_type_id, $company_id, $phone, $staff_id, $department_id)
    {
        //获取用户指定日期订餐数量
        $consumptionCount = OrderingV::getRecordForDayOrdering($u_id, $day, $dinner);
        $strategies = (new CanteenService())->getStaffConsumptionStrategy($canteen_id, $dinner_id, $staff_type_id);
        //获取消费策略中订餐金额
        $money = $this->checkConsumptionStrategy($strategies, 1, $consumptionCount);
        $pay_way = $this->checkBalance($company_id, $canteen_id, $phone, $money['money'] + $money['sub_money']);
        if (!$pay_way) {
            throw new SaveException(['errorCode' => 11010, 'msg' => '余额不足']);
        }
        $data['u_id'] = $u_id;
        $data['c_id'] = $canteen_id;
        $data['d_id'] = $dinner_id;
        $data['staff_type_id'] = $staff_type_id;
        $data['department_id'] = $department_id;
        $data['staff_id'] = $staff_id;
        $data['company_id'] = $company_id;
        $data['ordering_date'] = date('Y-m-d');
        $data['count'] = 1;
        $data['order_num'] = makeOrderNo();
        $data['ordering_type'] = OrderEnum::ORDERING_NO;
        $data['money'] = $money['money'];
        $data['sub_money'] = $money['sub_money'];
        $data['consumption_type'] = 'unordered_meals';
        $data['pay_way'] = $pay_way;
        $data['used'] = CommonEnum::STATE_IS_OK;
        $data['used_time'] = date('Y-m-d H:i:s');
        $data['pay'] = CommonEnum::STATE_IS_OK;
        $order = OrderT::create($data);
        if (!$order) {
            throw new SaveException(['msg' => '保存订单失败']);
        }
        return $order;
    }


    public
    function checkBalance($company_id, $canteen_id, $phone, $money)
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

    private
    function checkConsumptionStrategy($strategies, $orderCount, $consumptionCount)
    {
        if ($strategies->unordered_meals == CommonEnum::STATE_IS_FAIL) {
            throw new SaveException(['errorCode' => 11011, 'msg' => '消费受限制-未订餐']);
        }
        if (!$strategies) {
            throw new SaveException(['errorCode' => 11004, 'msg' => '饭堂消费策略没有设置']);
        }
        if ($orderCount > $strategies->ordered_count) {
            throw new SaveException(['errorCode' => 11005, 'msg' => '订餐数量超过最大订餐数量，最大订餐数量为：' . $strategies->ordered_count]);
        }
        if ($consumptionCount >= $strategies->consumption_count) {
            throw new SaveException(['errorCode' => 11006, 'msg' => '消费次数已达到上限，最大消费次数为：' . $strategies->consumption_count]);
        }
        $detail = $strategies->detail;
        if (empty($detail)) {
            throw new ParameterException(['errorCode' => 11007, 'msg' => "消费策略设置异常"]);
        }
        //获取消费策略中：未订餐就餐的标准金额和附加金额
        $returnMoney = [];
        foreach ($detail as $k => $v) {
            if (($consumptionCount + 1) == $v['number']) {
                $strategy = $v['strategy'];
                foreach ($strategy as $k2 => $v2) {
                    if ($v2['status'] == "unordered_meals") {
                        $returnMoney['money'] = $v2['money'];
                        $returnMoney['sub_money'] = $v2['sub_money'];
                        break;
                    }
                }
                break;
            }
        }
        if (empty($returnMoney)) {
            throw new ParameterException(['errorCode' => 11003, 'msg' => '未订餐就餐失败，消费策略未设置']);
        }
        return $returnMoney;
    }


    private
    function getNowDinner($canteen_id)
    {
        $dinners = DinnerT::dinners($canteen_id);
        if ($dinners->isEmpty()) {
            throw new ParameterException(['errorCode' => 11001, 'msg' => '饭堂未设置餐次信息']);
        }
        $dinner = array();
        foreach ($dinners as $k => $v) {
            if (strtotime($v->meal_time_begin) <= time() &&
                time() <= strtotime($v->meal_time_end)) {
                $dinner['id'] = $v->id;
                $dinner['name'] = $v->name;
                $dinner['fixed'] = $v->fixed;
                break;
            }
        }
        if (empty($dinner)) {
            throw new ParameterException(['errorCode' => 11002, 'msg' => '当前时间不在就餐时间内']);
        }
        return $dinner;
    }

    public function confirmOrder($order_id)
    {
        $phone = Token::getCurrentPhone();
        $canteenID = Token::getCurrentTokenVar('current_canteen_id');
        Db::query('call canteenConsumptionWX(:in_orderID,:in_userPhone,
               @resCode,@resMessage,@dinnerID)',
            [
                'in_orderID' => $order_id,
                'in_userPhone' => $phone
            ]);
        $resultSet = Db::query('select @resCode,@resMessage,@dinnerID');
        $errorCode = $resultSet[0]['@resCode'];
        $resMessage = $resultSet[0]['@resMessage'];
        if ($errorCode != 0) {
            return [
                'errorCode' => $errorCode,
                'msg' => $resMessage,
                'type' => 'canteen',
                'data' => [
                    'username' => ''
                ]
            ];
        }
        return [
            'errorCode' => $errorCode,
            'msg' => $resMessage,
            'type' => 'canteen',
            'data' => [
                'create_time' => date('Y-m-d H:i:s'),
            ]
        ];
    }

    public function saveRedisOrderCode($canteen_id, $dinner_id, $order_id)
    {

        $hash = "$canteen_id:$dinner_id";
        $code = Redis::instance()->hLan($hash);
        $newCode = $code + 1;
        Redis::instance()->hSet($hash, $order_id, $newCode);
        return str_pad($newCode, 5, "0", STR_PAD_LEFT);;

    }
}