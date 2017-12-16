<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace common\components;

use Yii;
use yii\web\User;
use common\events\AnnualCreditEvent;
/**
 * Description of User
 *
 * @author Administrator
 * @datetime 2017-3-3 19:59:05
 */
class MyUser extends User
{
    const EVENT_AFTER_LOGIN = 'afterLogin';
    const EVENT_SEND_CREDITS = 'send_credits';
    const GETUSERADDRESS = 'getUserAddress';
    const GETUSERBANKCARDINFO = 'getUserBankCardInfo';

    /*
     * 获取存储在缓存中的用户基础信息
     * user_id,auser等
     */
    public function getBaseInfo($key = '', $cache = 'true')
    {
        if ($cache) {
            $session = Yii::$app->session;
            $user_base_info = $session->get('user_base_info');
            //开发阶段，先在查缓存时添加银行卡信息，
            $user_base_info = array_merge($user_base_info, $this->getUserBankCard($user_base_info));
        } else {
            $user = $this->getIdentity();
            $user_base_info = $user->attributes;
            $user_base_info = array_merge($user_base_info, $this->getUserBankCard($user_base_info));
            $session = Yii::$app->session;
            $session->set('user_base_info', $user_base_info);
        }
        if (!empty($key)) {
            if (is_string($key)) {
                $key = explode(',', $key);
            }
            foreach ($key as $val) {
                $specify[$val] = $user_base_info[$val];
            }
        } else {
            $specify = $user_base_info;
        }
        $borrow = new \common\models\Borrow();
        $specify['trustee_pay'] = $borrow->getBidByDebtor($user_base_info['user_id']);
        return $specify;
    }

    public function getUserCache()
    {
        $session = Yii::$app->session;
        $user_base_info = $session->get('user_base_info');
        return $user_base_info;
    }

    //继承yii\web\user类的登录触发器
    public function afterLogin($identity, $cookieBased, $duration)
    {
        $after = new \common\events\LoginEvent([
            'identity' => $identity,
            'cookieBased' => $cookieBased,
            'duration' => $duration,
        ]);
/*        $userInfo=$identity->attributes;
        //添加要刷新的总额的用户Key
        if($userInfo['card_status']>2)
        {
            $redis=Yii::$app->redisUser;
            $redis->ZADD('FreshloginUser',time(),$userInfo['user_id'].'_'.$userInfo['accountId'].'_'.$userInfo['card_status']);
        }*/

    }
    
//    public function afterLogout($identity) {
//        $after = new \common\events\LogoutEvent([
//            'identity' => $identity,
//        ]);
//        $this->on(self::EVENT_AFTER_LOGOUT, [$after, 'afterLogout']);
//        parent::afterLogout($identity);
//    }

    //获取用户银行卡
    protected function getUserBankCard($userInfo)
    {
        $cardBid = \Yii::$app->db->createCommand("select `card_bid`,`cardnaps` from {{%account_bank_app}} where user_id=" . $userInfo['user_id'] . " and status=1")->queryOne();
        return $cardBid ? $cardBid : array('card_bid' => 0, 'cardnaps' => 0);
    }

    //获取用户收货地址
    public function getUserAddress($cache = true)
    {
        $user = $this->getIdentity();
        $redis = Yii::$app->redis;
        $user_base_info = $user->attributes;
        $userId = $user_base_info['user_id'];
        $this->on(self::GETUSERADDRESS, ['\common\models\User', 'getAddressById'], ['user_id' => $userId, 'cache' => $cache]);
        $this->trigger(self::GETUSERADDRESS);
        $userAddressInfo = $redis->GET("user:$userId:Address");
        return $userAddressInfo ? json_decode($userAddressInfo, true) : '';
    }

    //获取用户银行卡信息
    public function getUserCardInfo($cache = true)
    {
        //$user=$this->getIdentity();
        $userInfo = $this->getBaseInfo('', false);
        $cardId = $userInfo['card_bid'];
        $userId = $userInfo['user_id'];
        if ($cardId) {
            $redis = Yii::$app->redis;
            $this->on(self::GETUSERADDRESS, ['\common\models\User', 'checkBankCardInfo'], ['cardId' => $cardId, 'userId' => $userId, 'cache' => $cache]);
            $this->trigger(self::GETUSERADDRESS);
            return $redis->GET("userId:$userId:cardId:$cardId");
        }else
        {
            return null;
        }
    }
    //判断用户是否借款人
    public function getUserIsBorrower()
    {
        $userInfo = $this->getBaseInfo('', false);
        $userId=$userInfo['user_id'];
        if ($userId == '309274') {
            return null;
        }
        $redis = Yii::$app->redis;
        $this->on(self::GETUSERADDRESS, ['\common\models\User', 'isBorrower'], [ 'userId' =>$userId]);
        $this->trigger(self::GETUSERADDRESS);
        return $redis->GET('isBorrower:userId:'.$userId);
    }
}