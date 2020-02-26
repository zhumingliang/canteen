<?php
/**
 * Created by PhpStorm.
 * User: 明良
 * Date: 2019/9/10
 * Time: 0:13
 */

namespace app\api\service;


use app\api\model\CanteenAddressT;
use app\api\model\OutConfigT;
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
        $canteen_id = Token::getCurrentTokenVar('current_canteen_id');
        $limit = 2;
        $outConfig = OutConfigT::config($canteen_id);
        if ($outConfig) {
            $limit = $outConfig->address_limit;
        }
        $limitAddress = array();
        if ($limit == CommonEnum::STATE_IS_OK) {
            $limitAddress = CanteenAddressT::address($canteen_id);
        }
        $address = UserAddressT::userAddress($u_id);
        $address = $this->prefixAddressLimit($limitAddress, $address);

        return [
            'limit' => $limit,
            'limit_address' => $limitAddress,
            'user_address' => $address
        ];
    }

    private function prefixAddressLimit($limitArr, $addressArr)
    {
        if (!count($addressArr)) {
            return $addressArr;
        }
        foreach ($addressArr as $k => $v) {
            $addressArr[$k]['use'] = CommonEnum::STATE_IS_FAIL;
            if (!count($limitArr)) {
                continue;
            }
            foreach ($limitArr as $k2 => $v2) {
                $check = $this->checkAddressLimit($v2, $v);
                if ($check == CommonEnum::STATE_IS_OK) {
                    $addressArr[$k]['use'] = CommonEnum::STATE_IS_OK;
                    break;
                }
            }
        }
        return $addressArr;

    }

    private function checkAddressLimit($limit, $address)
    {
        if (!empty($limit['province'])) {
            if ($limit['province'] != $address['province']) {
                return 2;
            }
        } else {
            return 1;
        }

        if (!empty($limit['city'])) {
            if ($limit['city'] != $address['city']) {
                return 2;
            }
        } else {
            return 1;
        }

        if (!empty($limit['area'])) {
            if ($limit['area'] != $address['area']) {
                return 2;
            }
        } else {
            return 1;
        }
        return 1;


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