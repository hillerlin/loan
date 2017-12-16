<?php

namespace pc\modules\custody\controllers;

use Yii;
use pc\controllers\UserController;
use yii\web\BadRequestHttpException;
use common\custody\UserApi;
use yii\helpers\Url;

/**
 * Default controller for the `custody` module
 */
class GotoController extends UserController {

    //设置密码
    public function actionPasswordset() {
        $updateResultObj=new \common\lib\commonlogic\RenderData('\common\lib\commonlogic\UserCenterBase','setPayPwd');
        $requsetAttr=$updateResultObj->entrustRenderData(['type'=>'Pc','channel'=>'000002']);
        return $this->renderPartial('loading.html',$requsetAttr);
    }
   /* public function actionPasswordset() {
        $smscode = Yii::$app->request->get('smsCode');
        //基本信息
        $user_base_info = Yii::$app->user->getBaseInfo(['user_id', 'accountId', 'real_phone', 'real_card', 'set_pwd'], false);
        $user_id = $user_base_info['user_id'];
        $params = [];
        if (!empty($smscode)) {
            $params['srvTxCode'] = UserApi::getLastSrvAuthCode($user_id);
            $params['smsCode'] = $smscode;
            if (empty($params['srvTxCode'])) {
                throw new \yii\web\BadRequestHttpException("请先获取验证码");
            }
        } else {
            if ($user_base_info['set_pwd'] == 1) {
                throw new \yii\web\BadRequestHttpException("无效请求22");
            }
        }

        if (empty($user_base_info['accountId'])) {
            $this->redirect('register/openandsetpw');
        }
        $user_bank_info = \common\models\AccountBank::findActiveByUid($user_id);
        if (!$user_bank_info) {
            //等前端做好提示页
            throw new \yii\web\BadRequestHttpException("清先开通存管账号");
        }
        $user_api = new UserApi();
        $params = array_merge($user_bank_info, $user_base_info, $params);
//        $params = $user_bank_info + $user_base_info;
        $params['retUrl'] = Url::base(true) . '/';
        
        $data = $user_api->passwordSet($params);
//        var_dump($data);
        echo $this->render('loading.html', $data);
    }*/

    //自动投标签约
    public function actionAutobidauthplus() {
        $userInfo = Yii::$app->user->getBaseInfo();
        if (empty($userInfo['accountId'])) {
            throw new \yii\web\BadRequestHttpException("清先开通存管账号");
        }
        if (empty($userInfo['set_pwd'])) {
            throw new \yii\web\BadRequestHttpException("清先设置交易密码");
        }
        $userId = $userInfo['user_id'];
        $smsCode = Yii::$app->request->get('smsCode', '');
        $_validate = new \common\models\CommonRule(['scenario' => 'autoBidAuthPlus']);
        if (!$_validate->load(['smsCode' => $smsCode], '') || !$_validate->returnCheck()) {
            
            $this->json_error($_validate->joinErrorMsg());
        }
        $atctive = [1, 7, 8, 9,10, 18, 19, 20]; //也可以写成$atctive=['accountId','real_name','txAmount'.......
        $sendData = \common\lib\Helps::joinMappingPlus($atctive, $userId, 'autoBidAuthPlus');
        $sendData['retUrl']=$sendData['retUrl'].'?txCode=autoBidAuthPlus&orderId='.$sendData['orderId'];
        $sendData['forgotPwdUrl']=Yii::$app->request->hostInfo.'/custody/goto/passwordset?type=0&_csrf-mobile='.Yii::$app->request->csrfToken;
        //往auto_bidauth表中插入一条临时数据
        $updateAutoBidauth = \common\models\CommonAuthSql::create_Auto_bidauth($sendData, $userId);
        $requsetApi = new \common\lib\custody\CustodyApi;
       // $sendData['retUrl']=$sendData['retUrl'].'?txCode=autoBidAuthPlus&orderId='.$sendData['orderId'];
        $sendData['smsCode'] = $smsCode;
        $requset = $requsetApi->submitForm($sendData, 2);
        if (!$updateAutoBidauth) {
            throw new \yii\web\NotFoundHttpException("数据库繁忙");
        }
        return $this->render('loading.html', $requset);
    }

    //提现跳转
    public function actionWithdraw() {
        $money = Yii::$app->request->get('money');
        $routeCode = Yii::$app->request->get('routeCode');
        $user_base_info = Yii::$app->user->getBaseInfo(['user_id', 'accountId', 'real_phone', 'real_card', 'set_pwd'], false);
        $user_id = $user_base_info['user_id'];
        if (empty($money)) {
            throw new \yii\web\NotFoundHttpException("无效请求");
        }
        $user_bank_info = \common\models\AccountBank::findActiveByUid($user_id);
        $params['txAmount'] = $money;
        //提现手续费
        $params['txFee'] = '';
        $params['retUrl'] = Url::base(true) . '/';
        $params = $user_bank_info + $user_base_info;
        $user_api = new UserApi();
        $data = $user_api->withdraw($params);
        echo $this->render('loading.html', $data);
    }
        
    //受托支付跳转
    public function actionTrusteepay() {
        $bid = Yii::$app->request->get('bid');
        $user_id = Yii::$app->user->getId();
        $api = new \common\custody\BorrowApi();
        $data = $api->trusteePay($user_id, $bid);
        echo $this->render('loading.html', $data);
    }

}
