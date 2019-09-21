<?php


namespace app\api\service;


use app\api\model\ShopModuleT;
use app\api\model\ShopProductStockT;
use app\api\model\ShopProductT;
use app\api\model\ShopT;
use app\api\model\SystemShopModuleT;
use app\lib\enum\CommonEnum;
use app\lib\enum\ModuleEnum;
use app\lib\enum\PayEnum;
use app\lib\enum\ShopEnum;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;
use app\lib\exception\UpdateException;
use think\Db;
use think\Exception;
use think\Model;
use think\Request;

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

    public function saveProduct($params)
    {
        try {
            Db::startTrans();
            if ($this->checkName($params['company_id'], $params['name'])) {
                throw new SaveException(['msg' => '商品名称已经存在']);
            }
            $product = ShopProductT::create($params);
            if (!$product) {
                throw new SaveException();
            }
            $this->saveStock($product->id, $params['count'], ShopEnum::STOCK_INIT);
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();;
            throw $e;
        }
    }

    public function updateProduct($params)
    {
        $product = ShopProductT::update($params);
        if (!$product) {
            throw new UpdateException();
        }
    }

    public function product($id)
    {
        $product = ShopProductT::where('id', $id)
            ->hidden(['create_time', 'update_time', 'state'])
            ->find();
        if (!$product) {
            throw new ParameterException(['msg' => '参数错误，商品不存在']);
        }
        $product->stock = $this->getProductStock($id);
        return $product;
    }

    private function saveStock($product_id, $stock, $type)
    {
        $data = [
            'product_id' => $product_id,
            'count' => $stock,
            'type' => $type,
            'state' => CommonEnum::STATE_IS_OK,
            'admin_id' => Token::getCurrentUid()
        ];
        $shopStock = ShopProductStockT::create($data);
        if (!$shopStock) {
            throw new SaveException(['msg' => '保存库存明细失败']);
        }

    }

    private function checkName($company_id, $name)
    {
        $product = ShopProductT::where('company_id', $company_id)
            ->where('name', $name)
            ->where('state', '<>', CommonEnum::STATE_IS_DELETE)
            ->count();
        return $product;
    }

    private function getProductStock($id)
    {
        return 100;

    }

    public function saveProductStock($params)
    {
        $params['admin_id'] = Token::getCurrentUid();
        $params['type'] = ShopEnum::STOCK_ADD;
        $params['state'] = CommonEnum::STATE_IS_OK;
        $stock = ShopProductStockT::create($params);
        if (!$stock) {
            throw new SaveException();
        }
    }

    public function officialProducts()
    {
        $canteen_id = Token::getCurrentTokenVar('current_canteen_id');
        if (empty($canteen_id)) {
            throw new ParameterException(['msg' => '请先选择饭堂']);
        }
        $company_id = (new CanteenService())->getCanteenBelongCompanyID($canteen_id);
        //获取企业所有类别
        $categories = (new CategoryService())->companyCategories($company_id);
        //获取企业所有商品
        $products = $this->companyProducts($company_id);
        return $this->prefixOfficialProducts($categories, $products);
    }

    private function companyProducts($company_id)
    {
        $products = ShopProductT::companyProducts($company_id);
        return $products;

    }

    private function prefixOfficialProducts($categories, $products)
    {
        if (empty($categories)) {
            return $categories;
        }
        foreach ($categories as $k => $v) {
            if (empty($products)) {
                break;
            }
            $data = [];
            foreach ($products as $k2 => $v2) {
                if ($v['id'] == $v2['category_id']) {
                    array_push($data, $products[$k2]);
                }
            }
            $categories[$k]['products'] = $data;
        }
        return $categories;

    }


}