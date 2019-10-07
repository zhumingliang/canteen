<?php


namespace app\api\service;


use app\api\model\CanteenT;
use app\api\model\DinnerT;
use app\api\model\MenuDetailT;
use app\api\model\MenuT;
use app\lib\enum\CommonEnum;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;
use think\Db;
use think\Exception;

class MenuService
{
    public function save($params)
    {
        try {
            Db::startTrans();
            $detail = $params['detail'];
            $detail_arr = json_decode($detail, true);
            if (!count($detail_arr)) {
                throw new ParameterException();
            }
            foreach ($detail_arr as $k => $v) {
                $detail_arr[$k]['d_id'] = $params['d_id'];
                $detail_arr[$k]['c_id'] = $params['c_id'];
                $detail_arr[$k]['state'] = CommonEnum::STATE_IS_OK;
            }
            $menuDetail = (new MenuT())->saveAll($detail_arr);
            if (!$menuDetail) {
                throw new SaveException(['msg' => '新增菜单明细失败']);
            }
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw  $e;
        }
    }

    public function update($params)
    {
        try {
            Db::startTrans();
            $detail = $params['detail'];
            $detail_arr = json_decode($detail, true);
            if (!count($detail_arr)) {
                throw new ParameterException();
            }
            foreach ($detail_arr as $k => $v) {
                $detail_arr[$k]['m_id'] = $params['m_id'];
                $detail_arr[$k]['c_id'] = $params['c_id'];
            }
            $menuDetail = (new MenuDetailT())->saveAll($detail_arr);
            if (!$menuDetail) {
                throw new SaveException(['msg' => '更新菜单明细失败']);
            }
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw  $e;
        }
    }

    public function companyMenus($page, $size, $company_id,$canteen_id)
    {
        $menus = CanteenT::canteensMenu($page, $size, $company_id,$canteen_id);
        return $menus;
    }

    public function canteenMenus($canteen_id)
    {
        $menus = DinnerT::canteenDinnerMenus($canteen_id);
        return $menus;
    }

    public function dinnerMenus($dinner_id){
        $menus=MenuT::dinnerMenus($dinner_id);
        return $menus;
    }


}