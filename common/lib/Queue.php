<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace common\lib;

use Yii;

/**
 * Description of Queue
 *
 * @author Administrator
 * @datetime 2017-6-16 18:10:11
 */
class Queue {

    public static function push($queue, $params) {
        $queue = 'queue:' . $queue;
        $value = json_encode($params);
        Yii::$app->redis->RPUSH($queue, $value);
    }

    public static function pop($queue) {
        $queue = 'queue:' . $queue;
        return Yii::$app->redis->LPOP($queue);
    }

    public static function len($queue) {
        $queue = 'queue:' . $queue;
        return Yii::$app->redis->LLEN($queue);
    }

}
