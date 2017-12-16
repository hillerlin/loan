<?php

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\helpers;

/**
 * StringHelper
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Alex Makarov <sam@rmcreative.ru>
 * @since 2.0
 */
class StringHelper extends BaseStringHelper {

    /**
     * 格式化字符串
     * @param string $auser 字符串
     * @return string
     */
    public static function formatUser($auser) {
        if (is_numeric($auser) && mb_strlen($auser) > 6) {
            $formatUser = self::numbersubstr($auser, 3, 1, '**');
        } elseif (mb_strlen($auser) > 2) {
            $formatUser = self::numbersubstr($auser, 2, 1, '**');
        } else {
            $formatUser = self::chinesesubstr($auser);
        }
        return $formatUser;
    }

    public static function numbersubstr($str, $end = 4, $start = 3, $chars = '****') {
        $tmpstr = '';
        if ($str) {
            if (is_numeric($str)) {
                $tmpstr .= mb_substr($str, 0, $start) . $chars . mb_substr($str, -$end);
            } else {
                if (ord(substr($str, 0, 1)) > 0xa0 && ord(iconv_substr($str, iconv_strlen($str, 'UTF-8') - 1, 1, 'UTF-8')) > 0xa0) { // 如果字符串中首个字节的ASCII序数值大于0xa0,则表示汉字
                    $tmpstr .= iconv_substr($str, 0, 1, 'UTF-8') . $chars; //.iconv_substr ( $str, iconv_strlen($str,'UTF-8')-1, 1 ,'UTF-8'); // 每次取出三位字符赋给变量$tmpstr，即等于一个汉字
                } else if (ord(substr($str, 0, 1)) > 0xa0 && !(ord(iconv_substr($str, iconv_strlen($str, 'UTF-8') - 1, 1, 'UTF-8')) > 0xa0)) {
                    $tmpstr .= iconv_substr($str, 0, 1, 'UTF-8') . $chars . substr($str, -1);
                } else if (ord(iconv_substr($str, iconv_strlen($str, 'UTF-8') - 1, 1, 'UTF-8')) > 0xa0) {
                    $tmpstr .= iconv_substr($str, 0, 1, 'UTF-8') . $chars . iconv_substr($str, iconv_strlen($str, 'UTF-8') - 1, 1, 'UTF-8'); // 每次取出三位字符赋给变量$tmpstr，即等于一个汉字
                } else {
                    $tmpstr .= substr($str, 0, 1) . $chars . substr($str, -1); // 如果不是汉字，则每次取出一位字符赋给变量$tmpstr
                }
            }
        }
        return $tmpstr;
    }

    public static function chinesesubstr($str, $chars = '**') {
        //mb_detect_encoding($str, array("ASCII","UTF-8","GB2312","GBK","BIG5"));
        // iconv_strlen($str, 'UTF-8');
        $tmpstr = '';
        if ($str) {
            if (ord(substr($str, 0, 1)) > 0xa0 && ord(iconv_substr($str, iconv_strlen($str, 'UTF-8') - 1, 1, 'UTF-8')) > 0xa0) { // 如果字符串中首个字节的ASCII序数值大于0xa0,则表示汉字
                $tmpstr .= iconv_substr($str, 0, 1, 'UTF-8') . $chars; //.iconv_substr ( $str, iconv_strlen($str,'UTF-8')-1, 1 ,'UTF-8'); // 每次取出三位字符赋给变量$tmpstr，即等于一个汉字
            } else if (ord(substr($str, 0, 1)) > 0xa0 && !(ord(iconv_substr($str, iconv_strlen($str, 'UTF-8') - 1, 1, 'UTF-8')) > 0xa0)) {
                $tmpstr .= iconv_substr($str, 0, 1, 'UTF-8') . $chars . substr($str, -1);
            } else if (ord(iconv_substr($str, iconv_strlen($str, 'UTF-8') - 1, 1, 'UTF-8')) > 0xa0) {
                $tmpstr .= iconv_substr($str, 0, 1, 'UTF-8') . $chars . iconv_substr($str, iconv_strlen($str, 'UTF-8') - 1, 1, 'UTF-8'); // 每次取出三位字符赋给变量$tmpstr，即等于一个汉字
            } else {
                $tmpstr .= substr($str, 0, 1) . $chars . substr($str, -1); // 如果不是汉字，则每次取出一位字符赋给变量$tmpstr
            }
        }
        return $tmpstr;
    }

    public static function hideStar($str, $type = '') { //用户名、邮箱、手机账号中间字符串以*隐藏
        if ($type == 'real_name') {
            $rs = substr($str, 0, 3) . "**";
        } else if (strpos($str, '@')) {
            $email_array = explode("@", $str);
            $prevfix = ( strlen($email_array[0]) < 4 ) ? "" : substr($str, 0, 3); //邮箱前缀
            $count = 0;
            $str = preg_replace('/([\d\w+_-]{0,100})@/', '***@', $str, - 1, $count);
            $rs = $prevfix . $str;
        } else {
            $pattern = '/(1[3458]{1}[0-9])[0-9]{4}([0-9]{4})/i';
            if (preg_match($pattern, $str)) {
                $rs = preg_replace($pattern, '$1****$2', $str); // substr_replace($name,'****',3,4);
            } else {
                $rs = substr($str, 0, 3) . "***" . substr($str, - 1);
            }
        }
        return $rs;
    }

    /**
     * 生成随机数
     * @param int $strlen
     * @return string
     */
    public static function randStr($strlen = 8) {
        $letter = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $str = '';
        for ($i = 0; $i < $strlen; $i++) {
            $str .= $letter{mt_rand(0, 61)};
        }
        return $str;
    }

    /**
     * 返回不转义中文和斜杠的 JSON 格式数据
     * @param array $array
     * @return string
     */
    public static function jsonUnicode($array)
    {
        return json_encode($array,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    }

    /**
     * 下划线转驼峰写法
     * @param string $str
     * @return string
     */
    static public function underlineToHump($str)
    {
        $str_hump = '';

        if($str){
            $str_array = explode('_',$str);
            foreach($str_array as $value)
            {
                $str_hump .= ucfirst($value);
            }
        }

        return $str_hump?$str_hump:$str;
    }
}
