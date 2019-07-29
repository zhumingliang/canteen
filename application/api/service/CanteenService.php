<?php


namespace app\api\service;


use app\api\model\CanteenModuleT;
use app\api\model\CanteenT;
use app\api\model\SystemCanteenModuleT;
use app\lib\enum\CommonEnum;
use app\lib\enum\ModuleEnum;
use app\lib\exception\SaveException;
use think\Db;
use think\Exception;

class CanteenService
{

    public function save($params)
    {
        try {
            Db::startTrans();
            $canteens = $params['canteens'];
            $canteens = json_decode($canteens, true);
            $c_id = $params['c_id'];
            foreach ($canteens as $K => $v) {
                $id = $this->saveDefault($c_id, $v);
                if (!$id) {
                    Db::rollback();
                    throw new SaveException();
                    break;
                }
            }
            Db::commit();
        } catch (Exception$e) {
            Db::rollback();
            throw  $e;
        }

    }

    public function saveDefault($company_id, $name)
    {
        $data = [
            'c_id' => $company_id,
            'name' => $name,
            'state' => CommonEnum::STATE_IS_OK
        ];
        $canteen = CanteenT::create($data);
        if (!$canteen) {
            throw new SaveException();
        }
        //新增饭堂默认功能模块
        $this->saveDefaultCanteen($canteen->id);
        return $canteen->id;

    }

    private function saveDefaultCanteen($c_id)
    {
        $modules = SystemCanteenModuleT::defaultModules();
        $data = array();
        if (count($modules)) {
            $pc_order = $mobile_order = 1;
            foreach ($modules as $k => $v) {
                if ($v->type == ModuleEnum::MOBILE) {
                    $order = $mobile_order;
                    $mobile_order++;
                } else {
                    $order = $pc_order;
                    $pc_order++;
                }

                $data[] = [
                    'c_id' => $c_id,
                    'state' => CommonEnum::STATE_IS_OK,
                    'm_id' => $v->id,
                    'type' => $v->type,
                    'order' => $order
                ];


            }
            if (!count($data)) {
                return true;
            }
            $res = (new CanteenModuleT())->saveAll($data);
            if (!$res) {
                throw new SaveException();
            }

        }


    }

}