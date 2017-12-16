<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace common\models;

use Yii;

/**
 * PlBorrow model
 *
 * @property integer $id
 * @property integer $uimg_id
 * @property integer $user_id
 * @property string $account
 * @property integer $type
 * @property integer $limit_time
 * @property string $apr
 * @property string $interest
 * @property string $contract_no
 * @property integer $starttime
 * @property integer $endtime
 * @property string $income
 * @property string $sex
 * @property string $degree
 * @property string $marriage
 * @property string $property
 * @property string $purpose
 * @property string $storeaddress
 * @property string $car
 * @property string $emergent
 * @property integer $addtime
 * @property string $nowaddress
 * @property integer $cpc_soure
 * @property integer $age
 * @property string $company_industry
 * @property string $company_nature
 * @property string $company_position
 */
class PlBorrow extends DmActiveRecord{

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return '{{%pl_borrow}}';
    }

    /**
     * 添加借款人信息
     * @param $data
     * @return bool
     */
    public function addPlBorrow($data) {
        $this->setAttributes($data, false);
        if (!$this->save(false)) {
            return false;
        }
        return true;
    }

    public function copyPlBorrow($plbId)
    {
        $plbInfo = self::findOne(['id' => $plbId]);
        if ($plbInfo) {
            $info = $plbInfo->attributes;
            unset($info['id']);
            $this->setAttributes($info, false);
            $this->isNewRecord;
            if (!$this->save(false)) {
                return false;
            }
            return Yii::$app->db->getLastInsertID();
        } else {
            return false;
        }
    }
}
