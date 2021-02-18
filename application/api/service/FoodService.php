<?php


namespace app\api\service;


use app\api\model\AutomaticFoodT;
use app\api\model\AutomaticT;
use app\api\model\CanteenModuleV;
use app\api\model\DinnerT;
use app\api\model\FoodCommentT;
use app\api\model\FoodDayStateT;
use app\api\model\FoodDayStateV;
use app\api\model\FoodMaterialT;
use app\api\model\FoodT;
use app\api\model\FoodV;
use app\api\model\MenuT;
use app\lib\enum\CommonEnum;
use app\lib\enum\FoodEnum;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;
use app\lib\exception\UpdateException;
use EasyWeChat\Factory;
use Monolog\Handler\IFTTTHandler;
use think\Db;
use think\Exception;
use think\Model;
use function GuzzleHttp\Psr7\str;

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

    public function foodsForOfficialManager($canteenId, $dinnerId, $day, $foodType)
    {
        //获取菜单配置
        $menus = MenuT::dinnerMenusCategory($dinnerId);
        //获取所有菜品信息
        $foods = FoodT::foodsForOfficialManager($canteenId, $foodType);
        //获取自动上架配置
        $dayWeek = date('w', strtotime($day));
        $auto = AutomaticT::infoToDinner($canteenId, $dinnerId, $dayWeek);
        //获取选定日期已上架的菜品
        $foodDay = FoodDayStateT::FoodStatus($canteenId, $dinnerId, $day);
        //获取餐次是否固定消费
        $dinner = DinnerT::get($dinnerId);

        //$nextAuto = $this->getNextAuto($auto);
        $nextAuto = $this->getCurrentAutoDay($day, $foodDay, $auto);
        $data = $this->prefixFoodDayStatus($menus, $foods, $auto, $foodDay, $day);
        return [
            'fixed' => $dinner->fixed,
            'auto' => empty($auto) ? 0 : 1,
            'nextAuto' => $nextAuto,
            'foodData' => $data
        ];
    }

    private function getNextAuto($auto)
    {
        if (!$auto) {
            return 0;
        }
        $autoWeek = $auto['auto_week'];
        // $repeatWeek = $auto[0]['repeat_week'];
        $w = date('w');
        if ($w == $autoWeek) {
            return date('Y-m-d', strtotime('+7 day', time())) . ' 00:00';
        } else {
            return date('Y-m-d', strtotime('+' . (7 - abs($w - $autoWeek)) . ' day', time())) . ' 00:00';
        }
    }

    private function getCurrentAutoDay($day, $foodDay, $auto)
    {
        if (!$auto) {
            return 0;
        }
        //获取选择日期的周几信息
        $repeatWeek = date('w', strtotime($day));
        $autoWeek = $auto->auto_week;
        $repeatWeek = $repeatWeek == 0 ? 7 : $repeatWeek;
        $autoWeek = $autoWeek == 0 ? 7 : $autoWeek;
        if ($repeatWeek >= $autoWeek) {
            return reduceDay(7 + ($repeatWeek - $autoWeek), $day);
        } else {
            return reduceDay(7 - ($autoWeek - $repeatWeek), $day);
        }
    }

    private function prefixFoodDayStatus($menus, $foods, $auto, $foodDay, $day)
    {
        foreach ($menus as $k => $v) {
            $menuFood = [];
            if (count($foods)) {
                foreach ($foods as $k2 => $v2) {
                    if ($v['id'] == $v2['m_id']) {
                        $check = $this->checkFoodStatus2($v2['id'], $auto, $foodDay, $day);
                        array_push($menuFood, [
                            'food_id' => $v2['id'],
                            'default' => $check['default'],
                            'name' => $v2['name'],
                            'price' => $v2['price'],
                            'des' => $v2['des'],
                            'external_price' => $v2['external_price'],
                            'img_url' => $v2['img_url'],
                            'status' => $check['status']
                        ]);
                        unset($foods[$k2]);
                        continue;
                    }

                }

            }
            $menus[$k]['foods'] = $menuFood;
        }
        return $menus;
    }

    private function checkFoodStatus($foodId, $auto, $foodDay, $day)
    {
        //状态有三种：上架1/待上架2/未上架3
        //设置了自动上架菜品：待上架/未上架/
        //未设置自动上架菜品：已上架/未上架
        $default = CommonEnum::STATE_IS_FAIL;

        //未设置自动上架
        if (!$auto) {
            $status = FoodEnum::STATUS_DOWN;
            if (count($foodDay)) {
                foreach ($foodDay as $k => $v) {
                    if ($foodId == $v['f_id']) {
                        $status = $v['status'];
                        $default = $v['default'];
                        break;
                    }
                }
            }
            return [
                'default' => $default,
                'status' => $status
            ];
        }

        $foods = $auto['foods'];
        $status = FoodEnum::STATUS_DOWN;
        if (count($foods)) {
            foreach ($foods as $k => $v) {
                if ($foodId == $v['food_id']) {
                    if ($day == date('Y-m-d') && date('Y-m-d', strtotime($v['create_time'])) != date('Y-m-d')) {
                        $status = FoodEnum::STATUS_UP;
                    } else {
                        $status = FoodEnum::STATUS_READY;
                    }
                    break;
                }

            }
        }

        if (count($foodDay)) {
            foreach ($foodDay as $k => $v) {
                if ($foodId == $v['f_id']) {
                    $default = $v['default'];
                    $status = $v['status'];
                    /* if ($day == date('Y-m-d')) {
                         $status = $v['status'];
                     } else {
                         if ($v['status'] != FoodEnum::STATUS_DOWN) {
                             $status = FoodEnum::STATUS_READY;
                         } else {
                             $status = FoodEnum::STATUS_DOWN;
                         }
                     }*/
                    break;
                }
            }
        }
        return [
            'default' => $default,
            'status' => $status
        ];


    }

    private function checkFoodStatus2($foodId, $auto, $foodDay, $day)
    {
        $default = CommonEnum::STATE_IS_FAIL;
        $status = FoodEnum::STATUS_DOWN;
        //未配置自动上架
        if (!$auto) {
            $status = FoodEnum::STATUS_DOWN;
            if (count($foodDay)) {
                foreach ($foodDay as $k => $v) {
                    if ($foodId == $v['f_id']) {
                        $status = $v['status'];
                        $default = $v['default'];
                        break;
                    }
                }
            }
            return [
                'default' => $default,
                'status' => $status
            ];
        }
        //设置自动上架
        //判断是否到了上架时间
        $autoWeek = $auto['auto_week'];
        $repeatWeek = $auto['repeat_week'];
        $checkAlready = $this->checkUpTime($autoWeek, $repeatWeek, $day);
        $autoFoods = $auto['foods'];
        if (!$checkAlready) {
            //未到上架时间
            //1.点击了上架-上架状态
            //2.点击了待上架-待上架状态
            //3非配置菜品-未上架
            $needReturn = false;
            foreach ($foodDay as $k => $v) {
                if ($foodId == $v['f_id']) {
                    $default = $v['default'];
                    $status = $v['status'];
                    /*  if ($status != FoodEnum::STATUS_UP) {
                          $status = FoodEnum::STATUS_READY;
                      }*/
                    $needReturn = true;

                }
            }
            if ($needReturn) {
                return [
                    'default' => $default,
                    'status' => $status
                ];
            }
            foreach ($autoFoods as $k => $v) {
                if ($foodId == $v['food_id']) {
                    $status = FoodEnum::STATUS_READY;
                    break;
                }
            }
            return [
                'default' => $default,
                'status' => $status
            ];

        }

        //已过上架时间
        $needReturn = false;
        foreach ($foodDay as $k => $v) {
            if ($foodId == $v['f_id']) {
                $default = $v['default'];
                $status = $v['status'] == FoodEnum::STATUS_DOWN ? FoodEnum::STATUS_DOWN : $v['status'];
                $needReturn = true;
            }
        }
        if ($needReturn) {
            return [
                'default' => $default,
                'status' => $status
            ];
        }

        foreach ($autoFoods as $k => $v) {
            if ($foodId == $v['food_id']) {
                $status = FoodEnum::STATUS_UP;
                break;
            }
        }
        return [
            'default' => $default,
            'status' => $status
        ];
    }

    private
    function checkUpTime($autoWeek, $repeatWeek, $day = '')
    {

        if (!strlen($day)) {
            $day = date('Y-m-d');
        }
        $repeatWeek = $repeatWeek == 0 ? 7 : $repeatWeek;
        $autoWeek = $autoWeek == 0 ? 7 : $autoWeek;

        if ($repeatWeek >= $autoWeek) {
            $upTime = reduceDay(7 + ($repeatWeek - $autoWeek), $day);
        } else {
            $upTime = reduceDay(7 - ($autoWeek - $repeatWeek), $day);
        }
        return strtotime(date('Y-m-d')) >= strtotime($upTime);


    }

    public
    function handelFoodsDayStatus($params)
    {
        $day = $params['day'];
        $foodId = $params['food_id'];
        $canteenId = $params['canteen_id'];
        $dinnerId = $params['dinner_id'];
        if (!empty($params['default'])) {
            if (!$this->checkStatus($foodId, $day, $params['default'])) {
                throw new SaveException(['msg' => '默认菜式数量已达到最大值']);
            }
        }
        //获取自动上架配置
        $dayWeek = date('w', strtotime($day));
        $auto = AutomaticT::infoToDinner($canteenId, $dinnerId, $dayWeek);

        $dayFood = FoodDayStateT::where('f_id', $foodId)
            ->where('dinner_id', $dinnerId)
            ->where('day', $day)
            ->find();

        if (!$dayFood) {
            FoodDayStateT::destroy(function ($query) use ($foodId, $dinnerId, $day) {
                $query->where('f_id', $foodId)
                    ->where('dinner_id', $dinnerId)
                    ->where('day', $day);
            });
            $data = [
                'f_id' => $foodId,
                'canteen_id' => $canteenId,
                'dinner_id' => $dinnerId,
                'day' => $day,
                'default' => empty($params['default']) ? CommonEnum::STATE_IS_OK : $params['default'],
                'user_id' => Token::getCurrentUid(),

            ];
            $data['status'] = $params['status'];
            if ($auto) {
                $autoWeek = $auto['auto_week'];
                $repeatWeek = $auto['repeat_week'];

                if (!$this->checkUpTime($autoWeek, $repeatWeek, $day)) {
                    //未到上架时间
                    $data['status'] = $params['status'] == FoodEnum::STATUS_DOWN ? FoodEnum::STATUS_DOWN : FoodEnum::STATUS_READY;
                }
            }
            if (!FoodDayStateT::create($data)) {
                throw new SaveException(['msg' => '新增菜品信息状态失败']);
            }
        } else {
            $dayFood->status = $params['status'];
            if ($auto) {
                $autoWeek = $auto['auto_week'];
                $repeatWeek = $auto['repeat_week'];
                if (!$this->checkUpTime($autoWeek, $repeatWeek, $day)) {
                    //未到上架时间
                    $dayFood->status = $params['status'] == FoodEnum::STATUS_DOWN ? FoodEnum::STATUS_DOWN : FoodEnum::STATUS_READY;
                }
            }
            if (!empty($params['default'])) {
                $dayFood->default = $params['default'];
            }
            $dayFood->update_timee = date('Y-m-d H:i:s');
            if (!$dayFood->save()) {
                throw new UpdateException (['msg' => '修改菜品信息状态失败']);

            }
        }


    }

    private
    function checkHandelStatus($auto, $day, $status)
    {

        if (!count($auto)) {
            return $status;
        } else {
            if ($day == date('Y-m-d')) {
                return $status;
            } else {
                //检测当前时间

                return $status == FoodEnum::STATUS_DOWN ?
                    $status : FoodEnum::STATUS_READY;
            }

        }
    }

    private
    function checkStatus($food_id, $day, $status)
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

    public
    function foodsForOfficialPersonChoice($d_id)
    {
        $foods = FoodDayStateV::foodsForOfficialPersonChoice($d_id);
        $menus = (new MenuService())->dinnerMenus($d_id);
        $foods = $this->prefixPersonChoiceFoods($foods, $menus);
        return $foods;
    }

    public
    function foodsForOfficialMenu($day, $d_id)
    {
        $foods = FoodDayStateV::foodsForOfficialMenu($day, $d_id);
        $menus = (new MenuService())->dinnerMenus($d_id);
        $foods = $this->prefixPersonChoiceFoods($foods, $menus);
        return $foods;
    }

    private
    function prefixPersonChoiceFoods($foods, $menus)
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

    public
    function saveComment($params)
    {
        $params['u_id'] = Token::getCurrentUid();
        $params['f_id'] = $params['food_id'];
        $comment = FoodCommentT::create($params);
        if (!$comment) {
            throw  new SaveException();
        }
    }

    public
    function infoToComment($food_id)
    {
        $food = FoodT::infoForComment($food_id);
        $canteen_id = Token::getCurrentTokenVar('current_canteen_id');
        return [
            'food' => $food,
            'canteenScore' => (new CanteenService())->canteenScore($canteen_id)
        ];
    }

    public
    function saveAutoConfig($params)
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

            //判断最近一次上架时间是否已经到了
            $week = date('w');
            $week = $week == 0 ? 7 : $week;
            $repeatWeek = $params['repeat_week'] == 0 ? 7 : $params['repeat_week'];
            if ($week <= $repeatWeek) {
                $add = $detail['add'];
                $foodList = [];
                foreach ($add as $k => $v) {
                    $foods = $v['foods'];
                    if (count($foods)) {
                        foreach ($foods as $k2 => $v2) {
                            array_push($foodList, [
                                'f_id' => $v2,
                                'status' => FoodEnum::STATUS_UP,
                                'day' => addDay($repeatWeek - $week, date('Y-m-d')),
                                'user_id' => 0,
                                'canteen_id' => $params['canteen_id'],
                                'default' => CommonEnum::STATE_IS_FAIL,
                                'dinner_id' => $params['dinner_id']
                            ]);
                        }
                    }
                }

                if (count($foodList)) {
                    $save = (new FoodDayStateT())->saveAll($foodList);
                    if (!$save) {
                        throw new SaveException(['msg' => "上架今日菜品失败"]);
                    }
                }
            }

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }


    }

    public
    function updateAutoConfig($params)
    {
        try {
            Db::startTrans();
            if (!empty($params['dinner_id'] || !empty($params['repeat_week']))) {
                $check = AutomaticT::checkExits($params['dinner_id'], $params['repeat_week']);
                if ($check->id > 0 && $check->id != $params['id']) {
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

    private
    function prefixAutoFoods($autoId, $add, $cancel)
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

    public
    function automatic($id)
    {
        $auto = AutomaticT::info($id);
        return $auto;
    }

    public
    function downAll($canteenId, $dinnerId, $day)
    {
        //检查是否配置自动上架
        $autoFoods = [];
        $foodList = [];
        $alreadyFoods = [];
        $dayWeek = date('w', strtotime($day));
        $auto = AutomaticT::infoToDinner($canteenId, $dinnerId, $dayWeek);
        if ($auto && count($auto['foods'])) {
            $autoFoods = $auto['foods'];
        }
        $status = FoodEnum::STATUS_DOWN;
        if (count($autoFoods)) {
            $autoWeek = $auto['auto_week'];
            $repeatWeek = $auto['repeat_week'];
            if (!$this->checkUpTime($autoWeek, $repeatWeek, $day)) {
                //未到上架时间-处理为待上架
                $status = FoodEnum::STATUS_READY;
            }
        }

        //清除所有信息
        FoodDayStateT::destroy(function ($query) use ($canteenId, $dinnerId, $day) {
            $query->where('canteen_id', $canteenId)
                ->where('dinner_id', $dinnerId)
                ->where('day', '=', $day);
        });
        if (count($autoFoods)) {
            foreach ($autoFoods as $k => $v) {
                if (in_array($v['food_id'], $alreadyFoods)) {
                    continue;
                }
                array_push($foodList, [
                    'f_id' => $v['food_id'],
                    'status' => $status,
                    'day' => $day,
                    'user_id' => 0,
                    'canteen_id' => $canteenId,
                    'default' => CommonEnum::STATE_IS_FAIL,
                    'dinner_id' => $dinnerId
                ]);
                array_push($alreadyFoods, $v['food_id']);

            }
        }


        if (count($foodList)) {
            $save = (new FoodDayStateT())->saveAll($foodList);
            if (!$save) {
                throw new SaveException(['msg' => '批量下架失败']);
            }
        }


    }

    public
    function upAll($canteenId, $dinnerId, $day)
    {
        //获取自动上架配置
        $autoFoods = [];
        $dayWeek = date('w', strtotime($day));
        $auto = AutomaticT::infoToDinner($canteenId, $dinnerId, $dayWeek);
        if ($auto && count($auto['foods'])) {
            $autoFoods = $auto['foods'];
        }
        $foodDay = FoodDayStateT::FoodStatus($canteenId, $dinnerId, $day);
        $foodList = [];
        $alreadyFoods = [];
        $status = FoodEnum::STATUS_UP;
        if (count($autoFoods)) {
            $autoWeek = $auto['auto_week'];
            $repeatWeek = $auto['repeat_week'];
            if (!$this->checkUpTime($autoWeek, $repeatWeek, $day)) {
                //未到上架时间-处理为待上架
                $status = FoodEnum::STATUS_READY;
            }
        }
        if (count($foodDay)) {
            foreach ($foodDay as $k => $v) {
                if (in_array($v['f_id'], $alreadyFoods)) {
                    continue;
                } else {
                    array_push($foodList, [
                        'id' => $v['id'],
                        'status' => $status
                    ]);
                    array_push($alreadyFoods, $v['f_id']);
                }

            }
        }

        if ($auto) {
            if (!count($auto['foods'])) {
                throw new ParameterException(['msg' => "自动上架菜品未设置"]);
            }
            $autoFoods = $auto['foods'];
            foreach ($autoFoods as $k => $v) {
                if (in_array($v['food_id'], $alreadyFoods)) {
                    continue;
                }
                array_push($foodList, [
                    'f_id' => $v['food_id'],
                    'status' => FoodEnum::STATUS_UP,
                    'day' => $day,
                    'user_id' => 0,
                    'canteen_id' => $canteenId,
                    'default' => CommonEnum::STATE_IS_FAIL,
                    'dinner_id' => $dinnerId
                ]);
                array_push($alreadyFoods, $v['food_id']);

            }
        }

        if (count($foodList)) {
            $save = (new FoodDayStateT())->saveAll($foodList);
            if (!$save) {
                throw new SaveException(['msg' => '上架失败']);
            }
        }

    }

    public function handleAutoAll($type, $canteenId, $dinnerId, $day, $foods)
    {
        //获取自动上架配置
        $autoFoods = [];
        $foodList = [];
        $alreadyFoods = [];
        $timed = true;
        $dayWeek = date('w', strtotime($day));
        $auto = AutomaticT::infoToDinner($canteenId, $dinnerId, $dayWeek);
        if ($auto && count($auto['foods'])) {
            $autoFoods = $auto['foods'];
            $autoWeek = $auto['auto_week'];
            $repeatWeek = $auto['repeat_week'];
            $timed = $this->checkUpTime($autoWeek, $repeatWeek, $day);

        }
        $foodDay = FoodDayStateT::FoodStatus($canteenId, $dinnerId, $day);

        $handleFoods = explode(',', $foods);
        if (!count($handleFoods)) {
            throw new ParameterException(['msg' => '操作菜品为空']);
        }
        foreach ($foodDay as $k => $v) {
            if ($timed) {
                $status = $type;
            } else {
                $status = $type == FoodEnum::STATUS_DOWN;
            }
            if (in_array($v['f_id'], $handleFoods)) {
                array_push($foodList, [
                    'id' => $v['id'],
                    'status' => $type
                ]);
            }

        }
    }


}