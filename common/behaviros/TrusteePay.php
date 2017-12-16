<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace common\behaviros;

use Yii;
/**
 * Description of TrusteePay
 *
 * @author Administrator
 * @datetime 2017-5-22 14:37:39
 */
class TrusteePay extends \yii\base\Behavior{
    //put your code here
    
    /**
     * 借款人
     * @param type $bid 标的Id
     * @param type $debtor 借款人user_id
     * @return boolean
     */
    public function setDebtor($bid, $debtor) {
        $wait_make_sure = Yii::$app->redis->get('trusteePay:' . $debtor);
        if (!empty($wait_make_sure)) {
            return false;
        }
        Yii::$app->redis->set('trusteePay:' . $debtor, $bid);
        return true;
    }

    /**
     * 修改借款人
     *
     * @param type $bid    标的Id
     * @param type $debtor 借款人user_id
     * @param      $old_debtor
     *
     * @return bool
     */
    public function editDebtor($bid, $debtor, $old_debtor) {
        if( $debtor == $old_debtor ){
            return true;
        }
        $wait_make_sure = Yii::$app->redis->get('trusteePay:' . $debtor);
        if (!empty($wait_make_sure)) {
            return false;
        }
        Yii::$app->redis->del('trusteePay:' . $old_debtor);
        Yii::$app->redis->set('trusteePay:' . $debtor, $bid);
        return true;
    }

    /**
     * 获取借款人受托支付
     * @param type $debtor
     * @return int
     */
    public function getBidByDebtor($debtor) {
        $bid = Yii::$app->redis->get('trusteePay:' . $debtor);
        return $bid;
    }
    
    public function delDebtor($debtor) {
        return Yii::$app->redis->del('trusteePay:' . $debtor);
    }
}
