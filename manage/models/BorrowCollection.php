<?php

namespace manage\models;

use common\lib\Helps;

class BorrowCollection extends \common\models\BorrowCollection {


    /**
     * 查找商家名下所有的还款信息
     * @param $merchantId
     * @param $page
     * @param int $limit
     * @param string $where
     * @return array
     */
    public static function getBorrowCollectionListByMerchantId($merchantId, $page, $limit=30, $where=' and 1')
    {
        $limitAttr=Helps::Limit($page,$limit);
        $totalAttr=Helps::createNativeSql("select count(bc.id) as total  from xx_borrow_collection as bc left JOIN
                                                xx_loan_order as lo on lo.borrow_id=bc.borrow_id 
                                                left join 
                                                xx_loan_borrower as lb on lb.borrower_id=lo.borrower_id
                                                left JOIN
                                                xx_user as u on u.user_id=lb.user_id
                                                where lb.merchant_id=$merchantId and lo.borrow_id>0  $where ")->queryOne();
        $userInfo=Helps::createNativeSql("select from_unixtime(bc.repay_time,'%Y-%m-%d') as repay_time,u.real_name,from_unixtime(bc.repay_yestime,'%Y-%m-%d') as repay_yestime,bc.interest_1,bc.repay_account,bc.borrow_id,bc.intermediary_fee,bc.status,u.accountId,lo.order_id,u.real_card  from xx_borrow_collection as bc left JOIN
                                                xx_loan_order as lo on lo.borrow_id=bc.borrow_id 
                                                left join 
                                                xx_loan_borrower as lb on lb.borrower_id=lo.borrower_id
                                                left JOIN
                                                xx_user as u on u.user_id=lb.user_id
                                                where lb.merchant_id=$merchantId and lo.borrow_id>0  $where order by bc.status asc, bc.repay_time asc limit {$limitAttr['start']} , {$limitAttr['end']} ")->queryAll();
        return $userInfo?['list'=>$userInfo,'total'=>$totalAttr['total']]:[];
    }

    public static function getRecentDailyRepayAccountByReceiptId($receiptId, $days = 7)
    {
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime('+'.$days.' days'));
        $sql = <<<SQL
SELECT
	FROM_UNIXTIME(bc.repay_time, '%Y-%m-%d') repay_date,
	COUNT(bc.borrow_id) borrow_count,
	SUM(bc.repay_account) account,
	SUM(bc.interest_1) interest,
	SUM(bc.intermediary_fee) intermediary_fee,
	SUM(bc.repay_account + bc.interest_1 + bc.intermediary_fee) total
FROM
	xx_borrow b
LEFT JOIN xx_borrow_collection bc ON b.bid = bc.borrow_id
WHERE
	b.receipt_id = $receiptId
AND bc.repay_time BETWEEN UNIX_TIMESTAMP('$startDate')
AND UNIX_TIMESTAMP('$endDate')
GROUP BY
	repay_date
ORDER BY 
    repay_date ASC 
SQL;
        $list = Helps::createNativeSql($sql)->queryAll();
        return $list ?: [];
    }
}
