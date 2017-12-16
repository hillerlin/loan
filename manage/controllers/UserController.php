<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace manage\controllers;

use common\custody\UserApi;
use common\models\LoanBorrower;
use manage\models\BorrowCollection;
use manage\models\LoanOrder;

/**
 * Description of HomeController
 *
 * @author Administrator
 * @datetime 2017-2-23 11:46:25
 */
class UserController extends CommonController {

    public function actionIndex()
    {
        $userInfo = $this->user_info;
        $userApi = new UserApi();
        $money =  $userApi->balanceQuery(['accountId' => $userInfo['accountId']]);
        $userInfo['currBal'] = $money['currBal'];
        $userInfo['availBal'] = $money['availBal'];
        $recentRepay = BorrowCollection::getRecentDailyRepayAccountByReceiptId($userInfo['enter_user_id'], 7);
        $todayRepay = ($recentRepay && $recentRepay[0]['repay_date'] == date('Y-m-d')) ? $recentRepay[0] : [];
        $borrowerCount = LoanBorrower::selectCount(['merchant_id' => $userInfo['merchant_id']]);
        $orderCount = LoanOrder::getCountByMerchantId($userInfo['merchant_id']);
        $data = [
            'userInfo' => $userInfo,
            'recentRepay' => $recentRepay,
            'todayRepay' => $todayRepay,
            'borrowerCount' => $borrowerCount,
            'orderCount' => $orderCount,
        ];
        return $this->render('index.html', $data);
    }
}
