<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace manage\controllers;


/**
 * Description of HomeController
 *
 * @author Administrator
 * @datetime 2017-2-23 11:46:25
 */
class IndexController extends CommonController {

    public function actions() {
        return [
            //错误页面
            'error' => [
                'class' => 'yii\web\ErrorAction',
                'view' => '404.html'
            ],
        ];
    }

    public function actionIndex()
    {
        return $this->renderPartial('index.html',['userInfo'=>$this->user_info]);
    }
}
