<?php

namespace common\models;

use Yii;
use common\models\DmActiveRecord;
use common\models\Borrow;
use common\lib\Helps;

class BorrowCollection extends DmActiveRecord {

    const STATUS_WAIT = 2;      //等待还款
    const STATUS_SUCCESS = 3;   //还款成功

    /**
     * @inheritdoc
     */

    public static function tableName() {
        return '{{%borrow_collection}}';
    }

    /**
     * 查询出标的最小的未还款的期数，已完全还款完成的就是查询出来的最小期数-1
     * @param $borrow_id
     * @return string
     */
    public static function getUnRepayPeriod($borrow_id) {
        $condition = 'borrow_id=:borrow_id AND status=:status';
        $params[':borrow_id'] = $borrow_id;
        $params[':status'] = self::STATUS_WAIT;
        $last_unrepay_period = self::find()->where($condition)->params($params)->min('period');
        $period = self::find()->where('borrow_id=' . $borrow_id)->max('period');
        //已经还款了
        if (empty($last_unrepay_period) && empty($period)) {
            return '0';
        }
        if (empty($last_unrepay_period)) {
            return $period;
        }
        return (string)($last_unrepay_period - 1);
    }

    public function getDebtScheduleFromBid($borrowId)
    {
        $sql = 'select * from xx_borrow_collection where borrow_id = :borrow_id;';
        $params = [':borrow_id' => $borrowId];
        $list = self::findBySql($sql, $params)
            ->asArray()
            ->all();
        return $list;
    }

    public function formatDebtSchedule($data)
    {
        if (empty($data)) {
            return [];
        }
        $lists = [];
        $repaymentAmt = '0.00';
        $repaymentInt = '0.00';
        $date = date('Ymd', time());
        foreach ($data as $val) {
            $list['repayTime'] = date('Ymd', $val['repay_time']);
            $list['period'] = $val['period'];
            $list['isRepay'] = $val['period'] <= $val['paid_period'] ? '1' : '0'; //0待还 1已还
            if ($list['isRepay']==0 && $date >= $list['repayTime']) {
                $list['isRepay'] = '2';//未还
            }
//            $list['principal'] = $val['repay_account'];
//            $list['interest'] = $val['interest_1'];
//            $list['intermediaryFee'] = $val['intermediary_fee'];
            $list['repayAmount'] = bcadd($val['intermediary_fee'],bcadd($val['repay_account'],$val['interest_1'],4),2);
            $repaymentAmt += $val['period'] <= $val['paid_period'] ? $val['principal'] + $val['interest'] + $val['intermediary_fee'] : '0.00';
            $repaymentInt += $val['interest'];
            $lists[] = $list;
        }
        unset($list);
        return ['subPacks' => $lists, 'repaymentAmt' => $repaymentAmt, 'repaymentInt' => $repaymentInt];
    }
}
