<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\job\UploadExcel;
use app\api\model\CanteenT;
use app\api\model\CompanyStaffT;
use app\api\model\ConsumptionRecordsV;
use app\api\model\ConsumptionStrategyT;
use app\api\model\DinnerT;
use app\api\model\OrderConsumptionV;
use app\api\model\OrderingV;
use app\api\model\OrderSubT;
use app\api\model\OrderT;
use app\api\model\PayT;
use app\api\model\RechargeCashT;
use app\api\model\RechargeV;
use app\api\model\Submitequity;
use app\api\model\UserBalanceV;
use app\api\service\AddressService;
use app\api\service\CanteenService;
use app\api\service\CompanyService;
use app\api\service\ConsumptionService;
use app\api\service\DepartmentService;
use app\api\service\ExcelService;
use app\api\service\NoticeService;
use app\api\service\OrderService;
use app\api\service\QrcodeService;
use app\api\service\SendSMSService;
use app\api\service\TakeoutService;
use app\api\service\WalletService;
use app\api\service\WeiXinService;
use app\lib\Date;
use app\lib\enum\CommonEnum;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use app\lib\printer\Printer;
use think\Db;
use think\db\Where;
use think\Exception;
use think\facade\Env;
use think\Queue;
use think\Request;
use zml\tp_tools\Aes;
use zml\tp_tools\Redis;
use function GuzzleHttp\Psr7\str;

