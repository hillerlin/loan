<?php

namespace common\models;

use Yii;

/**
 * LoanOrder model
 *
 * @property integer $order_id
 * @property integer $borrow_id
 * @property integer $borrower_id
 * @property string $amount
 * @property integer $duration
 * @property integer $unit
 * @property integer $repay_style
 * @property integer $addtime
 * @property integer $status
 */
class LoanOrder extends DmActiveRecord {

    const STATUS_GENERATE = 0;  //待确认（订单生成）
    const STATUS_WAITING = 1;  //待确认
    const STATUS_COLLECTING = 2;  //募集中
    const STATUS_REPAYING = 3;  //还款中（已放款）
    const STATUS_SUCCESS = 5;  //订单结束
    const STATUS_CANCEL = 9;  //订单撤销
    /**
     * @inheritdoc
     */

    public static function tableName() {
        return '{{%loan_order}}';
    }
    
    public function behaviors()
    {
        return [
            // 行为，仅直接给出行为的类名称
            'TrusteePay' => \common\behaviros\TrusteePay::className(),
        ];
    }

    public static function findByIdAndBorrowerId($id, $borrower_id) {
        return static::findOne(['order_id' => $id, 'borrower_id' => $borrower_id]);
    }
    
    public function addOneLog($params){
        
        $data = [
            'borrower_id' => $params['borrower_id'], 
            'amount' => $params['amount'], 
            'duration' => $params['duration'], 
            'unit' => $params['unit'], 
            'repay_style' => $params['repay_style'], 
            'rate' => $params['rate'], 
            'borrow_rate' => $params['borrow_rate'], 
            'addtime' => time()
            ];
        $this->setAttributes($data, false);
        return $this->save(false);
    }
    
    public function updateStatus($order_id, $borrow_id, $status) {
        return self::updateAll(['borrow_id' => $borrow_id, 'status' => $status], 'order_id = ' . $order_id);
    }

    public static function getStateFromStatus($status)
    {
        //订单状态（1:待确认|2:募集中|3:还款中|5:订单结束|9:已撤销）
        $array = [
            self::STATUS_GENERATE => 'A',
            self::STATUS_WAITING => 'A',
            self::STATUS_COLLECTING => 'B',
            self::STATUS_REPAYING => 'R',
            self::STATUS_SUCCESS => 'S',
            self::STATUS_CANCEL => 'C',
        ];
        return isset($array[$status]) ? $array[$status] : null;
    }
    
    public static function existWaitConfirm($order_id, $borrower_id) {
        return self::find()->where("status = 1 and order_id !=  $order_id and borrower_id = $borrower_id")->count();
    }

    public static function setRepayingStatusFromBid($bid)
    {
        $order = self::findOne(['borrow_id' => $bid]);
        if ($order) {
            $data = [
                'orderId' => $order->order_id,
                'borrowerId' => $order->borrower_id,
            ];
            $order->status = self::STATUS_REPAYING;
            $order->save();
            return $data;
        } else {
            return false;
        }
    }

    public static function checkOrderInMerchant($orderId, $merchantId)
    {
        $orderInfo = self::findOne(['order_id' => $orderId]);
        if ($orderInfo) {
            $borrowerInfo = LoanBorrower::findIdentity($orderInfo->borrower_id);
            if ($borrowerInfo && $borrowerInfo->merchant_id == $merchantId) {
                return $orderInfo;
            }
        }
        return false;
    }

    public static function getMerchantIdFromOrderId($orderId)
    {
        $sql = "select b.borrower_id, b.merchant_id, o.status from xx_loan_borrower b, xx_loan_order o where o.order_id = $orderId and o.borrower_id = b.borrower_id";
        $info = Yii::$app->db->createCommand($sql)->queryOne();
        return $info ?: ['borrower_id' => 0, 'merchant_id' => 0, 'status' => 0];
    }
}
