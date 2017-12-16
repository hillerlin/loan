<?php

namespace manage\models;

use common\lib\Helps;

class LoanOrder extends \common\models\LoanOrder {

    /**
     * 查找商家名下所有的标的信息
     * @param $merchantId
     * @param $page
     * @param int $limit
     * @param string $where
     * @return array
     */
    public static function getOrderListByMerchantId($merchantId, $page, $limit=30, $where=' and 1')
    {
        $limitAttr=Helps::Limit($page,$limit);
        $totalAttr=Helps::createNativeSql("select count(lo.borrow_id) as total from xx_loan_order as lo left JOIN
                                        xx_loan_borrower as lb on lb.borrower_id=lo.borrower_id 
                                        LEFT JOIN xx_user as u on u.user_id=lb.user_id
                                        where lb.merchant_id=$merchantId and lo.borrow_id>0 $where ")->queryOne();
        $userInfo=Helps::createNativeSql("select u.user_id,u.accountId,lo.*,FROM_UNIXTIME(lo.addtime,'%Y-%m-%d') as formatTime,lo.addtime,u.real_name,u.real_card from xx_loan_order as lo left JOIN
                                        xx_loan_borrower as lb on lb.borrower_id=lo.borrower_id 
                                        LEFT JOIN xx_user as u on u.user_id=lb.user_id
                                        where lb.merchant_id=$merchantId and lo.borrow_id>0 $where order by lo.addtime desc limit {$limitAttr['start']} , {$limitAttr['end']} ")->queryAll();
        return $userInfo?['list'=>$userInfo,'total'=>$totalAttr['total']]:[];
    }

    /**
     * 查找商家名下所有的放款信息
     * @param int $page
     * @param int $pageSize
     * @param string $where
     * @return array
     */
    public static function getLendPayListByMerchantId($page = 1, $pageSize = 30, $where = '1') {
        $limit = self::pageToLimit($page, $pageSize);
        $total = self::find()
            ->alias('lo')
            ->leftJoin('xx_loan_borrower lb', 'lb.borrower_id = lo.borrower_id')
            ->leftJoin('xx_borrow b', 'b.bid = lo.borrow_id')
            ->where($where)->count();
        $list = self::find()
            ->alias('lo')
            ->select("lo.order_id, lo.borrower_id, from_unixtime(lo.addtime) addtime, lo.amount, lo.duration, lo.unit, lo.repay_style, from_unixtime(b.lendpay_time) lendpay_time")
            ->leftJoin('xx_loan_borrower lb', 'lb.borrower_id = lo.borrower_id')
            ->leftJoin('xx_borrow b', 'b.bid = lo.borrow_id')
            ->where($where)
            ->offset($limit)
            ->limit($pageSize)
            ->orderBy('b.lendpay_time desc')
            ->asArray()
            ->all();
        return ['list' => $list, 'total' => $total];
    }

    public static function getCountByMerchantId($merchantId)
    {
        $list = self::find()
            ->alias('lo')
            ->select('lo.status status, count(1) count')
            ->innerJoin('xx_loan_borrower lb', 'lb.borrower_id = lo.borrower_id')
            ->where(['lb.merchant_id' => $merchantId])
            ->groupBy('lo.status')
            ->orderBy('lo.status asc')
            ->asArray()
            ->all();
        $total = 0;
        $countList = [
            self::STATUS_GENERATE => 0,
            self::STATUS_WAITING => 0,
            self::STATUS_COLLECTING => 0,
            self::STATUS_REPAYING => 0,
            self::STATUS_SUCCESS => 0,
            self::STATUS_CANCEL => 0,
        ];
        foreach ($countList as $key => $value) {
            foreach ($list as $l) {
                if ($l['status'] == $key) {
                    $countList[$key] = $l['count'];
                    $total += $l['count'];
                }
            }
        }
        return ['list' => $countList, 'total' => $total];
    }

    public static function getStatusNameFromStatus($status)
    {
        //订单状态（1:待确认|2:募集中|3:还款中|5:订单结束|9:已撤销）
        $array = [
            self::STATUS_GENERATE => '已生成',
            self::STATUS_WAITING => '待确认',
            self::STATUS_COLLECTING => '募集中',
            self::STATUS_REPAYING => '还款中',
            self::STATUS_SUCCESS => '已结束',
            self::STATUS_CANCEL => '已撤销',
        ];
        return isset($array[$status]) ? $array[$status] : null;
    }

}
