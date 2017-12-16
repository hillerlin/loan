<?php

/* 
 * 存管接口字段和内部字段对应表
 */
return [
    'retUrl' => 'retUrl',                                   //前端跳转页面
    'accountId' => 'accountId',                             //存管
    'idNo' => ['real_card','id_card'],                                  //身份证号
    'name' => ['real_name', 'username'],                    //姓名
    'mobile' => ['real_phone', 'mobile'],                               //手机号
    'cardNo' => ['card_bid', 'bank_card'],                                 //银行卡号
    'orderId' => ['orderId'],
    'txAmount' => ['txAmount'],
    'productId' => ['productId'],
    'bonusFlag' => ['bonusFlag'],
    'contOrderId' => ['contOrderId'],
];
