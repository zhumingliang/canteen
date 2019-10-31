<?php


namespace app\api\service;


use app\api\model\RechargeCashT;
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
        $company_id = sToken::getCurrentTokenVar('company_id');
        $admin_id = Token::getCurrentUid();
        $data = $this->prefixDetail($company_id, $admin_id, $detail, $params['money'], $params['remark']);
        $cash = (new RechargeCashT())->saveAll($data);
        if (!$cash) {
            throw new SaveException();
        }
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

}