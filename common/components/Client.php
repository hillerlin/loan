<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace common\components;

/**
 * Description of Client
 *
 * @author Administrator
 * @datetime 2017-4-21 18:31:50
 */
class Client {
    //put your code here
    const PC = '1';
    const IOS = '2';
    const ANDROID = '3';
    const H5 = '4';
    private static $client;
    
    public static function getClient() {
        if (is_null(self::$client)) {
            
        }
        return self::$index;
    }
    
    public function requestFrom() {
        $requset = '';
    }
}
