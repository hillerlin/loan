<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace common\events;

use Yii;
use yii\web\UserEvent;
use common\models\User;
use common\components\Client;
use common\lib\UcApi;

/**
 * Description of LoginEvent
 *
 * @author Administrator
 * @datetime 2017-2-23 17:17:53
 */
class LogoutEvent extends UserEvent {

    private $_user;

    //用户登录后触发的事件
    public function afterLogout() {
        $this->_user = $this->identity->attributes;
        $this->clearRedis();
    }

    //清除缓存
    protected function clearRedis() {
        $redisUser = Yii::$app->redisUser;
        $key = \common\lib\RedisKey::USER_INFO . $this->_user['user_id'];
        $redisUser->DEL($key);
    }

}
