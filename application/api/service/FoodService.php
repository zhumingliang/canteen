<?php


namespace app\api\service;


use app\api\model\AutomaticFoodT;
use app\api\model\AutomaticT;
use app\api\model\CanteenModuleV;
use app\api\model\FoodCommentT;
use app\api\model\FoodDayStateT;
use app\api\model\FoodDayStateV;
use app\api\model\FoodMaterialT;
use app\api\model\FoodT;
use app\api\model\FoodV;
use app\lib\enum\CommonEnum;
use app\lib\enum\FoodEnum;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;
use app\lib\exception\UpdateException;
use think\Db;
use think\Exception;
use think\Model;

class FoodService extends BaseService
{
    public function save($params)
    {
        try {
            Db::startTrans();
            $params['state'] = CommonEnum::STATE_IS_OK;
            $food = FoodT::create($params);
            if (!$food) {
                throw new SaveException();
            }
            $company_id = Token::getCurrentTokenVar('company_id');
            if (!empty($params['material']) && $this->checkCanteenHasMaterialModule($company_id)) {
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
        //检测菜品是否上架
        if ($this->checkFoodUp($params['id'])) {
            throw new ParameterException(['msg' => '菜品上架中，不能删除']);
        }
        $food->state = CommonEnum::STATE_IS_FAIL;
        $res = $food->save();
        if (!$res) {
            throw  new UpdateException();
        }

    }

    private function checkFoodUp($food_id)
    {
        $count = FoodDayStateT::where('f_id', $food_id)
            ->where('day', '>=', date('Y-m-d'))
            ->where('status', CommonEnum::STATE_IS_OK)
            ->count('id');
        return $count;

    }

    private function checkCanteenHasMaterialModule($company_id)
    {

        $name = "材料管理";
        $count = CanteenModuleV::where('name', $name)
            ->where('company_id', $company_id)
            ->count('c_m_id');
        return $count;

    }

    public function foods($page, $size, $params)
    {
        $f_type = $params['f_type'];
        $selectField = $this->prefixSelectFiled($params);
        $foods = FoodV::foods($page, $size, $f_type, $selectField['field'], $selectField['value']);
        return $foods;

    }

    public function food($id)
    {
        $info = FoodV::foodInfo($id);
        return $info;
    }

    public function foodMaterials($page, $size, $params)
    {
        $selectField = $this->prefixSelectFiled($params);
        $foods = FoodV::foodMaterials($page, $size, $selectField['field'], $selectField['value']);

        return $foods;
    }

    public function exportFoodMaterials($params)
    {
        $selectField = $this->prefixSelectFiled($params);
        $foods = FoodV::exportFoodMaterials($selectField['field'], $selectField['value']);
        $foods = $this->prefixFoodMaterials($foods);
        $header = ['企业', '饭堂', '餐次', '菜品', '材料名称', '数量', '单位'];
        $file_name = "菜品材料明细导出报表";
        $url = (new ExcelService())->makeExcelMerge($header, $foods, $file_name, 4);
        return [
            'url' => config('setting.domain') . $url
        ];
    }

    private function prefixFoodMaterials($foods)
    {
        $dataList = [];
        if (!count($foods)) {
            return $foods;
        }
        $i = 2;
        foreach ($foods as $k => $v) {
            $material = $v['material'];
            if (empty($material)) {
                array_push($dataList, [
                    'company' => $v['company'],
                    'canteen' => $v['canteen'],
                    'dinner' => $v['dinner'],
                    'food' => $v['name'],
                    'name' => '',
                    'count' => '',
                    'unit' => '',
                    'merge' => CommonEnum::STATE_IS_FAIL,
                    'start' => 0,
                    'end' => 0,
                ]);
                $i++;
                continue;
            }

            foreach ($material as $k2 => $v2) {
                array_push($dataList, [
                    'company' => $v['company'],
                    'canteen' => $v['canteen'],
                    'dinner' => $v['dinner'],
                    'food' => $v['name'],
                    'name' => $v2['name'],
                    'count' => $v2['count'],
                    'unit' => $v2['unit'],
                    'merge' => CommonEnum::STATE_IS_OK,
                    'start' => $k2 == 0 ? $i : $i - 1,
                    'end' => $i
                ]);
                $i++;
            }

        }

        return $dataList;

    }

    public function updateMaterial($params)
    {
        $id = $params['id'];
        $material = $params['material'];
        $this->prefixMaterial($material, $id);

    }

    public function foodsForOfficialManager($menu_id, $food_type, $day, $canteen_id, $page, $size)
    {
        $foods = FoodT::foodsForOfficialManager($menu_id, $food_type, $page, $size);
        $foods['data'] = $this->prefixFoodDayStatus($foods['data'], $day, $canteen_id);
        return $foods;
    }

    private function prefixFoodDayStatus($foods, $day, $canteen_id)
    {
        if (!count($foods)) {
            return $foods;
        }
        //获取指定时间菜品状态
        $foodDay = FoodDayStateT::FoodStatus($canteen_id, $day);
        foreach ($foods as $k => $v) {
            $status = 2;
            $default = 2;
            if (!empty($foodDay)) {
                foreach ($foodDay as $k2 => $v2) {
                    if ($v['id'] == $v2['f_id']) {
                        $status = $v2['status'];
                        $default = $v2['default'];
                    }
                }
            }
            $foods[$k]['status'] = $status;
            $foods[$k]['default'] = $default;
        }
        return $foods;
    }

    public function handelFoodsDayStatus($params)
    {
        $day = $params['day'];
        $food_id = $params['food_id'];
        $canteen_id = $params['canteen_id'];
        if (!empty($params['default'])) {
            if (!$this->checkStatus($food_id, $day, $params['default'])) {
                throw new SaveException(['msg' => '默认菜式数量已达到最大值']);
            }
        }
        $dayFood = FoodDayStateT::where('f_id', $food_id)
            ->where('day', $day)
            ->find();

        if (!$dayFood) {
            $data = [
                'f_id' => $food_id,
                'canteen_id' => $canteen_id,
                'day' => $day,
                'user_id' => Token::getCurrentUid()
            ];
            if (!empty($params['status'])) {
                $data['status'] = $params['status'];
            }
            if (!empty($params['default'])) {
                $data['default'] = $params['default'];
                if ($params['default'] == CommonEnum::STATE_IS_OK) {
                    $data['status'] = CommonEnum::STATE_IS_OK;
                }
            }
            if (!FoodDayStateT::create($data)) {
                throw new SaveException(['msg' => '新增菜品信息状态失败']);
            }
            return true;
        }
        if (!empty($params['status'])) {
            $dayFood->status = $params['status'];
        }
        if (!empty($params['default'])) {
            $dayFood->default = $params['default'];
            if ($params['default'] == CommonEnum::STATE_IS_OK) {
                $dayFood->status = CommonEnum::STATE_IS_OK;
            }
        }
        $dayFood->update_time = date('Y-m-d H:i:s');
        if (!$dayFood->save()) {
            throw new UpdateException (['msg' => '修改菜品信息状态失败']);

        }
    }

    private function checkStatus($food_id, $day, $status)
    {
        if ($status == CommonEnum::STATE_IS_FAIL) {
            return true;
        }
        $food = FoodT::where('id', $food_id)->with('menu')->find();
        $menu_status = $food->menu->status;
        $menu_count = $food->menu->count;
        if ($menu_status == 2) {
            //动态
            return true;
        }
        //获取该餐类下设置数量
        $count = FoodDayStateV::where('day', $day)
            ->where('m_id', $food->menu->id)
            ->where('status', CommonEnum::STATE_IS_OK)
            ->count('id');
        if ($count < $menu_count) {
            return true;
        }
        return false;

    }

    public function foodsForOfficialPersonChoice($d_id)
    {
        $foods = FoodDayStateV::foodsForOfficialPersonChoice($d_id);
        $menus = (new MenuService())->dinnerMenus($d_id);
        $foods = $this->prefixPersonChoiceFoods($foods, $menus);
        return $foods;
    }

    public function foodsForOfficialMenu($d_id)
    {
        $foods = FoodDayStateV::foodsForOfficialMenu($d_id);
        $menus = (new MenuService())->dinnerMenus($d_id);
        $foods = $this->prefixPersonChoiceFoods($foods, $menus);
        return $foods;
    }

    private function prefixPersonChoiceFoods($foods, $menus)
    {
        if (!count($foods)) {
            return $foods;
        }
        foreach ($menus as $k => $v) {
            $data = [];
            foreach ($foods as $k2 => $v2) {
                if ($v['id'] == $v2['m_id']) {
                    array_push($data, $foods[$k2]);
                    unset($foods[$k2]);
                }
            }
            $menus[$k]['foods'] = $data;

        }
        return $menus;
    }

    public function saveComment($params)
    {
        $params['u_id'] = Token::getCurrentUid();
        $params['f_id'] = $params['food_id'];
        $comment = FoodCommentT::create($params);
        if (!$comment) {
            throw  new SaveException();
        }
    }

    public function infoToComment($food_id)
    {
        $food = FoodT::infoForComment($food_id);
        $canteen_id = Token::getCurrentTokenVar('current_canteen_id');
        return [
            'food' => $food,
            'canteenScore' => (new CanteenService())->canteenScore($canteen_id)
        ];
    }

    public function saveAutoConfig($params)
    {
        try {
            Db::startTrans();
            $autoData = [];
            $autoData['canteen_id'] = $params['canteen_id'];
            $autoData['dinner_id'] = $params['dinner_id'];
            $autoData['auto_week'] = $params['auto_week'];
            $autoData['repeat_week'] = $params['repeat_week'];
            $autoData['state'] = CommonEnum::STATE_IS_OK;
            if (AutomaticT::checkExits($autoData['dinner_id'], $autoData['repeat_week'])) {
                throw new ParameterException(['msg' => "该餐次指定重复周期已经设置"]);
            }

            $auto = AutomaticT::create($autoData);
            if (!$auto) {
                throw new SaveException(['msg' => '保存自动上架配置失败']);
            }
            $detail = \GuzzleHttp\json_decode($params['detail'], true);
            if (empty($detail) || empty($detail['add'])) {
                throw new SaveException(['msg' => '上架菜品不能为空']);
            }
            $this->prefixAutoFoods($auto->id, $detail['add'], []);
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }


    }

    public function updateAutoConfig($params)
    {
        try {
            Db::startTrans();
            if (!empty($params['dinner_id'] || !empty($params['repeat_week']))) {
                $check = AutomaticT::checkExits($params['dinner_id'], $params['repeat_week']);
                if ($check->id != $params['id']) {
                    throw new ParameterException(['msg' => "该餐次指定重复周期已经设置"]);
                }

            }
            $auto = AutomaticT::update($params);
            if (!$auto) {
                throw new SaveException(['msg' => '修改自动上架配置失败']);
            }
            if (!empty($params['detail'])) {
                $detail = \GuzzleHttp\json_decode($params['detail'], true);
                $add = empty($detail['add']) ? [] : $detail['add'];
                $cancel = empty($detail['cancel']) ? [] : $detail['cancel'];
                $this->prefixAutoFoods($params['id'], $add, $cancel);
            }
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }


    }

    private function prefixAutoFoods($autoId, $add, $cancel)
    {
        $data = [];
        if (count($add)) {
            foreach ($add as $k => $v) {
                $menuId = $v['menu_id'];
                $foods = $v['foods'];
                if (count($foods)) {
                    foreach ($foods as $k2 => $v2) {
                        array_push($data, [
                            'auto_id' => $autoId,
                            'state', CommonEnum::STATE_IS_OK,
                            'food_id' => $v2,
                            'menu_id' => $menuId
                        ]);

                    }
                }
            }
        }
        if (count($cancel)) {
            foreach ($cancel as $k => $v) {
                array_push($data, [
                    'id' => $v,
                    'state' => CommonEnum::STATE_IS_FAIL
                ]);
            }
        }
        if (count($data)) {
            $save = (new AutomaticFoodT())->saveAll($data);
            if (!$save) {
                throw new SaveException(['msg' => "自动上架菜品明细保存失败"]);
            }
        }
    }

    public function automatic($id)
    {
        $auto = AutomaticT::info($id);
        return $auto;
    }

}