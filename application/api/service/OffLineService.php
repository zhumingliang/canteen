<?php


namespace app\api\service;


use app\api\model\CompanyStaffT;
use app\api\model\ConsumptionStrategyT;
use app\api\model\OrderParentT;
use app\api\model\OrderT;
use app\api\model\UserBalanceV;
use app\lib\enum\CommonEnum;
use app\lib\enum\StrategyEnum;

class OffLineService
{
    public function orderForOffline()
    {
        $canteenId = Token::getCurrentTokenVar('belong_id');
        $strategy = ConsumptionStrategyT::where('c_id', $canteenId)
            ->where('state', CommonEnum::STATE_IS_OK)->find();
        $consumptionType = $strategy->consumption_type;
        $consumptionDate = date('Y-m-d');
        if ($consumptionType == StrategyEnum::CONSUMPTION_TIMES_ONE) {
            return OrderT::canteenOrders($canteenId, $consumptionDate);
        } else {
            return OrderParentT::canteenOrders($canteenId, $consumptionDate);
        }

    }

    public function staffsForOffline()
    {
        $companyId =  Token::getCurrentTokenVar('company_id');
        $staffs = CompanyStaffT::staffsForOffLine($companyId);
        $staffBalance = UserBalanceV::balanceForOffLine($companyId);

        foreach ($staffs as $k => $v) {
            $balance = 0;
            foreach ($staffBalance as $k2 => $v2) {
                if ($v['id'] == $v2['staff_id']) {
                    $balance = $v2['balance'];
                    unset($staffBalance[$k2]);
                    break;
                }

            }
            $staffs[$k]['balance'] = $balance;

        }
        return $staffs;

    }

}