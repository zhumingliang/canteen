<?php
/**
 * Created by PhpStorm.
 * User: 明良
 * Date: 2019/9/10
 * Time: 0:13
 */

namespace app\api\service;


use app\api\model\UserAddressT;
use app\lib\enum\CommonEnum;
use app\lib\exception\SaveException;
use app\lib\exception\UpdateException;

class AddressService
{
    public function save($params)
    {
        $params['u_id'] = Token::getCurrentUid();
        $params['state'] = CommonEnum::STATE_IS_OK;
        $params['default'] = $this->checkDefault($params['u_id']);
        $address = UserAddressT::create($params);
        if (!$address) {
            throw new SaveException();
        }
    }

    private function checkDefault($u_id)
    {
        $userAddressCount = UserAddressT::where('u_id', $u_id)
            ->where('default', CommonEnum::STATE_IS_OK)->count();
        if ($userAddressCount) {
            return 2;
        }
        return 1;

    }

    public function update($params)
    {
        $address = UserAddressT::update($params);
        if (!$address) {
            throw new UpdateException();
        }
    }

    public function userAddresses()
    {
        $u_id = Token::getCurrentUid();
        $company_id = Token::getCurrentTokenVar('current_company_id');
        $address = UserAddressT::where('u_id', $u_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->hidden(['create_time', 'update_time', 'state'])
            ->select();
        return $address;
    }

    public function prefixAddressDefault($id)
    {
        $address = UserAddressT::get($id);
        if ($address->default == CommonEnum::STATE_IS_OK) {
            return true;
        }
        $u_id = Token::getCurrentUid();
        UserAddressT::update(['default' => CommonEnum::STATE_IS_FAIL],
            ['u_id' => $u_id, 'default' => CommonEnum::STATE_IS_OK]);
        $address->default = CommonEnum::STATE_IS_OK;
        $address->save();
    }

}