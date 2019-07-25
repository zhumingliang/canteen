<?php


namespace app\api\service;


use app\api\model\SystemCanteenModuleT;
use app\api\model\SystemModuleT;
use app\api\model\SystemShopModuleT;
use app\lib\enum\CommonEnum;
use app\lib\enum\ModuleEnum;
use app\lib\exception\SaveException;
use app\lib\exception\UpdateException;

class ModuleService
{
    public function saveSystem($params)
    {
        $params['state'] = CommonEnum::STATE_IS_OK;
        $module = SystemModuleT::create($params);
        if (!$module) {
            throw new SaveException();
        }
    }

    public function saveSystemCanteen($params)
    {
        $params['state'] = CommonEnum::STATE_IS_OK;
        $module = SystemCanteenModuleT::create($params);
        if (!$module) {
            throw new SaveException();
        }
    }

    public function saveSystemShop($params)
    {
        $params['state'] = CommonEnum::STATE_IS_OK;
        $module = SystemShopModuleT::create($params);
        if (!$module) {
            throw new SaveException();
        }
    }

    public function handelModule($params)
    {
        $type = $params['type'];
        $res = null;
        if ($type == ModuleEnum::CANTEEN) {
            $res = SystemCanteenModuleT::update($params);
        } elseif ($type == ModuleEnum::SYSTEM) {
            $res = SystemModuleT::update($params);
        } elseif ($type == ModuleEnum::SHOP) {
            $res = SystemShopModuleT::update($params);
        }
        if (!$res) {
            throw new UpdateException();
        }
    }

    public function systemModules($type)
    {
        $modules = array();
        if ($type == ModuleEnum::CANTEEN) {
            $modules = SystemCanteenModuleT::where('state', CommonEnum::STATE_IS_OK)
                ->hidden(['update_time'])
                ->order('create_time desc')
                ->select()
                ->toArray();
        } elseif ($type == ModuleEnum::SYSTEM) {
            $modules = SystemModuleT::where('state', CommonEnum::STATE_IS_OK)
                ->hidden(['update_time'])
                ->order('create_time desc')
                ->select()
                ->toArray();
        } elseif ($type == ModuleEnum::SHOP) {
            $modules = SystemShopModuleT::where('state', CommonEnum::STATE_IS_OK)
                ->hidden(['update_time'])
                ->order('create_time desc')
                ->select()
                ->toArray();
        }
        return $this->getTree($modules);

    }


    public function updateModule($params){

        $type = $params['type'];
        $res = null;
        if ($type == ModuleEnum::CANTEEN) {
            $res = SystemCanteenModuleT::update($params);
        } elseif ($type == ModuleEnum::SYSTEM) {
            $res = SystemModuleT::update($params);
        } elseif ($type == ModuleEnum::SHOP) {
            $res = SystemShopModuleT::update($params);
        }
        if (!$res) {
            throw new UpdateException();
        }
    }


    private function getTree($list, $pid = 0)
    {
        $tree = [];
        if (!empty($list)) {        //先修改为以id为下标的列表
            $newList = [];
            foreach ($list as $k => $v) {
                $newList[$v['id']] = $v;
            }        //然后开始组装成特殊格式
            foreach ($newList as $value) {
                if ($pid == $value['parent_id']) {//先取出顶级
                    $tree[] = &$newList[$value['id']];
                } elseif (isset($newList[$value['parent_id']])) {//再判定非顶级的pid是否存在，如果存在，则再pid所在的数组下面加入一个字段items，来将本身存进去
                    $newList[$value['parent_id']]['items'][] = &$newList[$value['id']];
                }
            }
        }
        return $tree;
    }

}