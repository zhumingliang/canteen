<?php


namespace app\api\service;


use app\api\model\CanteenModuleV;
use app\api\model\FoodMaterialT;
use app\api\model\FoodT;
use app\api\model\FoodV;
use app\lib\enum\AdminEnum;
use app\lib\enum\CommonEnum;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;
use app\lib\exception\UpdateException;
use think\Db;
use think\Exception;
use think\Model;

class FoodService
{
    public function save($params)
    {
        try {
            Db::startTrans();
            $params['state'] = CommonEnum::STATE_IS_FAIL;
            $food = FoodT::create($params);
            if (!$food) {
                throw new SaveException();
            }
            $c_id = $params['c_id'];
            if (!empty($params['material']) && $this->checkCanteenHasMaterialModule($c_id)) {
                $this->prefixMaterial($params['material'], $food->id);
            }

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw $e;

        }

    }


    private function prefixMaterial($material, $f_id)
    {
        $material = json_decode($material, true);
        foreach ($material as $k => $v) {
            if (empty($v['id'])) {
                $material[$k]['f_id'] = $f_id;
                $material[$k]['state'] = CommonEnum::STATE_IS_OK;
            }
        }

        $foodMaterial = (new FoodMaterialT())->saveAll($material);
        if (!$foodMaterial) {
            throw new SaveException(['msg' => '保存菜品材料信息失败']);
        }

    }

    public function update($params)
    {
        try {
            Db::startTrans();
            $food = FoodT::update($params);
            if (!$food) {
                throw new UpdateException();
            }
            if (!empty($params['material'])) {
                $this->prefixMaterial($params['material'], $food->id);
            }

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw $e;

        }
    }

    public function handel($params)
    {
        $food = FoodT::get($params['id']);
        if (!$food) {
            throw  new ParameterException(['msg' => '菜品不存在']);
        }
        $food->state = $params['state'];
        $res = $food->save();
        if (!$res) {
            throw  new UpdateException();
        }

    }

    private function checkCanteenHasMaterialModule($c_id)
    {
        return 1;
        /* $name = "材料管理";
         $count = CanteenModuleV::where('name', $name)
             ->where('canteen_id', $c_id)
             ->count('c_m_id');
         return $count;*/

    }

    public function foods($page, $size, $params)
    {
        $f_type = $params['f_type'];
        $selectField = $this->prefixSelectFoodsFiled($params);
        $foods = FoodV::foods($page, $size, $f_type, $selectField['field'], $selectField['value']);
        return $foods;

    }

    private function prefixSelectFoodsFiled($params)
    {
        if (!empty($params['menu_ids'])) {
            return [
                'field' => 'menu_id',
                'value' => $params['menu_ids']
            ];
        } else if (!empty($params['dinner_ids'])) {
            return [
                'field' => 'dinner_id',
                'value' => $params['dinner_ids']
            ];
        } else if (!empty($params['canteen_ids'])) {
            return [
                'field' => 'canteen_id',
                'value' => $params['canteen_ids']
            ];
        } else if (!empty($params['company_ids'])) {
            return [
                'field' => 'company_id',
                'value' => $params['company_ids']
            ];
        }
        throw  new ParameterException();
    }

    public function food($id)
    {
        $info = FoodV::foodInfo($id);
        return $info;
    }

}