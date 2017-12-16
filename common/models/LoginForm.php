<?php

namespace common\models;

use common\lib\Helps;
use Yii;
use yii\base\Model;
use yii\helpers\StringHelper;

/**
 * Login form
 */
class LoginForm extends Model {

    const EVENT_AFTER_LOGIN = 'afterLogin';
    public $img_verify_code;
    public $auser;
    public $apwd;
    public $real_phone;
    public $user_status;
    public $_userInfo;
    public $is_md5 = false;
    public $rememberMe = true;
    private $_user;

    
    public function init() {
        //绑定登录后的处理事件
        $this->on(self::EVENT_AFTER_LOGIN, [$this, 'cacheUserBaseInfo']);
    }
    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            // username and password are both required
            [['real_phone', 'apwd'], 'required', 'requiredValue'=>null, 'message'=>'登录名和密码不能为空'],
            // rememberMe must be a boolean value
            [['rememberMe', 'is_md5'], 'boolean'],
            // password is validated by validatePassword()
            ['apwd', 'validatePassword'],
            //captcha
            ['img_verify_code', 'captcha', 'captchaAction' => '/login/captcha', 'message' => '验证码错误'],
            ['user_status', 'validateUserStatus'],
        ];
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validatePassword($attribute, $params) {
        if (!$this->hasErrors()) {
            $user = $this->getUser();
            if (!$user || !$user->validatePassword($this->apwd, $this->is_md5)) {
                $this->addError($attribute, '账户或密码输入错误');
            }
        }
    }
    
    //判断用户状态
    public function validateUserStatus($attribute, $params = null) {
        if (!$this->hasErrors()) {
            $user = $this->getUser();
            if ($user['user_status'] != 1 && $user['cpc_soure'] == 20) {
                $this->addError($attribute, "用户处于冻结状态!  <a href='/reg/user_act/activate'>请点击激活</a>");
            } else if ($user['user_status'] != 1) {
                $this->addError($attribute, '用户被冻结，请联系客服！');
            }
        }
    }
    
//    public function validate($attributeNames = NULL, $clearErrors = true) {
//        parent::validate($attributeNames = NULL, $clearErrors = true);
//        $this->validateUserStatus('user_status');
//    }

    /**
     * Logs in a user using the provided username and password.
     *
     * @return bool whether the user is logged in successfully
     */
    public function login() {
        if ($this->validate()) {
            $resCookies = Yii::$app->response->cookies;
            $session=Yii::$app->session;
            $session->set('userInfo',$this->_userInfo);
            //Yii::$app->request->enableCookieValidation = false;
            $resCookies->add(new \yii\web\Cookie([
                'name' => 'set_pwd',
                'value' => '2',
                'expire' => time() + 3600*24*365*1000, // 失效时间一年
                'httpOnly' => false                    //允许通过js操作cookie
            ]));
            Yii::$app->response->send();
            return Yii::$app->user->login($this->getUser(), $this->rememberMe ? 3600 * 24 * 30 : 0);
        } else {
            return false;
        }
    }

    //根据用户id登录
    public function autoLogin($user_id) {
        $this->_user = User::findByPhone($user_id);
        return Yii::$app->user->login($this->_user , 3600 * 2);
    }
    
    //根据用户id登录
    public function autoLoginById($user_id) {
        $this->_user = User::findIdentity($user_id);
        return Yii::$app->user->login($this->_user , 3600 * 2);
    }
    
    /**
     * Finds user by [[username]]
     *
     * @return User|null
     */
    protected function getUser() {
        if ($this->_user === null) {
           $userInfo= Helps::createNativeSql("
            select u.real_phone,u.real_card,u.auser,u.apwd,u.real_name,u.accountId,u.set_pwd,u.auto_bid,u.accountId,
                    eu.name as companyName,eu.organType,eu.legalName,
                    lm.*
            from xx_user as u 
            LEFT JOIN xx_enter_users as eu on eu.user_id=u.user_id
            LEFT JOIN xx_loan_merchant as lm on lm.user_id=u.user_id
            where u.real_phone={$this->real_phone}")->queryOne();
            $this->_user = User::findByUsernameOrEmailOrPhone($this->real_phone);
            $this->_userInfo = $userInfo;
        }
        return $this->_user;
    }

}
