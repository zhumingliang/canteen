<?php


namespace app\api\service;


use app\api\model\AdminT;
use app\api\model\CompanyStaffT;
use app\api\model\StaffCanteenT;
use app\api\model\StaffQrcodeT;
use app\api\model\UserT;
use app\lib\enum\CommonEnum;
use app\lib\exception\AuthException;
use app\lib\exception\UpdateException;
use think\facade\Request;
use zml\tp_tools\Redis;

class UserService
{
    public function bindPhone($phone, $code)
    {
        $token = Request::header('token');
        $current_code = Redis::instance()->get($token);
        if (!$current_code) {
            throw new UpdateException(['errorCode' => '10007', 'msg' => '验证码过期，请重新获取']);
        }
        if ($current_code != $phone . '-' . $code) {
            throw new UpdateException(['errorCode' => '10002', 'msg' => '验证码不正确']);
        }


        $u_id = Token::getCurrentUid();
        $user = UserT::get($u_id);
        if (!empty($user->phone)) {
            throw new UpdateException(['msg' => '用户已经绑定手机号，不能重复绑定']);
        }
        $user->phone = $phone;
        $res = $user->save();
        if (!$res) {
            throw new UpdateException(['msg' => '绑定用户手机号失败']);
        }
        (new OfficialToken())->updatePhone($phone);
        return (new CanteenService())->userCanteens();
    }


    public function bindCanteen($canteen_id)
    {
        $phone = Token::getCurrentTokenVar('phone');
        $company_id = (new CanteenService())->getCanteenCompanyID($canteen_id);
        $staff = CompanyStaffT::where('phone', $phone)
            ->where('company_id', $company_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->find();
        if (!$staff) {
            throw  new AuthException(['msg' => '用户信息不存在']);
        }

        $userCanteen = StaffCanteenT::where('staff_id', $staff->id)
            ->where('canteen_id', $canteen_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->find();
        if (!$userCanteen) {
            throw  new AuthException(['msg' => '绑定失败，用户不属于该饭堂']);
        }
        $res = UserT::update([
            'current_canteen_id' => $canteen_id,
            'current_company_id' => $staff->company_id
        ],
            ['id' => Token::getCurrentUid()]);
        if (!$res) {
            throw  new UpdateException(['msg' => '绑定失败']);
        }
        //更新用户缓存
        Token::updateCurrentTokenVar('current_canteen_id', $canteen_id);
        Token::updateCurrentTokenVar('current_company_id', $staff->company_id);
    }

    public function getUserCurrentCompanyID()
    {
        $company_id = Token::getCurrentTokenVar('current_company_id');
        if (!empty($company_id)) {
            return $company_id;
        }
        $user = UserT::get(Token::getCurrentUid());
        if (empty($user->current_company_id)) {
            throw new AuthException(['msg' => '该用户无归属企业']);
        }
        Token::updateCurrentTokenVar('current_company_id', $user->current_company_id);
        return $user->current_company_id;
    }

    //检查用户是否为管理员
    public function checkUserAdminID()
    {
        $phone = Token::getCurrentTokenVar('phone');
        if (empty($phone)) {
            throw new AuthException(['msg' => '用户状态异常，未绑定手机号']);
        }
        $admin = AdminT::where('phone', $phone)->where('state', CommonEnum::STATE_IS_OK)
            ->find();
        if ($admin) {
            return $admin->id;
        }
        return 0;

    }

    public function getUserStaffTypeByPhone($phone)
    {
        if (empty($phone)) {
            throw new AuthException(['msg' => '用户状态异常，未绑定手机号']);
        }
        $admin = CompanyStaffT::where('phone', $phone)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->find();
        if (!$admin) {
            throw new AuthException(['msg' => '用户状态异常，不属于任何企业']);
        }
        return $admin->t_id;

    }

    public function getUserCompanyInfo($phone, $company_id)
    {
        if (empty($phone)) {
            throw new AuthException(['msg' => '用户状态异常，未绑定手机号']);
        }
        $admin = CompanyStaffT::where('phone', $phone)
            ->where('company_id', $company_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->find();
        if (!$admin) {
            throw new AuthException(['msg' => '用户状态异常，不属于任何企业']);
        }
        return $admin;

    }

    //获取用户电子饭卡
    public function mealCard()
    {
        $phone = Token::getCurrentTokenVar('phone');
        $staff = CompanyStaffT::staff($phone);
        if (!$staff) {
            throw  new  AuthException(['msg' => '用户信息不存在']);
        }
        if (empty($staff->qrcode)) {
            return (new DepartmentService())->saveQrcode($staff->id);
        }
        $qrcode = $staff->qrcode;
        if (strtotime($qrcode->expiry_date) >= time()) {
            return ['url' => $qrcode->url];
        }

        $newQrode = (new DepartmentService())->updateQrcode($qrcode->toArray());

        return ['url' => $newQrode->url];
    }

}