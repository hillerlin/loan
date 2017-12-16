<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/6
 * Time: 12:26
 */

namespace common\components;

use yii\base\Component;

class MyRedis extends Component
{
    protected static $redis;

    public function init()
    {
        $this->getInstance();
    }

    protected function getInstance(){
        if (!isset(self::$redis)) {
            self::$redis = new \Redis();
            self::$redis ;
        }
        return self::$redis;
    }

}