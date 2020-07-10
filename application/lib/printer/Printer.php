<?php


namespace app\lib\printer;


use app\api\model\OrderT;
use app\api\model\PrinterT;
use app\api\service\OrderService;
use app\api\service\UserService;
use app\lib\enum\CommonEnum;
use app\lib\exception\ParameterException;
use zml\tp_tools\Redis;

class Printer extends PrinterBase
{
    public function printOrderDetail($canteenID, $orderID, $sortCode)
    {
        $order = OrderT::infoForPrinter($orderID);
        $outsider = $order['outsider'];
        //获取打印机信息
        $printer = PrinterT::getPrinter($canteenID, $outsider);
        if (!$printer) {
            return false;
        }
        $sn = $printer->code;
        $printerStatus = $this->queryPrinterStatus($sn);
        // print_r($printerStatus);
        /* if (strpos($printerStatus['data'], '离线') !== false || strpos($printerStatus['data'], '不正常') !== false) {
             return false;
         }*/

        $canteenName = $outsider == 1 ? "外部食堂" : "内部食堂";
        $name = (new  UserService())->getUserName($order['company_id'], $order['phone'], $outsider);
        $arr = $order['foods'];
        $A = 14;
        $B = 6;
        $C = 3;
        $D = 6;
        $money = $order['money'] + $order['sub_money'];
        $fixed = $order['fixed'];
        $content = '<CB>' . $canteenName . '｜' . $sortCode . '</CB><BR>';
        $content .= '订单号：' . $orderID . '<BR>';
        $content .= '确认时间：' . $order['confirm_time'] . '<BR>';
        $content .= '餐次：' . $order['dinner']['name'] . '<BR>';
        $content .= '姓名：' . $name . '<BR>';
        $content .= '电话号码：' . $order['phone'] . '<BR>';
        $content .= '--------------------------------<BR>';
        $content .= '订单详情：<BR>';
        $content .= '名称　　　　　 单价  数量 金额<BR>';
        $content .= '--------------------------------<BR>';

        if (count($arr)) {
            foreach ($arr as $k5 => $v5) {
                $name = $v5['name'];
                $price = $fixed == CommonEnum::STATE_IS_OK ? '*' : $v5['price'];
                $num = $v5['count'];
                $prices = $fixed == CommonEnum::STATE_IS_OK ? '*' : $v5['price'] * $v5['count'];
                $kw3 = '';
                $kw1 = '';
                $kw2 = '';
                $kw4 = '';
                $str = $name;
                $blankNum = $A;//名称控制为14个字节
                $lan = mb_strlen($str, 'utf-8');
                $m = 0;
                $j = 1;
                $blankNum++;
                $result = array();

                if (strlen($price) < $B) {
                    $k1 = $B - strlen($price);
                    for ($q = 0; $q < $k1; $q++) {
                        $kw1 .= ' ';
                    }
                    $price = $price . $kw1;
                }
                if (strlen($num) < $C) {
                    $k2 = $C - strlen($num);
                    for ($q = 0; $q < $k2; $q++) {
                        $kw2 .= ' ';
                    }
                    $num = $num . $kw2;
                }
                if (strlen($prices) < $D) {
                    $k3 = $D - strlen($prices);
                    for ($q = 0; $q < $k3; $q++) {
                        $kw4 .= ' ';
                    }
                    $prices = $prices . $kw4;
                }
                for ($i = 0; $i < $lan; $i++) {
                    $new = mb_substr($str, $m, $j, 'utf-8');
                    $j++;
                    if (mb_strwidth($new, 'utf-8') < $blankNum) {
                        if ($m + $j > $lan) {
                            $m = $m + $j;
                            $tail = $new;
                            $lenght = iconv("UTF-8", "GBK//IGNORE", $new);
                            $k = $A - strlen($lenght);
                            for ($q = 0; $q < $k; $q++) {
                                $kw3 .= ' ';
                            }
                            if ($m == $j) {
                                $tail .= $kw3 . ' ' . $price . ' ' . $num . ' ' . $prices;
                            } else {
                                $tail .= $kw3 . '<BR>';
                            }
                            break;
                        } else {
                            $next_new = mb_substr($str, $m, $j, 'utf-8');
                            if (mb_strwidth($next_new, 'utf-8') < $blankNum) continue;
                            else {
                                $m = $i + 1;
                                $result[] = $new;
                                $j = 1;
                            }
                        }
                    }
                }
                $head = '';
                foreach ($result as $key => $value) {
                    if ($key < 1) {
                        $v_lenght = iconv("UTF-8", "GBK//IGNORE", $value);
                        $v_lenght = strlen($v_lenght);
                        if ($v_lenght == 13) $value = $value . " ";
                        $head .= $value . ' ' . $price . ' ' . $num . ' ' . $prices;
                    } else {
                        $head .= $value . '<BR>';
                    }
                }
                $content .= $head . $tail;
            }
        }
        $content .= '--------------------------------<BR>';
        $content .= '<B>份数：' . $order['count'] . '<B><BR>';
        $content .= '<B>附加金额：' . $order['sub_money'] . '</B><BR>';
        $content .= '<B>金额：' . $money . '</B><BR>';
        $content .= '<B>备注：' . $order['remark'] . '</B><BR>';
        $content .= '二维码叫号/确认<BR>';
        $content .= '（第一次扫码为叫号，第二次扫码为完成取餐）<BR>';;
        $content .= '<QR>' . $order['qrcode_url'] . '</QR>';
        //把二维码字符串用标签套上即可自动生成二维码

        $printRes = $this->printMsg($sn, $content, 1);
        //print_r($printRes);
        if ($printRes['msg'] == 'ok' && $printRes['ret'] == 0) {
            return true;
        }
        return false;


    }


