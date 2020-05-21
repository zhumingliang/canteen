<?php


namespace app\lib\printer;


use app\api\model\OrderT;
use app\api\model\PrinterT;
use app\api\service\OrderService;
use app\api\service\UserService;

class Printer extends PrinterBase
{
    public function printOrderDetail($canteenID, $orderID, $outsider, $sortCode)
    {
        //获取打印机信息
        /* $printer = PrinterT::getPrinter($canteenID, $outsider);
         if (!$printer) {
             return false;
         }
         $sn = $printer->code;*/
        $sn = '921527631';
        $canteenName = $outsider == 1 ? "外部食堂" : "内部食堂";
        $order = OrderT::infoForPrinter($orderID);
        $name = (new  UserService())->getUserName($order['company_id'], $order['phone'], $outsider);
        $arr = $order['foods'];
        $A = 14;
        $B = 6;
        $C = 3;
        $D = 6;
        $money = $order['money'] + $order['sub_money'];
        $content = '<CB>' . $canteenName . '｜' . $sortCode . '</CB><BR>';
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
                $price = $v5['price'];
                $num = $v5['count'];
                $prices = $v5['price'] * $v5['count'];
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


        /*  $content .= '饭　　　　　 　10.0   10  100.0<BR>';
          $content .= '炒饭　　　　　 10.0   10  100.0<BR>';*/
        $content .= '--------------------------------<BR>';
        $content .= '份数：' . $order['count'] . '<BR>';
        $content .= '<B>附加金额：' . $order['sub_money'] . '</B><BR>';
        $content .= '<B>金额：' . $money . '</B><BR>';
        $content .= '<B>备注：' . $order['remark'] . '</B><BR>';
        $content .= '二维码叫号/确认<BR>';
        $content .= '（第一次扫码为叫号，第二次扫码为完成取餐）<BR>';;
        $content .= '<QR>' . $order['qrcode_url'] . '</QR>';
        //把二维码字符串用标签套上即可自动生成二维码

        $res = $this->printMsg($sn, $content, 1);
        var_dump($res);

    }

}