<?php


namespace app\api\service;


use app\api\model\OrderDetailT;
use app\api\model\ShopModuleT;
use app\api\model\ShopOrderDetailT;
use app\api\model\ShopOrderQrcodeT;
use app\api\model\ShopOrderT;
use app\api\model\ShopProductCommentT;
use app\api\model\ShopProductStockBalanceV;
use app\api\model\ShopProductStockT;
use app\api\model\ShopProductStockV;
use app\api\model\ShopProductT;
use app\api\model\ShopT;
use app\api\model\StaffQrcodeT;
use app\api\model\SystemShopModuleT;
use app\lib\enum\CommonEnum;
use app\lib\enum\ModuleEnum;
use app\lib\enum\PayEnum;
use app\lib\enum\ShopEnum;
use app\lib\exception\AuthException;
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

    public function supplierProducts($category_id, $page, $size)
    {
        if (Token::getCurrentTokenVar('type') != 'supplier') {
            throw new AuthException();
        }
        $supplier_id = Token::getCurrentUid();
        $products = ShopProductStockV::supplierProducts($supplier_id, $category_id, $page, $size);
        return $products;

    }

    public function cmsProducts($supplier_id, $category_id, $page, $size)
    {
        $company_id = Token::getCurrentTokenVar('c_id');
        if (empty($company_id)) {
            throw new AuthException();
        }
        $products = ShopProductStockV::cmsProducts($company_id, $supplier_id, $category_id, $page, $size);
        return $products;

    }

    public function saveOrder($params)
    {
        try {
            Db::startTrans();
            $orderData = $this->prepareOrderData($params);
            $order = ShopOrderT::create($orderData);
            if (!$order) {
                throw new SaveException(['msg' => '创建订单失败']);
            }
            $detailData = $this->prepareOrderDetailData($order->id, json_decode($params['products'], true));
            $detail = (new ShopOrderDetailT())->saveAll($detailData);
            if (!$detail) {
                throw new SaveException(['msg' => '创建订单明细失败']);
            }
            //订单自取时，生成订单取货二维码
            if ($params['distribution'] == ShopEnum::ORDER_GET_SELF) {
                $this->prefixOrderQrcode($order->id);
            }
            Db::commit();;
        } catch (Exception $e) {
            Db::rollback();;
            throw  $e;
        }
    }

    private function prefixOrderQrcode($o_id)
    {
        $code = getRandChar(12);
        $url = sprintf(config("setting.qrcode_url"), 'shop', $code);
        $qrcode_url = (new QrcodeService())->qr_code($url);
        $data = [
            'code' => $code,
            'o_id' => $o_id,
            'url' => $qrcode_url
        ];
        $qrcode = ShopOrderQrcodeT::create($data);
        if (!$qrcode) {
            throw new SaveException(['msg' => '生成提货二维码失败']);
        }
        return $qrcode_url;
    }

    private function prepareOrderData($params)
    {
        $products = json_decode($params['products'], true);
        $u_id = Token::getCurrentUid();
        $money = $this->getProductsMoney($products);
        $payCheck = $this->checkMoney($u_id, $money);
        if (!$payCheck) {
            throw new  SaveException(['msg' => '余额不足，请先充值']);
        }
        $params['u_id'] = $u_id;
        $params['money'] = $money;
        $params['pay_way'] = $payCheck;
        $params['pay'] = CommonEnum::STATE_IS_OK;
        $params['order_num'] = makeOrderNo();

        $phone = Token::getCurrentPhone();
        $current_canteen_id = Token::getCurrentTokenVar('current_canteen_id');
        $staff = (new UserService())->getUserCompanyInfo($phone, $current_canteen_id);
        $params['staff_type_id'] = $staff->t_id;
        $params['department_id'] = $staff->d_id;
        $params['company_id'] = $staff->company_id;
        return $params;
    }

    private function prepareOrderDetailData($order_id, $products)
    {
        foreach ($products as $k => $v) {
            $this->checkProductStock($v['product_id'], $v['count']);
            $products[$k]['o_id'] = $order_id;
            $products[$k]['state'] = CommonEnum::STATE_IS_OK;
        }
        return $products;

    }

    private function checkProductStock($product_id, $count)
    {
        $stock = ShopProductStockBalanceV::getProductStock($product_id);
        if ($stock < $count) {
            throw new SaveException(['msg' => '商品库存不足']);
        }

    }

    private function getProductsMoney($products)
    {
        if (empty($products)) {
            throw new ParameterException(['msg' => '商品数据格式错误']);
        }
        $money = 0;
        foreach ($products as $k => $v) {
            $money += $v['price'];
        }
        if (!$money) {
            throw new ParameterException(['msg' => '商品数据格式错误']);
        }
        return $money;

    }

    private function checkMoney($u_id, $money)
    {
        $balance = 100;
        if ($balance < $money) {
            throw new SaveException(['errorCode' => 49000, 'msg' => '余额不足']);
        }
        return PayEnum::PAY_BALANCE;

    }

    public function saveProductComment($params)
    {
        $params['u_id'] = Token::getCurrentUid();
        $comment = ShopProductCommentT::create($params);
        if (!$comment) {
            throw  new SaveException();
        }
    }

    public function productComments($product_id, $page, $size)
    {
        $comments = ShopProductCommentT::productComments($product_id, $page, $size);
        return $comments;
    }
}