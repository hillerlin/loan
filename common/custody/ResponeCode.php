<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace common\custody;

use Yii;
/**
 * Description of ResponeCode
 *
 * @author Administrator
 * @datetime 2017-3-14 14:56:34
 */
class ResponeCode {
    //put your code here
    const SUCCESS = '00000000';         //成功
    
    /**
     * 返回对应的错误信息
     * @param type $code
     * @return type
     */
    public static function getMsg($code) {
        $redis = Yii::$app->redis;
        $rkey = 'custody_code:1';
        $exist_custody_code = $redis->EXISTS($rkey);
        if ($exist_custody_code != true) {
            $custody_code = require(__DIR__ . '/../config/custody_config/custody_code.php');
            $args[] = $rkey;
            foreach ($custody_code as $key => $value) {
                $args[] = $key;
                $args[] = $value;
            }
            $redis->executeCommand('HMSET', $args);
        }
        $msg = $redis->HGET($rkey, $code);
        return empty($msg) ? '未知错误，错误编码：' . $code : $msg;
    }
}
