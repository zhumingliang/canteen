<?php


namespace app\api\service;


use app\api\model\AdminModuleT;
use app\api\model\AdminT;
use app\api\model\CanteenModuleV;
use app\api\model\CompanyOutsiderT;
use app\api\model\OutsiderCanteenT;
use app\api\model\OutsiderModuleT;
use app\lib\enum\CommonEnum;
use app\lib\exception\SaveException;
use think\Db;
use think\Exception;

class OutsiderService
{
    public function updateOutsider($params)
    {
        try {
            Db::startTrans();
            $company_id = $params['company_id'];
            if (empty($params['id'])) {
                $outsider = CompanyOutsiderT::getCompanyOutsiderWithCompanyId($company_id);
                if ($outsider) {
                    throw new SaveException(['msg' =>'不能重复配置企业外来人员信息']);
                }
                $outsider = CompanyOutsiderT::create([
                    'company_id' => $company_id,
                    'rules' => $params['rules']
                ]);
                if (!$outsider) {
                    throw new SaveException();
                }
                $outsiderId = $outsider->id;
            } else {
                $outsiderId = $params['id'];
                CompanyOutsiderT::update($params);
            }

            if (!empty($params['canteens'])) {
                $canteens = json_decode($params['canteens'], true);
                $this->updateOutsiderCS($outsiderId, $canteens);
            }
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw  $e;

        }

    }

    public function updateOutsiderCS($outsider_id, $data)
    {
        if (!empty($data['add'])) {
            $add = $data['add'];
            foreach ($add as $k => $v) {
                $add[$k]['state'] = CommonEnum::STATE_IS_OK;
                $add[$k]['outsider_id'] = $outsider_id;
            }
            $res = (new OutsiderCanteenT())->saveAll($add);

            if (!$res) {
                throw new SaveException();
            }

        }

        if (!empty($data['cancel'])) {
            $ids = explode(',', $data['cancel']);
            $list = [];
            foreach ($ids as $k => $v) {
                array_push($list, [
                    'id' => $v,
                    'state' => CommonEnum::STATE_IS_FAIL
                ]);

            }
            $res = (new OutsiderCanteenT())->saveAll($list);

            if (!$res) {
                throw new SaveException();
            }

        }

        return true;

    }

    public function outsiders($page, $size, $company_id)
    {
        $outsiders = CompanyOutsiderT::outsiders($page, $size, $company_id);
        return $outsiders;

    }

    public function outsider($id)
    {
        $outsider = CompanyOutsiderT::outsider($id);
        //获取企业所有模块
        $modules = CanteenModuleV::modules($outsider['company_id']);
        $outsiderModulesArr = explode(',', $outsider['rules']);
        foreach ($modules as $k => $v) {
            $modules[$k]['have'] = CommonEnum::STATE_IS_FAIL;
            if (in_array($v['c_m_id'], $outsiderModulesArr)) {
                $modules[$k]['have'] = CommonEnum::STATE_IS_OK;
            }

        }
        $role['modules'] = getTree($modules);
        return $role;
    }


}