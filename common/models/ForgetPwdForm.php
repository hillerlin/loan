<?php

namespace common\models;

use common\lib\SMSApi;
use Yii;
use yii\base\Model;

/**
 * Login form
 */
class ForgetPwdForm extends Model {

    public $phone_number;
    public $phone_verify_code;
    public $new_pwd;
    public $type;

    const FORGET_PASSWORD_VALID = 'forget_pwd_valid';
    const SET_NEW_PASSWORD = 'set_new_pwd';

    public function scenarios() {
        $scenarios = parent::scenarios();
        $scenarios[self::FORGET_PASSWORD_VALID] = ['phone_number', 'phone_verify_code','type'];
        $scenarios[self::SET_NEW_PASSWORD] = ['phone_number', 'new_pwd',];
        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['phone_number', 'phone_verify_code', 'new_pwd', 'type' ], 'required', 'message' => '不能为空'],
            ['phone_number', 'validatePhoneNumber', 'message' => '手机号错误'],
            ['phone_verify_code', 'validatePhoneVerifyCode', 'message' => '手机验证码错误'],
            ['new_pwd', 'validateNewPwd', 'message' => '密码长度不符'],
        ];
    }

    //验证手机验证码
    public function validatePhoneVerifyCode($attribute, $params = null) {
        if (!$this->hasErrors()) {
            $sms = new SMSApi();
            if(!$sms->verifyPhoneCode($this->phone_number,$this->phone_verify_code,$this->type)){
                $this->addError($attribute, '验证码不正确');
            }
            //if (Yii::$app->session->get('reg_phone_code') != $attribute . $this->phone_number) {
            //    $this->addError($attribute, '验证码不正确');
            //}
        }
    }

    //验证手机验证码
    public function validateNewPwd($attribute, $params = null) {
        if (!$this->hasErrors()) {
            if( !preg_match( '/^[\S]{6,18}$/', $this->new_pwd ) ){
                $this->addError($attribute, '密码长度不符');
            }
        }
    }

    public function validatePhoneNumber( $attribute, $params = null ){
        if( ! $this->hasErrors() ){
            $user_info = Yii::$app->user->getUserCache();
            if( $user_info ){
                if( $this->phone_number != $user_info['real_phone'] ){
                    $this->addError($attribute, '手机号错误!');
                }
            }
            else{
                if( !User::findByPhone( $this->phone_number ) ){
                    $this->addError( $attribute, '手机号尚未注册' );
                }
            }
        }

    }

    public function returnCheck()
    {
        return $this->validate();
    }

}
