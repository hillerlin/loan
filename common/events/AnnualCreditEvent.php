<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace common\events;

use common\models\Credit;
/**
 * Description of CreditsEvent
 *
 * @author Administrator
 * @datetime 2017-9-16 14:46:41
 */
class AnnualCreditEvent extends CreditEvent{
    //put your code here
    
    const START_TIME = 1505707198;      //20170918
    const END_TIME = 1507824000;        //20171013 00：00:00
    const OPERATION = \common\models\CreditLog::OPERATION_ANNUAL_TASK;
    
    //周年庆活动登录，随机送
    public static function annualLogin($event) {
        if (self::timeLimit() == false) {
            return false;
        }
        $current = $_SERVER['REQUEST_TIME'];
        if ($current < self::START_TIME && $current > SELF::END_TIME) {
            return ;
        }
        $cyclenum = 1;
        $action = 'annual_login';
        $rand_num = mt_rand(1, 5);
        $credits = new Credit($event->data['user_id'], $action, $cyclenum, self::OPERATION, 100 * $rand_num);
        $credits->send();
    }
    
    
    //周年庆活动个人首投
    public static function annualFirstBid($event) {
        if (self::timeLimit() == false) {
            return false;
        }
        if ($event->data['bidMoney'] >= 100) {
            $cyclenum = 1;
            $action = 'annual_first_bid';
            $credits = new Credit($event->data['user_id'], $action, $cyclenum, self::OPERATION);
            $credits->send();
        }
    }
    
    //周年庆个人投资 1000送100积分
    public static function annualBid($event) {
        if (self::timeLimit() == false) {
            return false;
        }
        if ($event->data['limit_time'] < 6) {
            return false;
        }
        $cyclenum = floor($event->data['bidMoney'] / 1000);
        $action = 'annual_bid';
        if ($cyclenum > 0) {
            $credits = new Credit($event->data['user_id'], $action, $cyclenum, self::OPERATION);
            $credits->send();
        }
    }
    
    //周年庆邀请好友注册
    public static function annualInviteRegister($event) {
        if (self::timeLimit() == false) {
            return false;
        }
        $cyclenum = 1;
        $action = 'annual_invite_register';
        $credits = new Credit($event->data['user_id'], $action, $cyclenum, self::OPERATION);
        $credits->send();
    }
    
    //周年庆好友首投
    public static function annualFriendFirstBid($event) {
        if (self::timeLimit() == false) {
            return false;
        }
        $cyclenum = 1;
        $action = 'annual_friend_first_bid';
        if ($event->data['bidMoney'] >= 1000) {
            $credits = new Credit($event->data['user_id'], $action, $cyclenum, self::OPERATION);
            $credits->send();
        }
    }
    
    //周年庆好友投资达标 》=1000 送推荐人
    public static function annualFriendBidReach($event) {
        if (self::timeLimit() == false) {
            return false;
        }
        $cyclenum = 1;
        $action = 'annual_friend_bid_reach';
        
        $has_bid_money = \common\models\BorrowTender::getUserBidMoney($event->data['user_id'], self::START_TIME, SELF::END_TIME);
        //给推荐人送
        if ($has_bid_money >= 1000 && ($has_bid_money - $event->data['bidMoney'] < 1000)) {
            $credits = new Credit($event->data['pop_uid'], $action, $cyclenum, self::OPERATION);
            $credits->send();
        }
    }
    
    //周年庆好友首投
    public static function annualActiveEmail($event) {
        if (self::timeLimit() == false) {
            return false;
        }
        $cyclenum = 1;
        $action = 'annual_active_email';
        $credits = new Credit($event->data['user_id'], $action, $cyclenum, self::OPERATION);
        $credits->send();
    }
    
    public static function timeLimit() {
        $current = $_SERVER['REQUEST_TIME'];
        if ($current < self::START_TIME || $current > SELF::END_TIME) {
            return false;
        }
        return true;
    }
    
}
