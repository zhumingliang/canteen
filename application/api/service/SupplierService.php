<?php


namespace app\api\service;


use app\api\model\SupplierT;
use app\api\model\SupplierV;
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
        $params['admin_id'] = Token::getCurrentUid();
        $params['pwd'] = sha1($params['pwd']);
        $supplier = SupplierT::create($params);
        if (!$supplier) {
            throw new SaveException();
        }

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

    public function companySuppliers($page, $size)
    {
        $company_id = Token::getCurrentTokenVar('c_id');
        if (empty($company_id)) {
            throw new AuthException(['msg' => '该用户没有归属企业']);
        }
        $suppliers = SupplierT::companySuppliers($company_id, $page, $size);
        return $suppliers;
    }

}