<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace manage\controllers;
use Yii;

/**
 * Description of HomeController
 *
 * @author Administrator
 * @datetime 2017-2-23 11:46:25
 */
class LoginController extends CommonController {
    public function actions() {
        return [
            //错误页面
            'error' => [
                'class' => 'yii\web\ErrorAction',
                'view' => '404.html'
            ],
            //验证码
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
                'maxLength' => 5,
                'minLength' => 5,
            ],
        ];
    }
    public function actionIndex()
    {
        if (!Yii::$app->user->getIsGuest()) {
            $this->redirect('/index');
        }
        $request = Yii::$app->request;
        if ($request->isPost) {
            $_user['real_phone'] = trim($request->post('username', ''));
            $_user['apwd'] = trim($request->post('password', ''));
            $_user['img_verify_code'] = $request->post('captcha');
            $model = new \common\models\LoginForm();
            if ($model->load($_user, '') && $model->login()) {
                $this->json_success('正在登陆.....');
            } else {
                $this->json_error('账号或密码错误!');
            }
        }
        return $this->render('index.html',[]);
    }

    /* 退出 */
    public function actionLogout() {
        Yii::$app->user->logout();
        $this->redirect('/login');
    }


}