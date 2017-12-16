<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace common\events;

use Yii;
use yii\base\Model;
use common\models\User;
/**
 * Description of LoginEvent
 *
 * @author Administrator
 * @datetime 2017-2-23 17:17:53
 */
class RegisterEvent extends Model{

    //用户登录后触发的事件
    public function afterRegister() {
        //插入扫码注册用户表
            $info['user_id'] = $_rs['user_id'];
            $info['source_version'] = terminal();
            $info['ip'] = $_user['addip'];
            $info['addtime'] = $_user['addtime'];
            if (isset($_user['pop_uid']) && $_user['pop_uid']) {
                $pop = $this->uuser_model->_detailTable("user", "auser", "user_id = " . $_user['pop_uid']);
                $_info['source_code'] = $info['source_code'] = $pop['auser'];
            }
            $this->uuser_model->_saveTable('experience_user', $info);

            // 更新扫码记录表
            $_info['reg_status'] = 1;
            $this->uuser_model->_updateTable('experience_log', "phone = " . $_user['real_phone'], $_info);

            //分配客服
            $this->uuser_model->get_kefu_adduser($_rs['user_id']);

            //注册就送10000体验金和880元的
            $this->uuser_model->register_new_red($_rs['user_id']);
            $this->uuser_model->get_experience_money($_rs['user_id']);


            //发站内信
            $_msg['user_id'] = $_rs['user_id'];
            $_msg['m_title'] = '信息提示：平台注册成功';
            $_msg['m_content'] = '感谢您注册本平台，您的用户名为' . $_rs['auser'] . '。';
            $_msg['send_user_id'] = 0;
            $_msg['addtime'] = time();
            $this->uuser_model->send_msg($_msg);
            $phone_time = time();
            $this->uuser_model->_saveTable('user_identification', array("phone_time" => $phone_time, 'user_id' => $_rs['user_id']));
            
            $this->yrt_callback($_rs['user_id']);
        $lastip = Yii::$app->getRequest()->getUserIP();
        $lasttime = time();
        //发站内信
        $user_id = Yii::$app->user->id;
        $_msg['user_id'] = $user_id;
        $_msg['m_title'] = '信息提示：账户登录信息';
        $_msg['m_content'] = '上次登录ip：' . $lastip . '；上次登录时间：' . date('Y-m-d H:i:s', $lasttime);
        $_msg['send_user_id'] = 0;
        $_msg['addtime'] = time();
        $mssage = new \common\models\Message();
        $mssage->sendOne($_msg);
        //更新登录信息
        $user = User::findOne($user_id);
        $user->lastip = $lastip;
        $user->lasttime = $lasttime;
        $user->login_source = 0;
        $user->save();
    }
}
