<?php


namespace app\lib;


class Date
{

    /**
     *
     * 获取指定年月的开始和结束时间戳
     *
     * @param int $y 年份
     * @param int $m 月份
     * @return array(开始时间,结束时间)
     */
    public static function mFristAndLast($y = 0, $m = 0)
    {
        $y = $y ? $y : date('Y');
        $m = $m ? $m : date('m');
        $d = date('t', strtotime($y . '-' . $m));
        return array(
            "fist" => date('Y-m-d', strtotime($y . '-' . $m)),
            "last" => date('Y-m-d', mktime(23, 59, 59, $m, $d, $y)));
    }


    public static function mFristAndLast2($data)
    {
        $y = date('Y', strtotime($data));
        $m = date('m', strtotime($data));
        $d = date('t', strtotime($data));
        return array(
            "fist" => date('Y-m-d', strtotime($y . '-' . $m)),
            "last" => date('Y-m-d', mktime(23, 59, 59, $m, $d, $y)));
    }

    /**
     * 日期加上指定天数
     * @param $count
     * @param $time_old
     * @return false|string
     */
    public static function addDay($count, $time_old)
    {
        $time_new = date('Y-m-d', strtotime('+' . $count . ' day',
            strtotime($time_old)));
        return $time_new;

    }

    /**
     * 日期减去指定天数
     * @param $count
     * @param $time_old
     * @return false|string
     */
    public static function reduceDay($count, $time_old)
    {
        $time_new = date('Y-m-d', strtotime('-' . $count . ' day',
            strtotime($time_old)));
        return $time_new;

    }

    /**
     * 求两个日期之间相差的天数
     * (针对1970年1月1日之后，求之前可以采用泰勒公式)
     * @param string $day1
     * @param string $day2
     * @return number
     */
    public static function diffBetweenTwoDays($day1, $day2)
    {
        $second1 = strtotime($day1);
        $second2 = strtotime($day2);

        if ($second1 < $second2) {
            $tmp = $second2;
            $second2 = $second1;
            $second1 = $tmp;
        }
        return ($second1 - $second2) / 86400;
    }
}