    public function printReissueOrderDetail($orderID)
    {
        $order = OrderT::infoForPrinter($orderID);
        $outsider = $order['outsider'];
        $canteen_id = $order['c_id'];
        $sortCode = $order['sort_code'];
        //获取打印机信息
        $printer = PrinterT::getPrinter($canteen_id, $outsider);
        if (!$printer) {
            throw new  ParameterException(['msg' =>"打印机不存在"]);
        }
        $sn = $printer->code;
        $printerStatus = $this->queryPrinterStatus($sn);
        if (strpos($printerStatus['data'], '离线') !== false || strpos($printerStatus['data'], '不正常') !== false) {
            throw new  ParameterException(['msg' =>"打印机状态异常"]);
        }
        $canteenName = $outsider == 1 ? "外部食堂" : "内部食堂";
        $name = (new  UserService())->getUserName($order['company_id'], $order['phone'], $outsider);
        $arr = $order['foods'];
        $A = 14;
        $B = 6;
        $C = 3;
        $D = 6;
        $money = $order['money'] + $order['sub_money'];
        $fixed = $order['fixed'];
        $content = '<CB>' . $canteenName . '｜' . $sortCode . '</CB><BR>';
        $content .= '订单号：' . $orderID . '<BR>';
        $content .= '确认时间：' . $order['confirm_time'] . '<BR>';
        $content .= '餐次：' . $order['dinner']['name'] . '<BR>';
        $content .= '姓名：' . $name . '<BR>';
        $content .= '电话号码：' . $order['phone'] . '<BR>';
        $content .= '--------------------------------<BR>';
        $content .= '订单详情：<BR>';
        $content .= '名称　　　　　 单价  数量 金额<BR>';
        $content .= '--------------------------------<BR>';

        if (count($arr)) {
            foreach ($arr as $k5 => $v5) {
                $name = $v5['name'];
                $price = $fixed == CommonEnum::STATE_IS_OK ? '*' : $v5['price'];
                $num = $v5['count'];
                $prices = $fixed == CommonEnum::STATE_IS_OK ? '*' : $v5['price'] * $v5['count'];
                $kw3 = '';
                $kw1 = '';
                $kw2 = '';
                $kw4 = '';
                $str = $name;
                $blankNum = $A;//名称控制为14个字节
                $lan = mb_strlen($str, 'utf-8');
                $m = 0;
                $j = 1;
                $blankNum++;
                $result = array();

                if (strlen($price) < $B) {
                    $k1 = $B - strlen($price);
                    for ($q = 0; $q < $k1; $q++) {
                        $kw1 .= ' ';
                    }
                    $price = $price . $kw1;
                }
                if (strlen($num) < $C) {
                    $k2 = $C - strlen($num);
                    for ($q = 0; $q < $k2; $q++) {
                        $kw2 .= ' ';
                    }
                    $num = $num . $kw2;
                }
                if (strlen($prices) < $D) {
                    $k3 = $D - strlen($prices);
                    for ($q = 0; $q < $k3; $q++) {
                        $kw4 .= ' ';
                    }
                    $prices = $prices . $kw4;
                }
                for ($i = 0; $i < $lan; $i++) {
                    $new = mb_substr($str, $m, $j, 'utf-8');
                    $j++;
                    if (mb_strwidth($new, 'utf-8') < $blankNum) {
                        if ($m + $j > $lan) {
                            $m = $m + $j;
                            $tail = $new;
                            $lenght = iconv("UTF-8", "GBK//IGNORE", $new);
                            $k = $A - strlen($lenght);
                            for ($q = 0; $q < $k; $q++) {
                                $kw3 .= ' ';
                            }
                            if ($m == $j) {
                                $tail .= $kw3 . ' ' . $price . ' ' . $num . ' ' . $prices;
                            } else {
                                $tail .= $kw3 . '<BR>';
                            }
                            break;
                        } else {
                            $next_new = mb_substr($str, $m, $j, 'utf-8');
                            if (mb_strwidth($next_new, 'utf-8') < $blankNum) continue;
                            else {
                                $m = $i + 1;
                                $result[] = $new;
                                $j = 1;
                            }
                        }
                    }
                }
                $head = '';
                foreach ($result as $key => $value) {
                    if ($key < 1) {
                        $v_lenght = iconv("UTF-8", "GBK//IGNORE", $value);
                        $v_lenght = strlen($v_lenght);
                        if ($v_lenght == 13) $value = $value . " ";
                        $head .= $value . ' ' . $price . ' ' . $num . ' ' . $prices;
                    } else {
                        $head .= $value . '<BR>';
                    }
                }
                $content .= $head . $tail;
            }
        }
        $content .= '--------------------------------<BR>';
        $content .= '<B>份数：' . $order['count'] . '</B><BR>';
        $content .= '<B>附加金额：' . $order['sub_money'] . '</B><BR>';
        $content .= '<B>金额：' . $money . '</B><BR>';
        $content .= '<B>备注：' . $order['remark'] . '</B><BR>';
        if ( $order['qrcode_url']){
            $content .= '二维码叫号/确认<BR>';
            $content .= '（第一次扫码为叫号，第二次扫码为完成取餐）<BR>';;
            $content .= '<QR>' . $order['qrcode_url'] . '</QR>';
        }
        //把二维码字符串用标签套上即可自动生成二维码

        $printRes = $this->printMsg($sn, $content, 1);
        //print_r($printRes);
        if ($printRes['msg'] == 'ok' && $printRes['ret'] == 0) {
            return true;
        }
        return false;


    }


