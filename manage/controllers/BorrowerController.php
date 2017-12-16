<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/12 0012
 * Time: 上午 11:25
 */
namespace manage\controllers;

use api\handler\api\Borrow;
use api\handler\api\CollectUserInfo;
use common\models\AccountBank;
use common\models\LoanBorrower;
use api\handler\api\Register;
use Yii;


class BorrowerController extends CommonController
{

    public function actionIndex()
    {
        $request = Yii::$app->request;
        $session = Yii::$app->session;
        $userInfo = $session->get('userInfo');
        $page = $request->get('page');
        if ($request->isGet && $page) {
            $data = \common\models\LoanBorrower::getBorrowerListByMerchantId($userInfo['merchant_id'], $page);
            $data['list'] = $this->formatData($data['list']);
            $this->layuiJson($data);
        } else {
            return $this->render('borrower.html', $userInfo);
        }
    }

    protected function formatData($data) {
        foreach ($data as & $row) {
            $row['addtime'] = date('Y-m-d', $row['addtime']);
        }
        return $data;
    }

    public function actionAdd() {
        return $this->render('add.html');
    }
    
    /**
     * 开户
     */
    public function actionRegister()
    {
        $service = new Register();
        if ($service->setIsLocal(true)->getResult()) {
            $data = $service->getData();
            $borrower = LoanBorrower::findIdentity($data['dmId']);
            $accountBank = new AccountBank();
            $request = Yii::$app->request;
            $params['real_name'] = $request->post('realName');
            $params['bank_card'] = $request->post('cardNo');
            $params['user_id'] = $borrower->user_id;
            //将银行卡信息保存在系统中
            if ($accountBank->setDefaultBankCard($params) === false) {
                $this->json_error('银行卡添加失败。');
            }
            $this->json_success('添加成功');
        } else {
            $this->json_error($service->getErrorMsg());
        }
    }

    /**
     * 借款
     */
    public function actionLoan()
    {
        $request = Yii::$app->request;
        $borrower_id = $request->get('borrower_id');
        if ($request->isGet && $borrower_id) {
            $session = Yii::$app->session;
            $userInfo = $session->get('userInfo');
            //判断是否填写信息
            $borrower_info = LoanBorrower::findBorrowerByIdAndMerchantId($borrower_id, $userInfo['merchant_id']);
            if (empty($borrower_info['plb_id'])) {
                return $this->render('borrow_detail.html', $borrower_info);
            } else {
                return $this->render('borrow.html', $borrower_info);
            }
        }
    }

    public function actionEditDetail() {
        $service = new CollectUserInfo();
        if ($service->setIsLocal(true)->getResult()) {
            $this->json_success('添加成功');
        } else {
            $this->json_error($service->getErrorMsg());
        }
    }

    public function actionBorrow() {
        $service = new Borrow();
        if ($service->setIsLocal(true)->getResult()) {
            $this->json_success('添加成功');
        } else {
            $this->json_error($service->getErrorMsg());
        }
    }



}