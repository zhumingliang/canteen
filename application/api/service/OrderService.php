<?php
/**
 * Created by PhpStorm.
 * User: 明良
 * Date: 2019/9/5
 * Time: 11:14
 */

namespace app\api\service;


use app\api\model\CanteenAccountT;
use app\api\model\OrderingV;
use app\lib\enum\OrderEnum;
use app\lib\exception\SaveException;
use think\Db;
use think\Exception;

class OrderService
{

    public function personChoice($params)
    {
        try {
            Db::startTrans();
            $type = $params['type'];
            $dinner_id = $params['dinner_id'];
            $ordering_date = $params['ordering_date'];
            $count = $params['count'];
            $money = $this->getOrderingMoney($params['detail']);
            $u_id = Token::getCurrentUid();
            $canteen_id = Token::getCurrentTokenVar('current_canteen_id');
            $this->checkUserCanOrder($u_id, $dinner_id, $ordering_date, $canteen_id, $count);
            if (!$this->checkBalance($u_id, $canteen_id, $money)) {
                throw new  SaveException(['msg' => '余额不足，请先充值']);
            }

            //Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }

    }

    public function getOrderingMoney($detail)
    {
        $money = 0;
        return $money;
    }

    public function checkBalance($u_id, $canteen_id, $money)
    {
        $balance = 100;
        if ($balance >= $money) {
            return true;
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
        return true;

    }

    public function checkUserCanOrder($u_id, $dinner_id, $day, $canteen_id, $count)
    {
        //获取用户指定日期订餐信息
        $record = OrderingV::getRecordForDayOrdering($u_id, $day, $dinner_id);
        if ($record) {
            throw new SaveException(['msg' => '本餐次今日在' . $record->canteen . '已经预定，不能重复预定']);
        }
        return $this->checkOrderingCount($canteen_id, $dinner_id, $count);

    }

    private function checkOrderingCount($canteen_id, $dinner_id, $count)
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

}