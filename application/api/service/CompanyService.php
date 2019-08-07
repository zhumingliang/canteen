<?php


namespace app\api\service;


use app\api\model\CompanyT;
use app\lib\enum\AdminEnum;
use app\lib\enum\CanteenEnum;
use app\lib\enum\CommonEnum;
use app\lib\exception\SaveException;
use think\Db;
use think\Exception;

class CompanyService
{
    public function saveDefault($params)
    {
        try {
            Db::startTrans();
            $params['state'] = CommonEnum::STATE_IS_OK;
            $params['admin_id'] = Token::getCurrentUid();
            $parent = $this->getParentCompany($params['parent_id']);
            $params['parent_name'] = $parent['name'];
            $params['grade'] = $parent['grade'];
            $company = CompanyT::create($params);
            if (!$company) {
                throw  new SaveException();
            }
            $c_id = $company->id;
            //新增默认饭堂
            (new CanteenService())->saveDefault($c_id, CanteenEnum::DEFAULT_NAME);
            //新增默认小卖部
            // (new ShopService())->save($c_id);
            //新增默认企业超级管理员账号
            $account = $c_id . '-' . AdminEnum::DEFAULT_ACCOUNT;
            (new AdminService())->save($account, AdminEnum::DEFAULT_PASSWD, '企业系统管理员', AdminEnum::COMPANY_SUPER, $c_id);
            Db::commit();
            return [
                'company_id' => $c_id,
            ];
        } catch (Exception $e) {
            Db::rollback();
            throw $e;

        }

    }

    private function getParentCompany($c_id)
    {
        if (!$c_id) {
            $company = CompanyT::get($c_id);
            return [
                'name' => $company->name,
                'grade' => $company->grade + 1
            ];

        }
        return [
            'name' => '',
            'grade' => 1
        ];;
    }

    public function companies($page, $size, $name, $create_time)
    {
        $companies = CompanyT::companies($page, $size, $name, $create_time);
        return $companies;

    }


    public function managerCompanies($name)
    {
        $companies = CompanyT::managerCompanies( $name);
        $companies=getTree($companies);
        return $companies;
    }

}