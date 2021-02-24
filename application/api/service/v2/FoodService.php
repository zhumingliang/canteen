<?php


namespace app\api\service\v2;


use app\api\model\FoodDayStateT;
use app\api\model\FoodDayStateV;
use app\api\model\MenuT;
use app\api\service\MenuService;
use app\api\service\Token;

class FoodService
{
    public function foodsForOfficialPersonChoice($day)
    {
        $canteen_id = Token::getCurrentTokenVar('current_canteen_id');
        $foods = FoodDayStateV::foodsForOfficialPersonChoiceWithDay($day);
        $dinner = (new MenuService())->canteenMenus2($canteen_id);
        $foods = $this->prefixPersonChoiceFoods($foods, $dinner);
        return $foods;
    }

    private function prefixPersonChoiceFoods($foods, $dinner)
    {
        if (!count($dinner)) {
            return $dinner;
        }
        foreach ($dinner as $k => $v) {
            $menus = $v['menus'];
            if (count($menus)) {
                $data = [];
                foreach ($menus as $k2 => $v2) {
                    foreach ($foods as $k3 => $v3) {
                        if ($v2['id'] == $v3['m_id']) {
                            array_push($data, $foods[$k3]);
                            unset($foods[$k3]);
                        }
                    }
                    $menus[$k2]['foods'] = $data;
                }
            }
            $dinner[$k]['menus'] = $menus;

        }

        return $dinner;
    }

    public function haveFoodDay()
    {
        $canteen_id = Token::getCurrentTokenVar('current_canteen_id');
        $day = FoodDayStateT::haveFoodDay($canteen_id);
        return $day;

    }

}