<?php

namespace common\models;

use Yii;

/**
 * LoanCreditReport model
 *
 * @property integer $report_id
 * @property string $mobile
 * @property string $real_name
 * @property string $id_no
 * @property integer $merchant_id
 * @property string $report
 * @property integer $addtime
 */
class LoanCreditReport extends DmActiveRecord {
    /**
     * @inheritdoc
     */

    public static function tableName() {
        return '{{%loan_credit_report}}';
    }

    public static function findByReportIdAndMerchantId($report_id, $merchant_id) {
        return static::findOne(['report_id' => $report_id, 'merchant_id' => $merchant_id]);
    }

    public static function findByBorrowerInfo($info) {
        return static::findOne(['merchant_id' => $info['merchant_id'], 'mobile' => $info['mobile'], 'real_name' => $info['real_name'], 'id_no' => $info['id_no']]);
    }

    public function addOneLog($params){
        $data = [
            'mobile' => $params['mobile'],
            'real_name' => $params['real_name'],
            'id_no' => $params['id_no'],
            'merchant_id' => $params['merchant_id'],
            'report' => $params['report'],
            'addtime' => time()
        ];
        $this->setAttributes($data, false);
        return $this->save(false);
    }
}
