<?php


namespace app\api\service;


use app\api\model\CanteenCommentT;
use app\api\model\ShopModuleT;
use app\api\model\ShopOrderDetailT;
use app\api\model\ShopOrderQrcodeT;
use app\api\model\ShopOrderStatisticV;
use app\api\model\ShopOrderSupplierV;
use app\api\model\ShopOrderT;
use app\api\model\ShopOrderV;
use app\api\model\ShopProductCommentT;
use app\api\model\ShopProductStatisticV;
use app\api\model\ShopProductStockBalanceV;
use app\api\model\ShopProductStockT;
use app\api\model\ShopProductStockV;
use app\api\model\ShopProductT;
use app\api\model\ShopT;
use app\api\model\SystemShopModuleT;
use app\lib\enum\CommonEnum;
use app\lib\enum\ModuleEnum;
use app\lib\enum\OrderEnum;
use app\lib\enum\PayEnum;
use app\lib\enum\ShopEnum;
use app\lib\exception\AuthException;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;
use app\lib\exception\UpdateException;
use think\Db;
use think\Exception;
use function GuzzleHttp\Promise\each_limit;


class ShopService
{
    public function save($params)
    {
        try {
            $shop = ShopT::where('c_id', $params['c_id'])
                ->where('state', CommonEnum::STATE_IS_OK)
                ->count('id');
            if ($shop) {
                throw new SaveException(['msg' => '该企业已经有小卖部，不能重复添加']);
            }
            $params['state'] = CommonEnum::STATE_IS_OK;
            $shop = ShopT::create($params);
            if (!$shop) {
                throw  new SaveException();
            }
            $this->saveDefaultShopModule($shop->id);
            return [
                'shop_id' => $shop->id
            ];
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }

    }

