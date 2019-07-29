<?php


namespace app\api\service;


use app\api\model\ShopModuleT;
use app\api\model\ShopT;
use app\api\model\SystemShopModuleT;
use app\lib\enum\CommonEnum;
use app\lib\exception\SaveException;

class ShopService
{
    public function save($c_id)
    {
        $shop = ShopT::create([
            'state' => CommonEnum::STATE_IS_OK,
            'c_id' => $c_id
        ]);
        if (!$shop) {
            throw  new SaveException();
        }

        $this->saveDefaultCanteen($shop->id);
    }

    private function saveDefaultCanteen($s_id)
    {
        $modules = SystemShopModuleT::defaultModules();
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
                    's_id' => $s_id,
                    'state' => CommonEnum::STATE_IS_OK,
                    'm_id' => $v->id,
                    'type' => $v->type,
                    'order' => $order
                ];


            }
            if (!count($data)) {
                return true;
            }
            $res = (new ShopModuleT())->saveAll($data);
            if (!$res) {
                throw new SaveException();
            }

        }


    }


}