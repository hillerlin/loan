<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/8
 * Time: 14:35
 */

namespace common\models;

use Yii;
use common\models\DmActiveRecord;

class Batch_records extends DmActiveRecord {

    //1放款 2还款 3结束债权
    const TYPE_LAND_PAY = 1;
    const TYPE_REPAY = 2;
    const TYPE_FINISH_DEBT = 3;
    //1异常 2等待处理 3 已经处理完成
    const STATUS_ERROR = 1;
    const STATUS_WAIT = 2;
    const STATUS_SUCCESS = 3;
    const STATUS_FAILED = 4;
    const STATUS_CUSTODY_DONE = 5;

        /**
     * @inheritdoc
     */
    public static function tableName() {
        return '{{%batch_records}}';
    }
    
    /**
     * 返回状态描述
     * @param type $status
     * @return string
     */
    public static function statusDesc($status = null) {
        $desc = [self::STATUS_ERROR => '异常', self::STATUS_WAIT => '等待处理', self::STATUS_SUCCESS => '业务完成', self::STATUS_FAILED => '银行处理失败', self::STATUS_CUSTODY_DONE => '银行处理成功'];
        if ($status === null) {
            return $desc;
        }
        return isset($desc[$status]) ? $desc[$status] : '';
    }
    
    /**
     * 把请求数据放到数据库中
     * 从批次中获取到标的id,便于做关联查询
     */
    public static function addOneLog($params){
        if($params['txCode'] == 'balanceQuery' || $params['txCode']=='batchQuery'){//这2个不写库了
            return;
        }
        $productId = isset($params['productId']) ? $params['productId'] : 0;
        if (in_array($params['txCode'], ['batchLendPay', 'batchRepay', 'batchCreditEnd']) && isset($params['subPacks'])) {
            $subPacks = json_decode($params['subPacks'],true);
            $productId = $subPacks[0]['productId'];
        }
        $send_pay = ['batchLendPay' => self::TYPE_LAND_PAY, 'batchRepay' => self::TYPE_REPAY, 'batchCreditEnd' => self::TYPE_FINISH_DEBT];
        $data = [
            'batch_no' => isset($params['batchNo']) ? $params['batchNo'] : 0, 
            'status' => 2, 
            'borrow_id' => $productId, 
            'txDate' => $params['txDate'], 
            'txTime' => $params['txTime'], 
            'seqNo' => $params['seqNo'], 
            'txAmount' => isset($params['txAmount']) ? $params['txAmount'] : 0, 
            'txCounts' => isset($params['txCounts']) ? $params['txCounts'] : 0, 
            'txCode' => $params['txCode'], 
            'send_type' => isset($send_pay[$params['txCode']]) ? $send_pay[$params['txCode']] : 0, 
            'addtime' => time(), 
            'remark' => json_encode($params)
            ];
        self::addLog(0,$data);
    }
    
    
    /**
     * 获取对应的状态描述
     * @param type $status
     * @return string
     */
    public static function statusTypeDesc($status = '') {
        $desc = [self::STATUS_ERROR => '异常', self::STATUS_WAIT => '等待银行处理', self::STATUS_SUCCESS => '业务处理完成', self::STATUS_FAILED => '业务处理失败', self::STATUS_CUSTODY_DONE => '银行回调完成',];
        if (empty($status)) {
            return $desc;
        }
        return isset($desc[$status]) ? $desc[$status] : '';
    }

    public static function getTxDate($batch_no,$send_type=0){
        $sql="select txDate from xx_batch_records where status=2 and batch_no={$batch_no} and send_type={$send_type} order by addtime desc limit 1";
        $list = self::findBySql($sql)
                ->asArray()
                ->one();
        if(!empty($list)){
            return $list['txDate'];
        }
        return null;
    }
    public static function getOne($batch_no,$txCode,$send_type=0){
        if(empty($batch_no) || empty($txCode)){
            return null;
        }
        $sql="select * from xx_batch_records where status=2 and batch_no={$batch_no} and txCode='".$txCode."' and send_type={$send_type} order by addtime desc limit 1";
        $list = self::findBySql($sql)
                ->asArray()
                ->one();
        
        return $list;
    }
    public static function getRepayConfrim($status,$txCode,$send_type=0){
        if(empty($status) || empty($txCode)){
            return null;
        }
        $sql="select * from xx_batch_records where status={$status} and txCode='".$txCode."' and send_type={$send_type} order by addtime desc";
        $list = self::findBySql($sql)
                ->asArray()
                ->all();
        
        return $list;
    }

