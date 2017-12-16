<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace common\models;
use common\lib\Helps;

/**
 * User model
 *
 * @property integer $cid
 * @property string $username
 * @property string $card_bid
 * @property string $bank_name
 * @property string $province
 * @property string $branch
 * @property integer $user_id
 * @property integer $addtime
 * @property integer $status
 * @property integer $order_id
 * @property integer $bank_city
 * @property integer $unbind_time
 * @property string $cardnaps
 */
class AccountBank extends DmActiveRecord{
    
    const STAUS_ACTIVE = 1;
    const STAUS_UNBIND = 0;
    const CARD_STATUS =1;

    public $errMsg = '';
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%account_bank_app}}';
    }
    
    public static function getBankListByUid($user_id) {

        $sql="select * from xx_account_bank_app where `user_id`=$user_id";
        $list= Helps::createNativeSql($sql)->queryOne();
        return $list;
    }
    
    //绑定默认银行卡
    public function setDefaultBankCard($params) {
        //先解绑其他卡
        $this->updateAll(['status' => self::STAUS_UNBIND], 'user_id=' . $params['user_id']);
        //绑定卡
        $this->username = $params['real_name'];
        $this->card_bid = $params['bank_card'];
        $this->bank_name = $this->getBankName($params['bank_card']);
        $this->user_id = $params['user_id'];
        $this->status = self::CARD_STATUS;
        $this->addtime = time();
        return $this->save();
    }

    
    //查找用户的银行卡
    public static function findActiveByUid($user_id,$select='username,card_bid,bank_name, cardnaps') {
        $result = self::find()
                ->select($select)
                ->where('user_id = :user_id AND status = '. self::STAUS_ACTIVE, [':user_id' => $user_id])
                ->asArray()
                ->one();
        return $result;
    }
/*    //解绑
    public static function cardUnbind($userId)
    {
       // $this->
    }*/

    public static function getUserBindHistory( $userId ){
        $result = self::find()
                ->select('username,card_bid, addtime, unbind_time, bank_name')
                ->where('user_id = :user_id AND status = ' . self::STAUS_UNBIND, [':user_id' => $userId])
                ->orderBy('unbind_time DESC')
                ->limit(10)
                ->asArray()
                ->all();
        return $result;
    }


    public function getBankName($cardId)
    {
        $getName=Helps::checkCardInfo($cardId);
        if ($getName) {
            return $getName[0];
        } else {
            return '中国银联';
        }
    }

    public static function changeCard($userId, $update, $insert)
    {
        $up = \Yii::$app->db->createCommand()->update(self::tableName(), $update, ['status'=>self::STAUS_ACTIVE, 'user_id'=>$userId])->execute();
        $ins = \Yii::$app->db->createCommand()->insert(self::tableName(), $insert)->execute();
        if (($up!==false) && $ins) {
            return true;
        }else{
            return false;
        }
    }

    public function checkCancelOrNot($userId)
    {
        $userMoney = UserAccount::getAllMoney($userId);
        //提现前先做校验---解绑卡的规则：无论存在投资未回款、借款未还清、账户可用余额不为零，均不能解绑卡
        //账户待收
        $account_wait_income = BorrowCollection::getWaitAccount($userId);
        if($account_wait_income['wait_principal']!=0.00 || $account_wait_income['wait_interest']!=0.00 || floatval($userMoney['use_money_custody'])!=0.00 || floatval($userMoney['freeze_money_bid'])!=0.00)
        {
            $this->errMsg = '您卡上还有余额或未结清的款项，不能更换银行卡';
            return false;
        }
        return true;
    }


    /**
     * 解绑银行卡
     * @param $userId
     * @return bool
     */
    public function cancelBankCard($userId)
    {
        $cancelOrNot = $this->checkCancelOrNot($userId);
        if (!$cancelOrNot) {
            return $cancelOrNot;
        }
        $active = ['1', '2', '3', '4', '5'];
        $sendData = \common\lib\Helps::joinMappingPlus($active, $userId, 'cardUnbind');
        $requestApi = new \common\lib\custody\CustodyApi;
        $request = $requestApi->submitApi($sendData);
        if ($request) {
            $update = UserAccount::updateUserInfo($userId); //更改数据库的绑卡状态值\
            if ($update === false) {
                $this->errMsg = '抱歉,数据库繁忙，解绑失败！';
                return false;
            }
        } else {
            $this->errMsg = $requestApi->getError() ? :'抱歉,网络繁忙，请稍后在试！';
            return false;
        }
        return true;
    }

    /**
     * 更换银行卡
     * @param $userInfo
     * @param $smsCode
     * @param $mobileNew
     * @param $bankCard
     * @return bool
     */
    public function changeBankCard($userInfo, $smsCode, $mobileNew, $bankCard)
    {
        $userId = $userInfo['user_id'];
        $_validate = new CommonRule(['scenario' => 'cardbindplus']);
        if (!$_validate->load(['smsCode' => $smsCode, 'mobileNew' => $mobileNew, 'bankCard' => $bankCard], '') || !$_validate->returnCheck()) {
            $this->errMsg = $_validate->joinErrorMsg();
            return false;
        }
        $active = ['accountId', 'real_name', 'real_card', 'smsCode', 'lastSrvAuthCode', 'bankCard', 'mobileNew'];
        $sendData = \common\lib\Helps::joinMappingPlus($active, $userId, 'cardBindPlus');
        $sendData['mobile']=$mobileNew;
        $sendData['smsCode']=$smsCode;
        $requestApi = new \common\lib\custody\CustodyApi;
        $request = $requestApi->submitApi($sendData);
        if ($request) {
            //更新user表和userAccount的日志
            $params['bank_card'] = $bankCard;
            $params['real_name'] = $userInfo['real_name'];
            $params['user_id'] = $userId;
            $params['accountId'] = $request['accountId'];
            //将银行卡信息保存在系统中
            if ($this->setDefaultBankCard($params) !== false) {
                return true;
            } else {
                $this->errMsg = '数据库繁忙';
                return false;
            }
        } else {
            $this->errMsg = $requestApi->getError() ?: '抱歉,网络繁忙，请稍后在试！';
            return false;
        }
    }

    //获取用户银行卡
    public static function getUserBankCard($user_id)
    {
        $cardBid = \Yii::$app->db->createCommand("select `card_bid`,`cardnaps` from {{%account_bank_app}} where user_id=" . $user_id . " and status=1")->queryOne();
        return $cardBid ? $cardBid : array('card_bid' => 0, 'cardnaps' => 0);
    }

}