    private function saveDefaultShopModule($s_id)
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
            (new AuthorService())->checkAuthorSupplier();
            $params['company_id'] = Token::getCurrentTokenVar('company_id');
            $params['supplier_id'] = Token::getCurrentUid();
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
        (new AuthorService())->checkAuthorSupplier();
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
        (new AuthorService())->checkAuthorSupplier();
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
        $company_id = Token::getCurrentTokenVar('current_company_id');
        //获取企业所有类别
        $categories = (new CategoryService())->companyCategories($company_id);
        //获取企业所有商品
        $products = $this->companyProducts($company_id);
        return $this->prefixOfficialProducts($categories, $products);
    }

    public function companyProducts($company_id)
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
        (new AuthorService())->checkAuthorSupplier();
        $supplier_id = Token::getCurrentUid();
        $products = ShopProductStockV::supplierProducts($supplier_id, $category_id, $page, $size);
        return $products;

    }

    public function cmsProducts($supplier_id, $category_id, $page, $size)
    {
        $company_id = Token::getCurrentTokenVar('company_id');
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
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();;
            throw  $e;
        }
    }

    private function prefixOrderQrcode($o_id)
    {
        $code = QRcodeNUmber();
        $url = sprintf(config("setting.qrcode_url"), 'shop', $code);
        $qrcode_url = (new QrcodeService())->qr_code($url);
        $time_begin = date('Y-m-d H:i:s');
        $time_end = date('Y-m-d H:i:s', strtotime("+" . config("setting.shop_qrcode_expire_in") . "minute", time()));
        $data = [
            'code' => $code,
            'o_id' => $o_id,
            'url' => $qrcode_url,
            'end_time' => $time_end
        ];
        $qrcode = ShopOrderQrcodeT::create($data);
        if (!$qrcode) {
            throw new SaveException(['msg' => '生成提货二维码失败']);
        }
        return [
            'time_begin' => $time_begin,
            'time_end' => $time_end,
            'url' => $qrcode_url
        ];
    }

    private function updateOrderQrcode($id)
    {
        $code = getRandChar(12);
        $url = sprintf(config("setting.qrcode_url"), 'shop', $code);
        $qrcode_url = (new QrcodeService())->qr_code($url);
        $data = [
            'id' => $id,
            'code' => $code,
            'url' => $qrcode_url,
            'end_time' => date('Y-m-d H:i:s', strtotime("+" . config("setting.shop_qrcode_expire_in") . "minute", time()))
        ];
        $qrcode = ShopOrderQrcodeT::update($data);
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
        $params['money'] = $money * $params['count'];
        $params['pay_way'] = $payCheck;
        $params['pay'] = CommonEnum::STATE_IS_OK;
        $params['order_num'] = makeOrderNo();

        $phone = Token::getCurrentPhone();
        $current_company_id = Token::getCurrentTokenVar('current_company_id');
        $staff = (new UserService())->getUserCompanyInfo($phone, $current_company_id);
        $params['staff_id'] = $staff->id;
        $params['staff_type_id'] = $staff->t_id;
        $params['department_id'] = $staff->d_id;
        $params['company_id'] = $current_company_id;
        $params['phone'] = $phone;
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
        $company_id = Token::getCurrentTokenVar('current_company_id');
        $phone = Token::getCurrentTokenVar('phone');
        $balance = (new WalletService())->getUserBalance($company_id, $phone);
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
        return [
            'comments' => $comments,
            'productScore' => $this->productScore($product_id)

        ];
    }

    public function orderCancel($id)
    {
        $order = ShopOrderT::get($id);
        if (!$order) {
            throw new ParameterException(['msg' => '订单不存在']);
        }
        if ($order->used == CommonEnum::STATE_IS_OK) {
            throw new UpdateException(['msg' => '订单已经完成，不能取消']);
        }
        if ($order->u_id != Token::getCurrentUid()) {
            throw new AuthException();
        }
        $distribution = $order->distribution;
        if ($distribution == OrderEnum::USER_ORDER_OUTSIDE) {
            //外送
            if ($order->send == CommonEnum::STATE_IS_OK) {
                throw new UpdateException(['msg' => '订单正在派送，不能取消']);
            }
        }
        $order->state = CommonEnum::STATE_IS_FAIL;
        $res = $order->save();
        if (!$res) {
            throw new UpdateException(['msg' => '订单取消失败']);
        }

    }

    public function deliveryCode($order_id)
    {
        $order = ShopOrderT::get($order_id);
        if (!$order) {
            throw new ParameterException(['msg' => '订单不存在']);
        }
        if ($order->u_id != Token::getCurrentUid()) {
            throw new AuthException();
        }
        if ($order->distribution == OrderEnum::USER_ORDER_OUTSIDE) {
            throw new ParameterException(['msg' => '订单为外送订单，无提货券']);
        }

        $qrcode = ShopOrderQrcodeT::where('o_id', $order_id)->find();
        if (!$qrcode) {
            return $this->prefixOrderQrcode($order_id);
        }
        if (strtotime($qrcode->end_time) < time() - config("setting.shop_qrcode_expire_in") * 60) {
            return $this->updateOrderQrcode($qrcode->id);
        }
        return [
            'time_begin' => $qrcode->update_time,
            'time_end' => $qrcode->end_time,
            'url' => $qrcode->url
        ];
    }

    //获取商品评分
    public function productScore($product_id)
    {
        $taste = ShopProductCommentT::where('product_id', $product_id)->avg('taste');
        $service = ShopProductCommentT::where('product_id', $product_id)->avg('service');
        return [
            'taste' => round($taste, 1),
            'service' => round($service, 1),
        ];
    }

    public function takingMode()
    {
        $company_id = Token::getCurrentTokenVar('current_company_id');
        $shop = ShopT::where('c_id', $company_id)->field('taking_mode')
            ->find();
        return $shop;
    }

    public function orderDetailStatisticToSupplier($page, $size, $category_id, $product_id, $time_begin, $time_end)
    {
        (new AuthorService())->checkAuthorSupplier();
        $supplier_id = Token::getCurrentUid();
        $statistic = ShopOrderSupplierV::orderDetailStatisticToSupplier($page, $size, $category_id, $product_id, $time_begin, $time_end, $supplier_id);
        return $statistic;
    }

    public function exportOrderDetailStatisticToSupplier($category_id, $product_id, $time_begin, $time_end)
    {
        (new AuthorService())->checkAuthorSupplier();
        $supplier_id = Token::getCurrentUid();
        $statistic = ShopOrderSupplierV::exportOrderDetailStatisticToSupplier($category_id, $product_id, $time_begin, $time_end, $supplier_id);
        $header = ['下单时间', '类型', '商品名称', '商品数量', '商品金额（元）'];
        $file_name = "订单明细查询";
        $url = (new ExcelService())->makeExcel($header, $statistic, $file_name);
        return [
            'url' => config('setting.domain') . $url
        ];
    }

    public function orderStatisticToManager($page, $size, $department_id, $name, $phone, $status, $time_begin, $time_end, $company_id)
    {
        $statistic = ShopOrderV::orderStatisticToManager($page, $size, $department_id, $name, $phone, $status, $time_begin, $time_end, $company_id);
        return $statistic;
    }

    public function exportOrderStatisticToManager($department_id, $name, $phone, $status, $time_begin, $time_end, $company_id)
    {
        $statistic = ShopOrderV::exportOrderStatisticToManager($department_id, $name, $phone, $status, $time_begin, $time_end, $company_id);
        $statistic = $this->prefixOrderStatisticToExport($statistic);
        $header = ['序号', '下单时间', '结束时间', '姓名', '手机号', '商品数量', '商品金额（元）', '地址', '状态', '类型', '名称', '单位', '数量', '金额'];
        $file_name = "订单明细查询";
        $url = (new ExcelService())->makeExcelMerge($header, $statistic, $file_name, 9);
        return [
            'url' => config('setting.domain') . $url
        ];
    }

    private function prefixOrderStatisticToExport($list)
    {
        $dataList = [];
        if (!count($list)) {
            return $dataList;
        }
        $i = 2;
        foreach ($list as $k => $v) {
            $address = $v['address'];
            $products = $v['products'];
            if (empty($products)) {
                array_push($dataList, [
                    'number' => $k + 1,
                    'create_time' => $v['create_time'],
                    'used_time' => $v['used_time'],
                    'username' => $v['username'],
                    'phone' => $v['phone'],
                    'order_count' => $v['order_count'],
                    'money' => $v['money'],
                    'address' => empty($address['address']) ? '' : $address['address'],
                    'status_text' => $v['status_text'],
                    'category' => '',
                    'name' => '',
                    'unit' => '',
                    'count' => '',
                    'price' => '',
                    'merge' => CommonEnum::STATE_IS_FAIL,
                    'start' => 0,
                    'end' => 0,
                ]);
                $i++;
                continue;
            }

            foreach ($products as $k2 => $v2) {
                array_push($dataList, [
                    'number' => $k + 1,
                    'create_time' => $v['create_time'],
                    'used_time' => $v['used_time'],
                    'username' => $v['username'],
                    'phone' => $v['phone'],
                    'order_count' => $v['order_count'],
                    'money' => $v['money'],
                    'address' => empty($address['address']) ? '' : $address['address'],
                    'status_text' => $v['status_text'],
                    'category' => $v2['category'],
                    'name' => $v2['name'],
                    'unit' => $v2['unit'],
                    'count' => $v2['count'],
                    'price' => $v2['price'],
                    'merge' => CommonEnum::STATE_IS_OK,
                    'start' => $k2 == 0 ? $i : $i - 1,
                    'end' => $i
                ]);
                $i++;
            }

        }

        return $dataList;

    }

    public function salesReportToSupplier($page, $size, $time_begin, $time_end)
    {

        $supplier_id = (new AuthorService())->checkAuthorSupplier();
        //获取供应商所有商品
        $products = ShopProductT::supplierProducts($page, $size, $time_begin, $time_end, $supplier_id);
        $sale_money = ShopProductStatisticV::saleMoney($supplier_id, $time_begin, $time_end);
        $products['money'] = $sale_money;
        return $products;

    }

    public function exportSalesReportToSupplier($time_begin, $time_end)
    {

        $supplier_id = (new AuthorService())->checkAuthorSupplier();
        //获取供应商所有商品
        $products = ShopProductT::supplierProducts(1, 10000, $time_begin, $time_end, $supplier_id);
        $header = ['序号','名称', '单价（元）', '单位', '总进货量', '总销售量', '总销售额（元）'];
        $file_name = $time_begin . "-" . $time_end . "-进销报表";
        $url = (new ExcelService())->makeExcel($header, $products, $file_name);
        return [
            'url' => config('setting.domain') . $url
        ];

    }

    public function salesReportToManager($page, $size, $time_begin, $time_end, $supplier_id)
    {
        $products = ShopProductT::supplierProducts($page, $size, $time_begin, $time_end, $supplier_id);
        $sale_money = ShopProductStatisticV::saleMoney($supplier_id, $time_begin, $time_end);
        $products['money'] = $sale_money;
        return $products;
    }


    public function exportSalesReportToManager($time_begin, $time_end, $supplier_id)
    {
        $products = ShopProductT::supplierProducts(1, 10000, $time_begin, $time_end, $supplier_id);
        $products = $this->prefixExportSalesReport($products['data']);
        $header = ['序号','名称', '单价（元）', '单位', '总进货量', '总销售量', '总销售额（元）'];
        $file_name = $time_begin . "-" . $time_end . "-进销报表";
        $url = (new ExcelService())->makeExcel($header, $products, $file_name);
        return [
            'url' => config('setting.domain') . $url
        ];
    }

    private function prefixExportSalesReport($statistic)
    {
        $dataList = [];
        $all_money = 0;
        if (!empty($statistic)) {
            foreach ($statistic as $k => $v) {
                $sale_sum = empty($v['sale_sum']) ? 0 : $v['sale_sum'];
                $money = $v['price'] * $sale_sum;
                array_push($dataList, [
                    'number' => $k + 1,
                    'name' => $v['name'],
                    'price' => $v['price'],
                    'unit' => $v['unit'],
                    'purchase_sum' => $v['purchase_sum'],
                    'sale_sum' => $sale_sum,
                    'money' => $v['price'] * $sale_sum
                ]);
                $all_money += $money;
            }
        }

        array_push($dataList, [
            'number' => '合计',
            'name' => '',
            'price' => '',
            'unit' => '',
            'purchase_sum' => 0,
            'sale_sum' => 0,
            'money' => $all_money
        ]);
        return $dataList;

    }

    public function consumptionStatistic($page, $size, $category_id, $product_id,
                                         $status, $time_begin, $time_end, $type, $department_id, $username, $company_id)
    {
        $field = '';
        $supplier_id = 0;
        if (Token::getCurrentTokenVar('type') == 'supplier') {
            $supplier_id = (new AuthorService())->checkAuthorSupplier();
            $company_id = Token::getCurrentTokenVar('company_id');
        }
        if ($type == ShopEnum::STATISTIC_BY_CATEGORY) {
            $statistic = ShopOrderStatisticV::consumptionStatisticGroupByCategoryID($page, $size, $category_id, $product_id,
                $status, $time_begin, $time_end, $supplier_id, $department_id, $username, $company_id);
            $field = 'category_id';
        } else if ($type == ShopEnum::STATISTIC_BY_PRODUCT) {
            $statistic = ShopOrderStatisticV::consumptionStatisticGroupByProductID($page, $size, $category_id, $product_id,
                $status, $time_begin, $time_end, $supplier_id, $department_id, $username, $company_id);
            $field = 'product_id';
        } else if ($type == ShopEnum::STATISTIC_BY_STATUS) {
            $statistic = ShopOrderStatisticV::consumptionStatisticGroupByStatus($page, $size, $category_id, $product_id,
                $status, $time_begin, $time_end, $supplier_id, $department_id, $username, $company_id);
        } else if ($type == ShopEnum::STATISTIC_BY_DEPARTMENT) {
            $statistic = ShopOrderStatisticV::consumptionStatisticGroupByDepartmentID($page, $size, $category_id, $product_id,
                $status, $time_begin, $time_end, $supplier_id, $department_id, $username, $company_id);
            $field = 'department_id';
        } else if ($type == ShopEnum::STATISTIC_BY_USERNAME) {
            $statistic = ShopOrderStatisticV::consumptionStatisticGroupByUsername($page, $size, $category_id, $product_id,
                $status, $time_begin, $time_end, $supplier_id, $department_id, $username, $company_id);
            $field = 'staff_id';
        } else {
            throw new ParameterException();
        }

        if (empty($field)) {
            $statisticCount = 0;
        } else {
            $statisticCount = ShopOrderStatisticV::statisticCount($category_id, $product_id,
                $status, $time_begin, $time_end, $supplier_id, $field, $department_id, $username, $company_id);
        }

        $money = ShopOrderStatisticV::statisticMoney($category_id, $product_id,
            $status, $time_begin, $time_end, $supplier_id, $department_id, $username, $company_id);
        $statistic['statistic'] = [
            'statisticCount' => $statisticCount,
            'statisticMoney' => $money
        ];
        return $statistic;
    }


    public function exportConsumptionStatistic($category_id, $product_id,
                                               $status, $time_begin, $time_end, $type, $department_id, $username, $company_id)
    {
        $field = '';
        $supplier_id = 0;
        if (Token::getCurrentTokenVar('type') == 'supplier') {
            $supplier_id = (new AuthorService())->checkAuthorSupplier();
            $company_id = Token::getCurrentTokenVar('company_id');
        }
        if ($type == ShopEnum::STATISTIC_BY_CATEGORY) {
            $statistic = ShopOrderStatisticV::consumptionStatisticGroupByCategoryID(1, 1000, $category_id, $product_id,
                $status, $time_begin, $time_end, $supplier_id, $department_id, $username, $company_id);
            $field = 'category_id';
        } else if ($type == ShopEnum::STATISTIC_BY_PRODUCT) {
            $statistic = ShopOrderStatisticV::consumptionStatisticGroupByProductID(1, 1000, $category_id, $product_id,
                $status, $time_begin, $time_end, $supplier_id, $department_id, $username, $company_id);
            $field = 'product_id';
        } else if ($type == ShopEnum::STATISTIC_BY_STATUS) {
            $statistic = ShopOrderStatisticV::consumptionStatisticGroupByStatus(1, 1000, $category_id, $product_id,
                $status, $time_begin, $time_end, $supplier_id, $department_id, $username, $company_id);
        } else if ($type == ShopEnum::STATISTIC_BY_DEPARTMENT) {
            $statistic = ShopOrderStatisticV::consumptionStatisticGroupByDepartmentID(1, 1000, $category_id, $product_id,
                $status, $time_begin, $time_end, $supplier_id, $department_id, $username, $company_id);
            $field = 'department_id';
        } else if ($type == ShopEnum::STATISTIC_BY_USERNAME) {
            $statistic = ShopOrderStatisticV::consumptionStatisticGroupByUsername(1, 1000, $category_id, $product_id,
                $status, $time_begin, $time_end, $supplier_id, $department_id, $username, $company_id);
            $field = 'staff_id';
        } else {
            throw new ParameterException();
        }

        if (empty($field)) {
            $statisticCount = 0;
        } else {
            $statisticCount = ShopOrderStatisticV::statisticCount($category_id, $product_id,
                $status, $time_begin, $time_end, $supplier_id, $field, $department_id, $username, $company_id);
        }

        $money = ShopOrderStatisticV::statisticMoney($category_id, $product_id,
            $status, $time_begin, $time_end, $supplier_id, $department_id,
            $username, $company_id);

        $statistics = $this->prefixConsumptionStatistic($statistic['data'], $statisticCount, $money);
        $header = ['序号', '统计变量', '下单时间', '结束时间', '姓名', '部门', '类型', '商品名称', '单位', '数量', '商品总金额（元）'];
        $file_name = "消费订单汇总查询";
        $url = (new ExcelService())->makeExcel($header, $statistics, $file_name);
        return [
            'url' => config('setting.domain') . $url
        ];


    }

    private function prefixConsumptionStatistic($statistic, $count, $money)
    {
        $dataList = [];
        if (!empty($statistic)) {
            foreach ($statistic as $k => $v) {
                array_push($dataList, [
                    'number' => $k + 1,
                    'statistic' => $v['statistic'],
                    'create_time' => $v['create_time'],
                    'used_time' => $v['used_time'],
                    'username' => $v['username'],
                    'department' => $v['department'],
                    'category' => $v['category'],
                    'product' => $v['product'],
                    'unit' => $v['unit'],
                    'order_count' => $v['order_count'],
                    'order_money' => $v['order_money'],
                ]);
            }
        }

        array_push($dataList, [
            'number' => '合计',
            'statistic' => $count,
            'create_time' => '',
            'used_time' => '',
            'username' => '',
            'department' => '',
            'category' => '',
            'product' => '',
            'unit' => '',
            'order_count' => $count,
            'order_money' => $money,
        ]);
        return $dataList;

    }

    public function companyProductsToSearch($company_id, $product)
    {
        if (empty($company_id)) {
            $company_id = Token::getCurrentTokenVar('company_id');
        }
        $products = ShopProductT::companyProductsToSearch($company_id, $product);
        return $products;
    }

    public function supplierProductsToSearch($product)
    {
        $supplier_id = (new AuthorService())->checkAuthorSupplier();
        $products = ShopProductT::supplierProductsToSearch($supplier_id, $product);
        return $products;
    }


}