    public static function addLog($user_id, $data) {
        $table='{{%batch_records}}';
        
        $data = \yii\helpers\ArrayHelper::explodeArr($data);
        return Yii::$app->db->createCommand()->batchInsert($table, $data['columns'], $data['rows'])->execute();
    }

    public static function _listTable($where, $pageNum, $offset, $orderBy) {
        $list = static::find()
                ->where($where)
                ->offset($offset)
                ->limit($pageNum)
                ->orderBy($orderBy)
                ->asArray()
                ->all();
        return $list;
    }

    /**
     * 根据发送类型区分批次处理
     * 
     * @param type $page
     * @param type $pageSize
     * @param type $send_type
     * @return type
     */
    public static function getBatchBySendType($page, $pageSize, $send_type) {
        $condition = 'send_type = :send_type AND b.bid is not null ';
        $params[':send_type'] = $send_type;
        $orderBy = 'id DESC';
        $total = self::find()->from(self::tableName() . ' AS br')
                ->leftJoin('{{%borrow}} as b', 'br.batch_no=b.batch_no')->where($condition)->params($params)->count();
        $offset = self::pageToLimit($page, $pageSize);
        $list = self::find()
                ->select('b.*,br.status as br_status,br.addtime as br_addtime,notify_time')
                ->from(self::tableName() . ' AS br')
                ->leftJoin('{{%borrow}} as b', 'br.batch_no=b.batch_no')
                ->where($condition)
                ->params($params)
                ->orderBy($orderBy)
                ->limit($pageSize)
                ->offset($offset)
                ->asArray()
                ->all();
        return ['total' => $total, 'list' => $list];
    }

    public static function getBatchList($page, $pageSize, $txCode = '', $batchNo = 0, $borrow_id = 0)
    {
        $where = '1';
        if ($txCode) {
            $where .= ' and txCode = \'' . $txCode .'\'';
        }
        if ($batchNo) {
            $where .= ' and batch_no = ' . $batchNo;
        }
        if ($borrow_id) {
        $where .= ' and borrow_id = ' . $borrow_id;
        }
        $total = self::find()
                     ->where($where)
                     ->count();
        $offset = self::pageToLimit($page, $pageSize);
        $list = self::find()
                    ->select('id, batch_no, status, borrow_id, txCode, addtime, remark, rremark, notify_time')
                    ->where($where)
                    ->orderBy('id desc')
                    ->limit($pageSize)
                    ->offset($offset)
                    ->asArray()
                    ->all();
        return ['total' => $total, 'list' => $list];
    }

    public function getBatchByRepay($page, $pageSize) {
        $condition = 'send_type = :send_type ';
        $params[':send_type'] = self::TYPE_REPAY;
        $orderBy = 'id DESC';
        $total = self::find()->from(self::tableName() . ' AS br')
                ->leftJoin('{{%borrow}} as b', 'br.batch_no=b.batch_no')->where($condition)->params($params)->count();
        $offset = self::pageToLimit($page, $pageSize);
        $list = self::find()
                ->select('b.*,br.status as br_status,br.addtime as br_addtime,notify_time')
                ->from(self::tableName() . ' AS br')
                ->leftJoin('{{%borrow_collection}} as bc', 'bc.batch_no=b.batch_no')
                ->leftJoin('{{%borrow}} as b', 'bc.borrow_id=b.bid')
                ->where($condition)
                ->params($params)
                ->orderBy($orderBy)
                ->limit($pageSize)
                ->offset($offset)
                ->asArray()
                ->all();
        return ['total' => $total, 'list' => $list];
    }
    
    /**
     * 通过batch_no获取对应的记录信息
     * @param type $batch_no
     * @return type
     */
    public static function getByBatchNo($batch_no) {
        return self::find()->where('batch_no = :batch_no')->params([':batch_no' => $batch_no])->asArray()->one();
    }
    
    /**
     * 通过batch_no获取对应的记录信息
     * @param type $batch_no
     * @return type
     */
    public static function getBySeqNoAndTxDate($seqNo, $txDate, $columns = '*') {
        return self::find()->select($columns)->where('seqNo = :seqNo and txDate = :txDate')->params([':seqNo' => $seqNo, ':txDate' => $txDate])->asArray()->one();
    }

}