class
Index extends BaseController
{
    public function index($sorts)
    {
        //(new TakeoutService())->refundOrder([11283]);

        if (empty($sorts)) {
            throw new ParameterException(['排队号，不能为空']);
        }
        $orders = OrderT::where('sort_code', 'in', $sorts)
            ->where('ordering_date', \date('Y-m-d'))
            ->select();
        $res = [];
        foreach ($orders as $k => $v) {
            $canteenID = 179;
            $orderID = $v['id'];
            $outsider = 2;
            $sortCode = $v['sort_code'];
            $printRes = (new Printer())->printOrderDetail($canteenID, $orderID, $outsider, $sortCode);
            if ($printRes) {
                array_push($res, $v['sort_code'] . "补打印成功");
            } else {
                array_push($res, $v['sort_code'] . "补打印失败");
            }

        }
        return json(new  SuccessMessageWithData(['data' => $res]));


        /* $file_name = dirname($_SERVER['SCRIPT_FILENAME']) . '/static/excel/upload/test.xlsx';
         $data = (new ExcelService())->importExcel($file_name);
         $fail = (new WalletService())->prefixUploadData(69, 1, $data);
         return json(new SuccessMessageWithData(['data' => $fail]));*/

        //(new Printer())->printOrderDetail(1,1388,2,'0001');
// (new  NoticeService())->noticeTask(26,155,'');
//(new OrderService())->refundWxOrder($id);
// $this->mailTask($name);
// $detail = '[{"d_id":122,"ordering":[{"ordering_date":"2020-01-21","count":1}]}]';

// (new OrderService())->orderingOnlineTest($detail, $name);
        /* $strategy = ConsumptionStrategyT::where('state', CommonEnum::STATE_IS_OK)
          ->select()->toArray();
         foreach ($strategy as $k => $v) {
             if(!empty($v['detail'])){
                 (new CanteenService())->prefixStrategyDetail($v['id'],$v['c_id'],$v['d_id'],$v['t_id'],$v['detail']);
             }
         }*/

        /* $money = UserBalanceV::userBalanceGroupByEffective(3, '15521323081');
         print_r($money);*/

        /* $user = CanteenT::whereIn('id', "19")
             ->field('name')->select()->toArray();
         $user_ids = array();
         foreach ($user as $k => $v) {
             array_push($user_ids, $v['name']);
         }
         echo implode('|', $user_ids);*/
    }

    public function test($code = "")
    {
        $set = "webSocketReceiveCode";
        $check = Redis::instance()->sIsMember($set, $code);
        var_dump($check);
        // return json(\app\api\service\Token::getCurrentTokenVar());

        /*        $data = [
                    '朝阳社区' => '6-24,402-418,472,701-705,816-845,857,964-971,988—989,2188-2210,2249-2266,2469-2481',
                    '鹞山社区' => '384-401,749,789-809,1811-1816,1818-1819,2004-2033,2096,2205-2206,2267-2270,2272-2274,2232-2238',
                    '金口岭社区' => '51,751,754-761,764-788,810-812,953-957,971,1000-1003,1007,1236-1240,1242-1243,1330,1594-1601,2271,2277-2305',
                    '人民社区' => '23-25,28-29,34,56,58-59,61,68-69,71,315-380,698,700,706-709,711,713-740,743-748,750,752,813-815,824,846,858,870-896,948-951,1061-1067,1228-1230,1316-1328,1331-1362,1572-1590,1605-1652,1689,1936-1982,1997,2215,2306,2314-2319,2326-2428,2435,2446-2491,2495-2499,2502-2509,2550-2557,2705-2710,2716-2717,2726,2736-2875,2878-2921,2933-2955,2959-2960',
                    '天井湖社区新' => '69-70,72,75-129,158-163,167-221,230,266-269,271,425-426,449,451-453,457-459,478,1014-1022,1698-1708,1710-1712,1716-1726,1805,2044-2045,2119,2229-2230,2715-2716,2718-2720,2729-2732,2734',
                    '官塘社区' => '2-5,250-253,260-264,461-465,474-477,558-560,596-601,645,681-695,943-947,1054,1107-1114,1193-1196,1503,1525,1528—1543,1545,1691—1697,2047-2058,2211-2214,2993-2997',
                    '西湖镇' => '419-424,431-438,440-441,523-531,628-644,1042-1049,1068-1083,1104-1106,1231-1235,1244,1272,1289,1296，1425,1449,1462-1466,1468-1471,1476-1477,1479,1482-1484,1487-1494,1560,1836,1837,1928-1935,2000,2103-2107,2129-2186,2207,2239-2240,2548,2659-2662,2956',
                    '映湖社区' => '29-33,429-430,680,1023-1029,2036-2043,2558-2559,2592-2657,2735',
                    '螺蛳山社区' => '928-931,938-941,1129-1145,1191-1192,2685-2689',
                    '东郊办' => '26-28,231-239,241-243,479-498,511-514,533,646,674-678,1051,1060,1086-1098,1146-1190,1258-1269,1292-1296,1472-1473,1475,1478,1480-1481,1561-1571,1653-1687,1727-1745,1752,1755-1768,1773-1776,1787-1795,1825-1827,1831,1838-1847,1850-1853,1856-1925,1927,1983-1996,2961-2965,2989—2990',
                    '友好社区' => '288,298-304,534-535,670-673,911,913-927,942,1055-1056,1777-1778,1807-1808,2198-2202,2500,2562-2578,2665-2674,2676-2678,2680-2682,2684,2690-2703,2711-2714,2923-2924,2931-2932,2966-2988',
                    '新城办事处' => '106,117,156—157,161,224—225,268,439,661,682—684,689,1438—1439,1688',
                    '阳光社区' => '53,64—65,533,536--615,1297--1315,1363--1416,2998',
                    '滨江社区' => '29-33,309-314,897-899,959-963,987,992-995,1714-1715,2660,2662',
                    '学苑社区' => '273-287,515-521,564-595,616-627,646,1052,1107-1109,1519-1524,1526-1527,1746-1747,1854-1855,2059-2094,2232-2238',
                    '露采社区' => '51-58,289-297,301-304,933,970-979,1115-1128,1204-1225,1227,1245-1257, 1798',
                    '幸福社区' => '48,66,245—249,270,499-509,1031-1041,1499—1501,1518,1546,1554-1559,1591-1593,1602,2217-2228,2311-2312,2321-2325,2327-2328,2438,2443-2445,2492-2494,2543-2547',
                    '五松社区' => '647—655,712,739—740,847,849,851—856,859—863,1690,2109—2116,2120—2128,2442,2512—2542,2580—2584,2663—2664',
                    '高新区' => '59-67,70,72-74,130-155,164-166,222-229,265,446-447,454-456,510,1271,1273-1286,1427-1448,1451-1456,1459,1809-1810',
                    '狮子山社区' => '1009-1011,1290-1291,1419-1424,1426,1450,1457-1458,1460-1461,1495-1498,1604,1820-1823,2001-2003,2108,2658',
                ];

                $all = [];
                for ($i = 1; $i <= 2998; $i++) {
                    array_push($all, $i);
                }

                foreach ($data as $k => $v) {
                    $v = explode(',', $v);
                    foreach ($v as $k2 => $v2) {
                        $num = explode('-', $v2);
                        if (count($num) == 1) {
                            if (in_array($num, $all)) {
                                unset($all[$num]);
                            }
                        } else {
                            for ($i = $num[0]; $i <= $num[1]; $i++) {
                                if (in_array($i, $all)) {
                                    unset($all[$i]);
                                }
                            }
                        }

                    }
                }
                echo implode(',', $all);*/

        // echo (new Printer())->printOutsiderOrderDetail(33051, '921533330', 'more');


        /* $data = (new ExcelService())->saveTestExcel();
         $fail = [];
         foreach ($data as $k => $v) {
             if ($k == 1 || empty($v[0])) {
                 continue;
             }
                if ($k >200 && $k <= 300) {
                //if ( $k <= 100) {
                    $orderNum = $v[1];
                    $phone = $v[20];
                    $staff = CompanyStaffT::where('company_id', $company_id)
                        ->where('state',CommonEnum::STATE_IS_OK)
                        ->where('phone', $phone)
                        ->find();
                    if (!$staff) {
                        array_push($fail, [
                            'ordernumber' => $orderNum,
                            'phone' => $phone
                        ]);
                    }
                    PayT::update([
                        'phone' => $phone,
                        'username' => $staff->username,
                        'staff_id' => $staff->id
                    ], ['order_num' => $orderNum, 'company_id' => $company_id]);
                }

         }

         return json($fail);*/

        /*
                try {
                    Db::startTrans();
                    $data = (new ExcelService())->saveTestExcel();
                    $dataList = [];
                    foreach ($data as $k => $v) {
                        if ($k == 1 || empty($v[0])) {
                            continue;
                        }
                        if ($k < 15) {
                            array_push($dataList, [
                                'id' => $v[0],
                                'money' => $v[11],
                                'sub_money' => $v[12],
                            ]);
                        } else {
                            array_push($dataList, [
                                'id' => $v[0],
                                'no_meal_money' => $v[11],
                                'no_meal_sub_money' => $v[12],
                            ]);
                        }

                    }

                    $res = (new  OrderT())->saveAll($dataList);
                    if (!$res) {
                        throw  new SaveException();
                    }
                     Db::commit();
                    return json(new SuccessMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    throw  $e;
                }*/
    }

}