<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace api\controllers;

use api\handler\base\ApiHandler;

/**
 * Description of HomeController
 *
 * @author Administrator
 * @datetime 2017-2-23 11:46:25
 */
class LoanController extends MyController {

    //用户首页
    public function actionIndex() {
        echo (new ApiHandler())->getResponse();exit;
    }

}
