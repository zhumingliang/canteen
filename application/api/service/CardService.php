<?php


namespace app\api\service;


use app\api\model\CompanyStaffT;
use app\api\model\StaffCardT;
use app\api\model\StaffCardV;
use app\lib\enum\CommonEnum;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;
use app\lib\exception\UpdateException;

class CardService
{
    public function cardManager($name, $cardCode, $status, $page, $size)
    {
        $companyId = Token::getCurrentTokenVar('company_id');
        $staffs = StaffCardV::staffs($companyId, $name, $cardCode, $status, $page, $size);
        return $staffs;

    }

    public function bind($staffId, $cardCode)
    {
        //检测卡在本企业是否已经存在
        $staff = CompanyStaffT::where('id', $staffId)->find();
        if (StaffCardV::checkCardExits($staff->company_id, $cardCode)) {
            throw new ParameterException(['msg' => "卡号已存在，不能重复绑定"]);
        }
        //获取用户是否存在已经绑定的卡
        $card = StaffCardT::where('staff_id', $staffId)->find();
        if ($card) {
            if (in_array($card->state, [1, 2])) {
                throw new ParameterException(['用户已经绑定卡，不能重复绑定']);
            }
            $card->state = CommonEnum::STATE_IS_OK;
            $card->card_code = $cardCode;
            $res = $card->save();
            if (!$res) {
                throw new UpdateException(['msg' => "绑卡失败"]);
            }
        }
        //  //未绑定卡
        $data = [
            'staff_id' => $staffId,
            'card_code' => $cardCode,
            'state' => CommonEnum::STATE_IS_OK
        ];
        $card = StaffCardT::create($data);
        if (!$card) {
            throw new SaveException();
        }
    }

    public function handle($cardId,$state)
    {
        $card = StaffCardT::where('id', $cardId)->find();
        if (!$card) {
            throw new ParameterException(['msg' => '卡号不存在']);
        }
        $card->stae =$state;
        if (!$card->save()) {
            throw new UpdateException(['msg' => '卡状态操作失败']);
        }
    }

}