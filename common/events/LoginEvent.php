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
class LoginEvent extends UserEvent {

    private $_user;

    //用户登录后触发的事件
    public function afterLogin() {
        $this->_user = $this->identity->attributes;
        //登录更新缓存
        $this->cacheUserBaseInfo();

        $lastip = Yii::$app->getRequest()->getUserIP();
        $lasttime = time();
        //发站内信
        $user_id = Yii::$app->user->id;
        $_msg['user_id'] = $user_id;
        $_msg['m_title'] = '信息提示：账户登录信息';
        $_msg['m_content'] = '上次登录ip：' . $lastip . '；上次登录时间：' . date('Y-m-d H:i:s', $lasttime);
        $_msg['send_user_id'] = 0;
        $_msg['addtime'] = time();
        $mssage = new \common\models\Message();
        $mssage->sendOne($_msg);
        //更新登录信息
        $update_user['lastip'] = $lastip;
        $update_user['lasttime'] = $lasttime;
        $update_user['login_source'] = 0;
        User::updateInfo('user_id=' . $user_id, $update_user);
        $uc_api = new UcApi();
        $uc_api->localLogin($this->_user['user_id'], $this->_user['real_phone'], $this->_user['auser']);
//        $this->oldReback();
    }

    //设置缓存
    protected function cacheUserBaseInfo() {
        $session = Yii::$app->session;
        $session->set('user_base_info', $this->_user);
        
        $custody_money = \common\models\UserAccount::custodyBalanceQueryByAcIdRe($this->_user['user_id'], $this->_user['accountId']);
       
    }

    //7月老用户回归福利
    public function oldReback() {
        $current_time = time();
        $start_time = strtotime('20170713 10:00:00');
        $end_time = strtotime('20170801');
        //从2017071309开始，20170726结束
        if ($current_time < $start_time || $current_time > $end_time) {
            return false;
        }
        $active_time = strtotime('20170501');
        if ($this->_user['addtime'] >= $active_time) {
            return false; 
        }
        $params[':addtime'] = $active_time;
        $params[':user_id'] = $this->_user['user_id'];
        $tender_count = \common\models\BorrowTender::find()->where('user_id=:user_id and addtime>=:addtime')->params($params)->count();
        if ($tender_count > 0) {
            return false;
        }
        //7月老用户回归福利
        $send_count = \common\models\Ndm_coupon::activitySendCount($this->_user['user_id'], 10);
        if ($send_count > 0) {
            return false;
        }
        \common\models\Ndm_coupon::sendSpecify($this->_user['user_id'], 'old_reback');
    }
}