    public function printOutsiderOrderDetail($orderID, $sn)
    {

        $canteenName = "外卖订单";
        $order = OrderT::outsiderInfoForPrinter($orderID);
        $name = $order['address']['name'];
        $phone = $order['address']['phone'];
        $address = $order['address']['province'] .
            $order['address']['city'] .
            $order['address']['area'] .
            $order['address']['address'];
        $arr = $order['foods'];
        $A = 14;
        $B = 6;
        $C = 3;
        $D = 6;
        $money = $order['money'] + $order['sub_money'] + $order['delivery_fee'];
        $fixed = $order['fixed'];
        $content = '<CB>' . $canteenName . '｜' . $orderID . '</CB><BR>';
        $content .= '订单号：' . $orderID . '<BR>';
        $content .= '餐次日期：' . $order['ordering_date'] . '<BR>';
        $content .= '餐次：' . $order['dinner']['name'] . '<BR>';
        $content .= '姓名：' . $name . '<BR>';
        $content .= '电话号码：' . $phone . '<BR>';
        $content .= '地址：' . $address . '<BR>';
        $content .= '--------------------------------<BR>';
        $content .= '订单详情：<BR>';
        $content .= '名称　　　　　 单价  数量 金额<BR>';
        $content .= '--------------------------------<BR>';

        if (count($arr)) {
            foreach ($arr as $k5 => $v5) {
                $name = $v5['name'];
                $price = $fixed == CommonEnum::STATE_IS_OK ? '*' : $v5['price'];
                $num = $v5['count'];
                $prices = $fixed == CommonEnum::STATE_IS_OK ? '*' : $v5['price'] * $v5['count'];
                $kw3 = '';
                $kw1 = '';
                $kw2 = '';
                $kw4 = '';
                $str = $name;
                $blankNum = $A;//名称控制为14个字节
                $lan = mb_strlen($str, 'utf-8');
                $m = 0;
                $j = 1;
                $blankNum++;
                $result = array();

                if (strlen($price) < $B) {
                    $k1 = $B - strlen($price);
                    for ($q = 0; $q < $k1; $q++) {
                        $kw1 .= ' ';
                    }
                    $price = $price . $kw1;
                }
                if (strlen($num) < $C) {
                    $k2 = $C - strlen($num);
                    for ($q = 0; $q < $k2; $q++) {
                        $kw2 .= ' ';
                    }
                    $num = $num . $kw2;
                }
                if (strlen($prices) < $D) {
                    $k3 = $D - strlen($prices);
                    for ($q = 0; $q < $k3; $q++) {
                        $kw4 .= ' ';
                    }
                    $prices = $prices . $kw4;
                }
                for ($i = 0; $i < $lan; $i++) {
                    $new = mb_substr($str, $m, $j, 'utf-8');
                    $j++;
                    if (mb_strwidth($new, 'utf-8') < $blankNum) {
                        if ($m + $j > $lan) {
                            $m = $m + $j;
                            $tail = $new;
                            $lenght = iconv("UTF-8", "GBK//IGNORE", $new);
                            $k = $A - strlen($lenght);
                            for ($q = 0; $q < $k; $q++) {
                                $kw3 .= ' ';
                            }
                            if ($m == $j) {
                                $tail .= $kw3 . ' ' . $price . ' ' . $num . ' ' . $prices;
                            } else {
                                $tail .= $kw3 . '<BR>';
                            }
                            break;
                        } else {
                            $next_new = mb_substr($str, $m, $j, 'utf-8');
                            if (mb_strwidth($next_new, 'utf-8') < $blankNum) continue;
                            else {
                                $m = $i + 1;
                                $result[] = $new;
                                $j = 1;
                            }
                        }
                    }
                }
                $head = '';
                foreach ($result as $key => $value) {
                    if ($key < 1) {
                        $v_lenght = iconv("UTF-8", "GBK//IGNORE", $value);
                        $v_lenght = strlen($v_lenght);
                        if ($v_lenght == 13) $value = $value . " ";
                        $head .= $value . ' ' . $price . ' ' . $num . ' ' . $prices;
                    } else {
                        $head .= $value . '<BR>';
                    }
                }
                $content .= $head . $tail;
            }
        }
        $content .= '--------------------------------<BR>';
        $content .= '<B>份数：' . $order['count'] . '<B><BR>';
        $content .= '<B>基本金额：' . $order['money'] . '</B><BR>';
        $content .= '<B>附加金额：' . $order['sub_money'] . '</B><BR>';
        $content .= '<B>配送费：' . $order['delivery_fee'] . '</B><BR>';
        $content .= '<B>总计：' . $money . '</B><BR>';
        $content .= '<B>备注 ' . $order['remark'] . '</B><BR>';
        $printRes = $this->printMsg($sn, $content, 1);
        if ($printRes['msg'] == 'ok' && $printRes['ret'] == 0) {
            return true;
        }
        return false;


    }

    public function checkPrinter($canteenID, $outsider)
    {
        //获取打印机信息
        $printer = PrinterT::getPrinter($canteenID, $outsider);
        if (!$printer) {
            throw  new  ParameterException(['msg' => '小票打印失败提示，未设置打印机']);
        }
        $sn = $printer->code;
        $printerStatus = $this->queryPrinterStatus($sn);
        if (strpos($printerStatus['data'], '离线') !== false || strpos($printerStatus['data'], '不正常') !== false) {
            throw  new  ParameterException(['msg' => '小票打印失败提示，打印机状态异常']);
        }
        return $sn;
    }

    private function saveRedisNoPrint($printerID, $sn, $content)
    {
        $list = "noPrint";
        $data = json_encode([
            'printerID' => $printerID,
            'sn' => $sn,
            'content' => $content

        ]);
        Redis::instance()->lPush($list, $data);
    }
}