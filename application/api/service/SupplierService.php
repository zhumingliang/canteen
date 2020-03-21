<?php


namespace app\api\service;


use app\api\model\SupplierT;
use app\api\model\SupplierV;
use app\lib\enum\AdminEnum;
use app\lib\enum\CommonEnum;
use app\lib\exception\AuthException;
use app\lib\exception\DeleteException;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;
use app\lib\exception\UpdateException;
use think\Request;

class SupplierService
{
    public function save($params)
    {
        $account = $this->checkExit($params['c_id'], $params['name']);
        $params['admin_id'] = Token::getCurrentUid();
        $params['pwd'] = sha1($params['pwd']);
        $params['account'] = $account;
        $supplier = SupplierT::create($params);
        if (!$supplier) {
            throw new SaveException();
        }

    }

    private function checkExit($company_id, $name)
    {
        $checkName = SupplierT::where('name', $name)
            ->where('c_id', $company_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->count('id');
        if ($checkName) {
            throw new SaveException(['msg' => '供应商名称已存在']);
        }
        return $this->checkAccount($company_id);

    }

    private function checkAccount($company_id)
    {
        $account = $company_id . 'S' . rand(1000, 9999);

        $check = SupplierT::where('account', $account)->where('state', CommonEnum::STATE_IS_OK)
            ->count('id');
        if (!$check) {
            return $account;
        }
        return $this->checkAccount($company_id);
    }

    public function update($params)
    {
        if (!empty($params['pwd'])) {
            $params['pwd'] = sha1($params['pwd']);
        }
        $supplier = SupplierT::update($params);
        if (!$supplier) {
            throw new UpdateException();
        }

    }

    public function delete($id)
    {
        $supplier = SupplierT::update(['state' => CommonEnum::STATE_IS_FAIL], ['id' => $id]);
        if (!$supplier) {
            throw new DeleteException();
        }
    }

    public function suppliers($page, $size, $c_id)
    {
        $suppliers = SupplierV::suppliers($c_id, $page, $size);
        return $suppliers;
    }

    public function companySuppliers()
    {
        $company_id = Token::getCurrentTokenVar('company_id');
        $grade = Token::getCurrentTokenVar('grade');
        if ($grade != AdminEnum::SYSTEM_SUPER && empty($company_id)) {
            throw new AuthException(['msg' => '该用户没有归属企业']);
        }
        $suppliers = SupplierT::companySuppliers($company_id);
        return $suppliers;
    }

}