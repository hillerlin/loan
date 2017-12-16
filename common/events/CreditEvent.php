<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace common\events;

use yii\base\Event;
use common\models\Account;
/**
 * Description of CreditsEvent
 *
 * @author Administrator
 * @datetime 2017-9-16 14:46:41
 */
class CreditEvent extends Event{
    //put your code here
    
    public $user_id;

    public $moeny;
    
    
    public static function functionName($event) {
        $relatedid = isset($event->data['relatedid']) ? $event->data['relatedid'] : 0;
        $remark = isset($event->data['remark']) ? $event->data['remark'] : '';
        Account::creditsChange($event->data['user_id'], $event->data['change_credist'], $event->data['operation'], $relatedid, Account::CREDIT_OUT, $remark);
    }
}
