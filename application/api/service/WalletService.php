<?php


namespace app\api\service;


use app\api\model\RechargeCashT;
use app\api\model\RechargeV;
use app\api\model\UserBalanceV;
use app\lib\enum\CommonEnum;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;

class WalletService
{
    public function rechargeCash($params)
    {
        $detail = json_decode($params['detail'], true);
        if (empty($detail)) {
            throw new ParameterException(['msg' => '充值用户信息格式错误']);
        }
        $company_id = Token::getCurrentTokenVar('company_id');
        $admin_id = Token::getCurrentUid();
        $data = $this->prefixDetail($company_id, $admin_id, $detail, $params['money'], $params['remark']);
        $cash = (new RechargeCashT())->saveAll($data);
        if (!$cash) {
            throw new SaveException();
        }
    }

    public function rechargeCashUpload($cash_excel)
    {
        $company_id = Token::getCurrentTokenVar('company_id');
        $admin_id = Token::getCurrentUid();
        $data = (new ExcelService())->saveExcel($cash_excel);
        $dataList = $this->prefixUploadData($company_id, $admin_id, $data);
        $cash = (new RechargeCashT())->saveAll($dataList);
        if (!$cash) {
            throw new SaveException();
        }
    }

    private function prefixUploadData($company_id, $admin_id, $data)
    {
        $dataList = [];
        foreach ($data as $k => $v) {
            if ($k == 1) {
                continue;
            }
            array_push($dataList, [
                'admin_id' => $admin_id,
                'company_id' => $company_id,
                'phone' => $v[0],
                'card_num' => $v[1],
                'money' => $v[2],
                'remark' => $v[3]
            ]);
        }
        return $dataList;

    }

    private function prefixDetail($company_id, $admin_id, $detail, $money, $remark)
    {
        $dataList = [];
        foreach ($detail as $k => $v) {
            $data = [];
            $data['company_id'] = $company_id;
            $data['money'] = $money;
            $data['phone'] = $v['phone'];
            $data['card_num'] = $v['card_num'];
            $data['state'] = CommonEnum::STATE_IS_OK;
            $data['admin_id'] = $admin_id;
            $data['remark'] = $remark;
            array_push($dataList, $data);
        }
        return $dataList;
    }

    public function rechargeRecords($time_begin, $time_end,
                                    $page, $size, $type, $admin_id, $username)
    {
        $company_id = Token::getCurrentTokenVar('company_id');
        $records = RechargeV::rechargeRecords($time_begin, $time_end,
            $page, $size, $type, $admin_id, $username, $company_id);
        return $records;

    }

    public function usersBalance($page, $size, $department_id, $user, $phone)
    {
        $company_id = Token::getCurrentTokenVar('company_id');
        $users = UserBalanceV::usersBalance($page, $size, $department_id, $user, $phone, $company_id);
        return $users;
    }

}