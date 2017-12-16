<?php
/**
 * 定义一个存管的配置文件数组
 * 存管银行 江西银行
 */
$cusdoy = array();

/**
 * ==========================
 * 银行配置
 * 
 * 
 * 
 * *
*/

//加密私匙密码
$cusdoy['key']['pass'] = "damailicai_sit@2016";
$cusdoy['key']['keys'] = dirname(__FILE__) . "/damailicai_sit.p12";
//$cusdoy['key']['keys'] = "F:/code/svn/damai/weixfin/common/config/custody_config/yb_sit.p12";
$cusdoy['key']['crt'] = dirname(__FILE__) . "/fdep.crt";
//$cusdoy['key']['crt'] = "F:/code/svn/damai/weixfin/common/config/custody_config/damailicai_sit.crt";

$domain = 'https://test.credit2go.cn';
//uri 接口地址
$cusdoy['sys']['uri'] = "$domain/escrow/p2p/online";
$cusdoy['sys']['fileDownload'] = "$domain/escrow/file/download";
$cusdoy['sys']['notifyUrl'] = 'http://120.24.68.184:9017/custody/notify';
$cusdoy['sys']['forgotPwdUrl'] = $domain.'/home/setting';
//post表单接口对应地址
$cusdoy['formUrl']['directRecharge'] = "$domain/escrow/p2p/page/mobile";
$cusdoy['formUrl']['autoBidAuthPlus'] = "$domain/escrow/p2p/page/mobile/plus";
$cusdoy['formUrl']['passwordResetPlus'] = "$domain/escrow/p2p/page/mobile/plus";
$cusdoy['formUrl']['passwordSet'] = "$domain/escrow/p2p/page/passwordset";
$cusdoy['formUrl']['withdraw'] = "$domain/escrow/p2p/page/withdraw";
$cusdoy['formUrl']['trusteePay'] = "$domain/escrow/p2p/page/trusteePay";
$cusdoy['formUrl']['passwordReset'] = "$domain/escrow/p2p/page/mobile";
//平台回调
$cusdoy['retUrl']['000002'] = 'custody/callback';
$cusdoy['retUrl']['000001'] = '';
$cusdoy['retUrl']['000003'] = '';
//平台需要根据即信提供的参数进行配置
$cusdoy['sys']['version'] = "10";
$cusdoy['sys']['card'] = '621246186';
//银行代码
$cusdoy['sys']['bankcode'] = "30050000";
//银行编码
$cusdoy['sys']['bankinstcode'] = "3005";
//机构代码
$cusdoy['sys']['instcode'] = "00770001";
//合作编号
$cusdoy['sys']['coinstcode'] = "000133";
//产品编号
$cusdoy['sys']['product'] = "0087";
//产品发行发
$cusdoy['sys']['product_inst'] = 'LV';
//账户类型 2活期
$cusdoy['sys']['acc_type'] = "2";
//身份证号类型
$cusdoy['sys']['idType']='01';

//交易渠道
/**
 * 000001手机APP
 * 000002网页
 * 000003微信
 * 000004柜面
 */
$cusdoy['sys']['channel'] = "000002";//默认

/**
 * ==========================
 * 账户配置
 * 
 * 
 * 
 * *
*/
//红包账户
//电子银行账号
$cusdoy['red']['accountId'] = "6212461860000200059";
//银行卡号-绑定
$cusdoy['red']['bankcardId'] = "6222988812340038";
$cusdoy['red']['phone'] = "18681501795";
//身份证
$cusdoy['red']['realcard'] = "320404197709020214";
$cusdoy['red']['realname'] = "蒋牧人";
//手续费
//电子银行账号
//$cusdoy['fee']['accountId'] = "6212461270000000360";
$cusdoy['fee']['accountId'] = "6212461860000100036";
//银行卡号-绑定
$cusdoy['fee']['bankcardId'] = "6217214000020389785";
$cusdoy['fee']['phone'] = "18620691610";
//身份证
$cusdoy['fee']['realcard'] = "370702198105182228";
$cusdoy['fee']['realname'] = "韩婷";

//return $cusdoy;
//return \yii\helpers\ArrayHelper::merge($cusdoy, require(__DIR__ . '/custody-local.php'));//custody-local.php 文件未提交 先注释掉
return \yii\helpers\ArrayHelper::merge($cusdoy, require(__DIR__ . '/custody-local.php'));

