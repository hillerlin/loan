<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace pc\widgets;

use Yii;
use common\models\UserAccount;
/**
 * Description of HomeMenu
 *
 * @author Administrator
 * @datetime 2017-2-24 18:09:27
 */
class HomeMenu extends \yii\base\Widget {

    public $user;
    public $fix_data;

    public function init() {
        parent::init();
        $this->fix_data = ['is_fix' => 0, 'red_money' => 0, 'coupon' => 0, 'title' => 0, 'dialog_type' => 0,];
        //如果沒有传入用户基础信息，就去获取
        if ($this->user === null) {
            $user_info = Yii::$app->user->getBaseInfo();
            $this->user = \yii\helpers\ArrayHelper::merge(UserAccount::getAllMoney($user_info['user_id']), $user_info);
        }
    }

    public function run() {
        $action = Yii::$app->controller->action->id;
        return $this->render('/./../views/home/common.html', ['user' => $this->user, 'fix_data' => $this->fix_data, 'action_dsc' => $this->actionsMap($action)]);
    }

    protected function actionsMap($type = "index") {
        $menu['index'] = '账户总览';
        $menu['account_setting'] = '账户设置';
        $menu['message'] = '消息中心';
        $menu['account_detail'] = '资金明细';
        $menu['invest_record'] = '投资记录';
        $menu['user_wallet'] = '麦荷包';
        $menu['user_wallet_redeem'] = '麦荷包';
        $menu['user_wallet_proceeds'] = '麦荷包';
        $menu['card_setting'] = '银行卡';
        $menu['red_envelope'] = '我的红包';
        $menu['red_enveloped'] = '我的红包';
        $menu['raffle'] = '我的抽奖';
        $menu['experience'] = '体验金';
        $menu['experience_record'] = '体验金';
        $menu['recommend'] = '我的推荐';
        $menu['friends'] = '我的好友';
        $menu['friends_record'] = '我的好友';
        $menu['payment'] = '提现';
        $menu['recharge'] = '充值';
        $menu['recharge_list'] = '网银充值记录';
        $menu['payment_list'] = '提现记录';
        $menu['voucher_list'] = '代金券充值记录';

        return isset($menu[$type]) ? $menu[$type] : $menu['index'];
    }

}
