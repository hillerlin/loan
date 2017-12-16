<?php
namespace common\models;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
use Yii;
use yii\base\InvalidParamException;

/**
 * Description of DmActiveRecord
 *
 * @author Administrator
 */
class DmActiveRecord extends \yii\db\ActiveRecord{
    //put your code here
    
    //根据主id查找指定字段
    public function getByPk($select, $pk_value) {
        $pk = $this->primaryKey();
        if (count($pk) != 1) {
            throw new InvalidParamException('主键超过2个');
        }
        return static::find()->select($select)->where([$pk[0] => $pk_value])->asArray()->one();
    }

    /**
     * 将page转换成偏移起始量limit
     * @param $page
     * @param $pageSize
     * @return int
     */
    public static function pageToLimit($page, $pageSize) {
            return $page < 1 ? $pageSize : ($page - 1) * $pageSize;
    }

    /**
     *
     * @param $condition
     * @param string $params
     * @param string $select
     * @param int $page
     * @param int $pageSize
     * @param string $orderBy
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function listTable($condition, $params = '', $select = '*', $page = 0, $pageSize = 10, $orderBy = '') {
        $query = static::find()
                ->select($select)
                ->where($condition)
                ->params($params);
        if ($page && $pageSize) {
            $offset = self::pageToLimit($page, $pageSize);
            $query->offset($offset)->limit($pageSize);
        }
        $list = $query->orderBy($orderBy)
                ->asArray()
                ->all();
        return $list;
    }

    /**
     * 批量插入数据库
     * @param $data
     * @return int
     */
    public static function batchInsert($data) {
        $data = \yii\helpers\ArrayHelper::explodeArr($data);
        return Yii::$app->db->createCommand()->batchInsert(self::tableName(), $data['columns'], $data['rows'])->execute();
    }

    /**
     * 生成充值提现订单号
     * @param $txDate
     * @param $txTime
     * @param $seqNo
     * @return string
     */
    public function generateRechargeOrPaymentOrderId($txDate, $txTime, $seqNo) {
        return $txDate . $txTime . $seqNo;
    }

    public static function selectOne($where, $fields)
    {
        return static::find()->select($fields)->where($where)->asArray()->one();
    }

    public static function selectCount($where, $bind = [])
    {
        return static::find()->where($where)->params($bind)->count();
    }

    public static function updateInfo($where, $update)
    {
        return Yii::$app->db->createCommand()->update(static::tableName(), $update, $where)->execute();
    }

}
