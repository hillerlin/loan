<?php
use yii\helpers\Html;

function getUrl( $action, $param ){
    if( strpos( $action, '://' ) === false ){
        $action = [ $action ];
        $arr = array_merge( $action, $param );
        return Yii::$app->urlManager->createAbsoluteUrl($arr);
    }
    else{
        $url = $action.'?';
        foreach( $param as $key => $value ){
            $url .= "$key=$value&";
        }
        return substr( $url, 0, - 1 );
    }
}
//$resetLink = getUrl('http://dm_pc.com/home/check_email', ['verify_code' => $verifyCode]);
//$logoUrl = 'http://dm_pc.com/img/common/logo_fang.png';
$resetLink = getUrl(Yii::$app->params['urlPc'].'/public/check_email', ['verify_code' => $verifyCode]);
$logoUrl = Yii::$app->params['urlPc'].'/img/common/logo_fang.png';
$email = 'kefu@damailicai.com';
?>

<div style="width:800px; margin:0 auto;color:#666;font:15px/30px 'lucida Grande',Verdana,'Microsoft YaHei';">
    <p style="width:100%;height:30px;text-align:center;font-size:13px;color:#fff;background:#c0c0c0;margin:0;">
        为了您能够正常收到来自大麦理财的优惠信息和会员邮件，请将 <?= Html::encode($email) ?> 添加进您的通讯录</p>
    <div style="background:#eaeaea;padding:5px 10px;position:relative;">
        <div><a href="https://www.damailicai.com" target="_bank">
                <img src="<?= Html::encode($logoUrl) ?>" border="0" alt="大麦理财Logo" /></a></div>
        <img src="https://www.damailicai.com/style/public/images/mascot1.png" alt="大麦吉祥物" style="width:209px;height:280px;border:0;position:absolute;top:40px;right:40px;"/>
        <p style="font-size:16px;color:#ff9900;margin:0 0 0 10px;">深圳大麦理财互联网金融服务有限公司</p>
        <p style="font-size:16px;margin:15px 0 0 25px;">麦粉&nbsp;<span style="color:#ff9900;"><?= Html::encode($userName) ?></span>&nbsp;你好：</p>
        <div style="margin:5px 0 0 30px;">
            <p style="margin:0 0 0 32px;color:#808080;">感谢您注册大麦理财理财平台</p>
            <p style="margin:10px 0 10px 32px;color:#000;font-size:18px;">完成邮箱激活，开启您的财富之旅！</p>
            <a href="<?= Html::encode($resetLink) ?>" target="_bank" style="display:block;width:120px;text-align:center;margin:15px 0 0 32px;text-decoration:none;color:#000;background:#ffcc00;border:1px #000 solid;border-radius:5px;letter-spacing:2px;">点击激活</a>
        </div>
        <p style="margin:15px 0 2px 10px;">如果点击无效，可复制下方网页地址到浏览器地址栏中打开：</p>
    </div>

    <div style="border:3px solid #808080;padding:20px 20px 15px;margin:5px 0;">
        <p style="margin:0 15px 15px;"><?= Html::a(Html::encode($resetLink), $resetLink) ?></p>
        <p style="margin:10px 0;text-align:center;">如有疑问请致电大麦客服热线：<a href="tel:4000822188" style="color:#ff0000;">400-0822-188</a>&nbsp;或咨询
            <a href="http://www.365webcall.com/chat/ChatWin3.aspx?settings=mw7mbbbmIw7PwXz3APwNXPz3AI7m6mz3Am6mmXX" target="_blank" style="color:#ff9966;">在线客服</a></p>
        <p style="margin:0;">大麦理财邮件中心<br /><?= date('Y-m-d',time()) ?></p>
        <p style="margin:0;font-size:13px;">大麦理财专注于中国实体企业融资服务，现金通过第三方资金管理机构开设独立专管账户进行严格托管。以透明、公开、平等、分享为服务宗旨；真诚为社会大众提供个人理财服务，关怀客户、重视客户体验，力求实现普惠金融。</p>
    </div>
    <p style="width:790px;font-size:13px;background:#eaeaea;margin:0;padding-left:10px;">
        此邮件为系统邮件，请勿回复。可回复邮件至：<?= Html::encode($email) ?></p>
</div>
