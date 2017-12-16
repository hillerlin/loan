<?php
/**
 * Created by PhpStorm.
 * User: billy
 * Date: 2016/6/3
 */

namespace common\lib;
use yii\helpers\StringHelper;


/**
 * 类型验证操作类
 *
 * Class VerifyUtil
 * @package Utility
 */
class VerifyUtil
{
    /**
     * 检测参数 $value 是否符合参数 $verify_type 指定类型
     *
     * @param $value        : 待检测参数
     * @param $verify_type  : 指定的检测类型
     * @return bool|null
     */
    static public function check($value,$verify_type)
    {
        $method_name = 'is'.StringHelper::underlineToHump($verify_type);

        if(!method_exists(self::class,$method_name))   return NULL;

        return self::$method_name($value);
    }

    /**
     * 检测变量是否为数字或数字字符串
     *
     * @param $value    : 待检测变量
     * @return bool
     */
    static public function isNumber($value)
    {
        return is_numeric($value)?true:false;
    }

    /**
     * 检测变量是否为整数
     *
     * @param $value    : 待检测变量
     * @return bool
     */
    static public function isInt($value)
    {
        return is_int($value)?true:false;
    }

    /**
     * 检测变量是否为数组
     *
     * @param $value    : 待检测变量
     * @return bool
     */
    static public function isArray($value)
    {
        return is_array($value)?true:false;
    }

    /**
     * 检测变量是否为 JSON 格式字符串
     *
     * @param $value    : 待检测变量
     * @return bool
     */
    static public function isJson($value)
    {
        $array = ($value != '')?json_decode($value,true):'';

        return is_array($array)?true:false;
    }

    /**
     * 检测变量是否为手机号码格式
     *
     * @param $value    : 待检测变量
     * @return bool
     */
    static public function isMobile($value)
    {
        if(self::isNumber($value))
            return preg_match("/^1[3,4,5,7,8]{1}[0-9]{9}$/",$value)?true:false;

        return false;
    }

    /**
     * 检测变量是否为 Email 格式
     *
     * @param $value    : 待检测变量
     * @return bool
     */
    static public function isEmail($value)
    {
        return preg_match("/^[_.0-9a-zA-Z-]+@([0-9A-Za-z-]+.)+[a-zA-Z]{2,3}$/",$value)?true:false;
    }

    /**
     * 检测变量是否为标准 Date 格式
     *
     * @param $value    : 待检测变量
     * @return bool
     */
    static public function isDate($value)
    {
        return (strlen($value) != 10 || strtotime($value) === false)?false:true;
    }

    /**
     * 检测变量是否为标准 Datetime 格式
     *
     * @param $value    : 待检测变量
     * @return bool
     */
    static public function isDatetime($value)
    {
        return (strlen($value) != 19 || strtotime($value) === false)?false:true;
    }

    public static function isIdNo($value)
    {
        return \common\lib\IdValidator::validationFilterIdCard($value);
    }

    public static function getEnumValue($value, $verify)
    {
        $arr = [
            'srvTxCode' => ['accountOpenPlus'=>'accountOpenPlus', 'cardBindPlus' => 'cardBindPlus', 'mobileModifyPlus' => 'mobileModifyPlus', 'passwordResetPlus' => 'passwordResetPlus'],
            'gender' => ['1'=>'男', '2' => '女'],
            'degree' => ['1'=>'博士', '2' => '硕士', '3' => '本科', '4' => '专科', '5' => '高中', '6' => '初中', '7' => '小学及以下'],
            'marriage' => ['1'=>'已婚', '2' => '未婚', '3' => '离异'],
            'property' => ['1'=>'有房', '2' => '无房'],
            'unit' => ['1' => '1', '2' => '2'],
            'repayStyle' => ['1' => '1', '2' => '2', '3' => '3', '4' => '4',],
        ];
        return isset($arr[$verify[0]][$value]) ? $arr[$verify[0]][$value] : false;
    }

}