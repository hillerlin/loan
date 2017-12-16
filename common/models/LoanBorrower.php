<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace common\models;

use common\lib\Helps;
use yii\db\Expression;

/**
 * LoanBorrower model
 *
 * @property integer $borrower_id
 * @property integer $user_id
 * @property integer $merchant_id
 * @property integer $plb_id
 * @property string $credit
 * @property string $used_credit
 * @property string $dm_credit
 * @property string $dm_used_credit
 */
class LoanBorrower extends DmActiveRecord {

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return '{{%loan_borrower}}';
    }

    /**
     * 添加借款人信息
     * @param $data
     * @return bool
     */
    public function addBorrower($data) {
        $this->setAttributes($data, false);
        if (!$this->save(false)) {
            return false;
        }
        return true;
    }

    /**
     * 校验用户授信额度
     * @param $credit
     * @param $userId
     * @param $dmCredit
     * @return bool
     */
    public static function checkUserCredit($credit, $userId, $dmCredit) {
        $info = self::selectOne(['user_id' => $userId], 'sum(credit) total_credit');
        if ($dmCredit < ($info['total_credit'] + $credit)) {
            return false;
        }
        return true;
    }

    /**
     * 更新用户额度
     * @param $borrower_id
     * @param $user_id
     * @param $merchant_id
     * @param $use_credit
     * @return bool
     */
    public function updateCredit($borrower_id, $user_id, $merchant_id, $use_credit) {
        //更新额度和大麦额度
        $update = [
            'used_credit' => new Expression('used_credit + ' . $use_credit),
        ];
        if (!self::updateInfo(['borrower_id' => $borrower_id], $update)) {
            return false;
        }
        //更新大麦已使用额度
        $updateUser = [
            'dm_used_credit' => new Expression('dm_used_credit + ' . $use_credit),
        ];
        if (!self::updateInfo(['user_id' => $user_id], $updateUser)) {
            return false;
        }
        //更新平台已使用额度
        $updateMerchant = [
            'merchant_used_credit' => new Expression('merchant_used_credit + ' . $use_credit),
        ];
        if (!LoanMerchant::updateInfo(['merchant_id' => $merchant_id], $updateMerchant)) {
            return false;
        }
        return true;
    }

    /**
     * 添加已注册过的借款人信息
     * @param $params
     * @return bool
     */
    public function addExistBorrower($params) {
        $info = self::selectOne(['user_id' => $params['user_id']], 'dm_credit, dm_used_credit');
        if ($info) {
            $params['dm_credit'] = $info['dm_credit'];
            $params['dm_used_credit'] = $info['dm_used_credit'];
        }
        if (!$this->addBorrower($params)) {
            return false;
        }
        return true;
    }

    public static function findIdentity($id) {
        return static::findOne(['borrower_id' => $id]);
    }

    /**
     * find borrower info by borrower_id and merchant_id
     * @param $borrower_id
     * @param $merchant_id
     * @return static
     */
    public static function findByIdAndMerchantId($borrower_id, $merchant_id) {
        return self::findOne(['borrower_id' => $borrower_id, 'merchant_id' => $merchant_id]);
    }

    /**
     * find borrower info by user_id and merchant_id
     * @param $user_id
     * @param $merchant_id
     * @return static
     */
    public function findByUidAndMerchantId($user_id, $merchant_id) {
        return self::findOne(['user_id' => $user_id, 'merchant_id' => $merchant_id]);
    }

    public static function getBorrowerInfoFormUid($userId, $key = '')
    {
        $info = self::findOne(['user_id' => $userId]);
        $info = $info ? $info->toArray() : [];
        return ($key == '') ? $info : (isset($info[$key]) ? $info[$key] : null);
    }

    /**
     * 查找商家名下所有的借款人信息
     * @param $merchantId
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public static function getBorrowerListByMerchantId($merchantId, $page = 1, $pageSize = 30) {
        $condition = 'merchant_id = :merchant_id';
        $params = [':merchant_id' => $merchantId];
        $limit = self::pageToLimit($page, $pageSize);
        $total = self::find()
                        ->where($condition)->params($params)->count();
        $list = self::find()
                        ->select("borrower_id,u.user_id,u.accountId,u.addtime,u.real_name,u.real_card,real_phone,credit,used_credit,card_bid")
                        ->alias('lb')
                        ->leftJoin('xx_user as u', 'u.user_id=lb.user_id')
                        ->leftJoin('xx_account_bank_app as aba', 'aba.user_id=u.user_id')
                        ->where($condition)->params($params)
                        ->offset($limit)
                        ->limit($pageSize)
                        ->orderBy('borrower_id desc')
                        ->asArray()
                        ->all();
        return ['list' => $list, 'total' => $total];
    }

    /**
     * find borrower info by borrower_id and merchant_id
     * @param $borrower_id
     * @param $merchant_id
     * @return static
     */
    public static function findBorrowerByIdAndMerchantId($borrower_id, $merchant_id, $column = '*') {
        $borrower = self::find()->select($column)->where(['borrower_id' => $borrower_id, 'merchant_id' => $merchant_id])->asArray()->one();
        $user = new User();
        $card = $user->getByPk('real_name,real_phone,real_card', $borrower['user_id']);
        return array_merge($borrower, $card);
    }
}
