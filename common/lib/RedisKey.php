<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace common\lib;

/**
 * Description of RedisKey
 *
 * @author Administrator
 * @datetime 2017-7-5 19:56:52
 */
class RedisKey {
    //put your code here
    const FRESH_USER_CUSTODY_MONEY = 'fresh_user_custody_money';   //zset刷新存管余额
    const BID_REPAY_ACCOUNT_0706 = 'bid_repay_account_0706:';   //hash类型
    const USER_INFO = 'user_info:'; //hash类型
    const USER_CUSTODY_ACCOUNT_FRESH_TIME = 'user_custody_account_fresh_time';  //有序集合,用户最近刷新时间
    const FRIEND_RECOMMEND_MONTH_TOP = 'friend_recommend_month_top';  //string 好友推荐收益当月前十
    const FRIEND_RECOMMEND_TOP = 'friend_recommend_month_top';  //string 好友推荐总收益前十
    const CPC_SOURCE = 'cpc_source';  //string 推广链接

    const SUMMER_ACTIVITY = 'summer_activity';  //夏日活动
    const BOON_ACTIVITY = 'boon_activity';  //福利大放送
    
    const SUPPLY_CHAIN_TOKEN = 'token:';    //供应链token

    const VALENTINE_ACTIVITY = 'valentine_activity';  //七夕活动
    const SCHOOL_ACTIVITY = 'school_activity';  //开学季
    const ANNIVERSARY_PART_1 = 'anniversary_part_1';  //周年庆Part1
    const ANNIVERSARY_PART_3 = 'anniversary_part_3';  //周年庆Part3
    const ANNIVERSARY_PART_4_RANK = 'anniversary_part_4_rank';  //周年庆Part3

    const AUTO_BID_LISTS = 'auto_bid_lists';    //list自动投标队列
    const AUTO_BID_FIRST = 'auto_bid_first';    //list自动投标优先队列
    const AUTO_BID_SECOND = 'auto_bid_second';          //list自动投标队列
    const AUTO_BID_SETS = 'auto_bid_sets';          //list自动投标队列
    const AUTO_BID_RULES = 'auto_bid_rules:';          //hash 自动投标规则
    const AUTO_BID_BORROW_LISTS = 'queue:auto_bid_borrow';          //list自动投标队列
    const BORROW_INFO = 'borrow_info:';          //list自动投标队列
    
    const MIDAUTUMN_FRIEND_RED = 'midautumn_friend_red';        //中秋好友送红包
    const XFD_TODAY_COUNT = 'xfd_today_count';        //消费贷当天发标统计次数

}
