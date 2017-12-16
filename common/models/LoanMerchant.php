<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace common\models;

use Yii;
use yii\db\Expression;

/**
 * LoanMerchant model
 *
 * @property integer $merchant_id
 * @property string $name
 * @property string $key
 * @property integer $user_id
 * @property integer $enter_user_id
 * @property string $merchant_credit
 * @property string $merchant_used_credit
 * @property string $borrow_rate
 * @property string $overdue_rate
 * @property integer $status
 * @property integer $add_time
 * @property integer $update_time
 * @property integer $repay_opt
 * @property integer $repay_style
 * @property integer $unit
 */
class LoanMerchant extends DmActiveRecord{

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return '{{%loan_merchant}}';
    }

    /**
     * 校验企业授信额度
     * @param $credit
     * @param $merchantId
     * @return bool
     */
    public static function checkMerchantCredit($credit, $merchantId)
    {
        $info = self::selectOne(['merchant_id' => $merchantId], 'merchant_credit, merchant_used_credit');
        if ($info['merchant_credit'] < ($info['merchant_used_credit'] + $credit)) {
            return false;
        }
        return true;
    }

    /**
     * 企业已使用的额度增加
     * @param $params
     * @return bool
     */
    public function addUsedCredit($params)
    {
        $update = [
            'merchant_used_credit' =>new Expression('merchant_used_credit + ' . $params['credit']),
        ];
        if (!self::updateInfo(['merchant_id' => $params['merchant_id']], $update)) {
            return false;
        }
        return true;
    }
    
    public static function addOneLog($params){
        
        $data = [
            'version' => $params['version'], 
            'service' => $params['service'], 
            'borrower_id' => $params['borrower_id'], 
            'amount' => $params['amount'], 
            'duration' => $params['duration'], 
            'unit' => $params['unit'], 
            'repay_style' => $params['repay_style'], 
            'addtime' => json_encode($params)
            ];
        return Yii::$app->db->createCommand()->insert(self::tableName(), $data)->execute();
    }
    
    public static function findIdentity($id) {
        return static::findOne(['merchant_id' => $id]);
    }

    /**
     * 根据商户ID返回商户信息
     * @param $merchantId
     * @param string $key
     * @return array
     */
    public static function getMerchantInfoById($merchantId, $key='')
    {
        $redis = Yii::$app->redis;
        $redisKey = 'merchantInfo:' . $merchantId;
        $info = $redis->GET($redisKey);
        if ($info) {
            $info = json_decode($info, true);
        } else {
            $merchantInfo = self::findIdentity($merchantId);
            if ($merchantInfo) {
                $info = $merchantInfo->toArray();
                $redis->SET($redisKey, json_encode($info));
            }else{
                return null;
            }
        }
        return ($key == '') ? $info : (isset($info[$key]) ? $info[$key] : null);
    }
}
