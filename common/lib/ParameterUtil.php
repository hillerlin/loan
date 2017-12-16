<?php
/**
 * Created by PhpStorm.
 * User: Neo
 * Date: 2017/11/17
 * Time: 12:05
 */

namespace common\lib;


class ParameterUtil
{

    /**
     * 获取签名
     * @param $params
     * @param $key
     * @return string
     */
    public static function getSignFromData($params, $key)
    {
        ksort($params);
        $paramsStr = '';
        foreach($params as $k => $v){
            $v = is_array($v) ? json_encode($v) : $v;
            $paramsStr .= $k.'='.$v.'&';
        }
        $paramsStr = substr($paramsStr,0,-1);
        $paramsStr .= $key;
        return md5($paramsStr);
    }

    /**
     * 根据身份证号，自动返回性别
     * @param $idCard
     * @return string
     */
    public static function getGenderFromIdCard($idCard){ //根据身份证号，自动返回性别
        $sexInt = (int)substr($idCard, 16, 1);
        return $sexInt % 2 === 0 ? '女' : '男';
    }
    /**
     *  根据身份证号码计算年龄
     *  @param string $idCard    身份证号码
     *  @return int $age
     */
    public static  function getAgeFromIdCard($idCard){
        #  获得出生年月日的时间戳
        $date = strtotime(substr($idCard,6,8));
        #  获得今日的时间戳
        $today = strtotime('today');
        #  得到两个日期相差的大体年数
        $diff = floor(($today-$date)/86400/365);
        #  strtotime加上这个年数后得到那日的时间戳后与今日的时间戳相比
        $age = strtotime(substr($idCard,6,8).' +'.$diff.'years')>$today?($diff+1):$diff;
        return $age;
    }

    /**
     *  根据身份证号码获取出身地址
     *  @param string $idCard    身份证号码
     *  @return string $address
     */
    public static function getAddressFromIdCard($idCard){
        if(empty($idCard)) return null;
        require __DIR__.'/idCardAddress.php';
        # 截取前六位数(获取基体到县区的地址)
        $key = substr($idCard,0,6);
        if(!empty($address[$key])) return $address[$key];

        # 截取前两位数(没有基体到县区的地址就获取省份)
        $key = substr($idCard,0,2);
        if(!empty($address[$key])) return $address[$key];

        # 都没有
        return '未知地址';
    }
}