<?php

namespace common\models;

use Yii;
use yii\base\NotSupportedException;
use yii\web\IdentityInterface;
use common\lib\Helps;

/**
 * User model
 *
 * @property integer $user_id
 * @property string $auser
 * @property string $apwd
 * @property integer $real_status
 * @property string $real_name
 * @property integer $card_status
 * @property string $real_card
 * @property string $real_mail
 * @property integer $phone_status
 * @property integer $real_phone
 * @property integer $addtime
 * @property string $addip
 * @property integer $reg_source
 * @property integer $cpc_soure
 * @property string $cpc_soure_cid
 * @property string $accountId
 * @property integer $set_pwd
 */
class User extends DmActiveRecord implements IdentityInterface {

    const LIMIT_WITHDRAW_STATUS = 2;
    const NORMAL_STATUS = 1;

    const REG_SOURCE_LOAN = 6;

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return '{{%user}}';
    }

    /**
     * @inheritdoc
     */
//    public function behaviors() {
//        return [
//            TimestampBehavior::className(),
//        ];
//    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            ['user_id', 'unique'],
            ['auser', 'string', 'length' => [2, 12], 'message' => '用户名长度不符合'],
//            ['status', 'default', 'value' => self::STATUS_ACTIVE],
//            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_DELETED]],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function findIdentity($id) {
        return static::findOne(['user_id' => $id]);
    }


    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null) {
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }

    /**
     * Finds user by mix
     *
     * @param string $usernameOrEmailOrPhone
     * @return static|null
     */
    public static function findByUsernameOrEmailOrPhone($usernameOrEmailOrPhone) {
        if (filter_var($usernameOrEmailOrPhone, FILTER_VALIDATE_EMAIL)) {
            return self::findByUserEmail($usernameOrEmailOrPhone);
        } elseif (self::validatePhone($usernameOrEmailOrPhone)) {
            return self::findByPhone($usernameOrEmailOrPhone);
        }
        return self::findByUsername($usernameOrEmailOrPhone);
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username) {
        return static::findOne(['auser' => $username]);
    }

    /**
     * Finds user by username
     *
     * @param string $mail
     * @return static|null
     */
    public static function findByEmail($mail) {
        return static::findOne(['real_mail' => $mail]);
    }

    /**
     * Finds user by username
     *
     * @param string $phone
     * @return static|null
     */
    public static function findByPhone($phone) {
        return static::findOne(['real_phone' => $phone]);
    }
    
    /**
     * Finds user by username
     *
     * @param string $accountId
     * @return static|null
     */
    public static function findByAccountId($accountId) {
        return static::findOne(['accountId' => $accountId]);
    }

    /**
     * @inheritdoc
     */
    public function getId() {
        return $this->getPrimaryKey();
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey() {
        return $this->user_id;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey) {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * @inheritdoc
     */
    public static function validatePhone($phone) {
        $pregMobile = "/^1[34578]{1}\\d{9}$/";
        return preg_match($pregMobile, $phone);
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @param bool|string $is_md5 password is md5
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password, $is_md5 = false) {
        if ($is_md5 == false) {
            $password = $this->generatePwd($password);
        }
        return strcasecmp($password, $this->apwd) === 0 ? true : false;
    }

    /**
     * 【用】 设置密码
     * @param $mobile
     */
    public function setPassword($mobile) {
        $this->apwd = $this->generatePwd($mobile);
    }

    /**
     * 【用】 生成加密密码
     * @param $mobile
     * @return string
     */
    public function generatePwd($mobile) {
        return md5($mobile);
    }

    /**
     * 【用】 第一步注册
     * This method is used to register new user account.
     * @return bool
     */
    public function register() {
        if ($this->getIsNewRecord() == false) {
            throw new \RuntimeException('Calling "' . __CLASS__ . '::' . __METHOD__ . '" on existing user');
        }

        try {
            $this->addtime = time();
            if (!$this->auser) {
                $this->auser = $this->generateUserName();  //生成用户名
            }
            $this->setPassword($this->real_phone);
            if (!$this->save(false)) {
                return false;
            }
            return true;
        } catch (\Exception $e) {
            \Yii::warning($e->getMessage());
            return false;
        }
    }

    /**
     * 【用】 生成随机用户名
     * @param int $length
     * @param int $length1
     * @return string
     */
    public function generateUserName($length=4, $length1 = 6) {
        $returnStr = '';
        $pattern = 'abcdefghidfjlgdfagaqewweqqqtqtuqtwehfhbvbhkskosdhkfkhsdgkkhaggaiiguyeweqrqwetwioqeuiouweibwfbbiibwvuuerbufvufhzuduiipppopodssdzxsagsajjjklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz';
        for ($i = 0; $i < $length; $i ++) {
            $returnStr .= $pattern {mt_rand(0, 51)};
        }
        $pattern = '093478532847721317232847384547859843574370777071125381232135770155271571775213759235709573429087597234097858934785324095843795439493509230989574397843';
        for ($e = 0; $e < $length1; $e++) {
            $returnStr .= $pattern {mt_rand(0, 51)};
        }
        return $returnStr;
    }

    /**
     * 验证密码是否是当前登录密码
     * @param $userId
     * @param $pwd
     *
     * @return int|string
     */
    public static function checkPwd( $userId, $pwd ){
        $where = ['user_id' => $userId, 'apwd'=> md5($pwd)];
        return self::selectCount( $where );
    }

    /**
     * 修改登录密码
     * @param $userId
     * @param $pwd
     *
     * @return int
     */
    public static function changePwd( $userId, $pwd ){
        $where = ['user_id' => $userId];
        $update = [ 'apwd' => md5( $pwd )];
        return self::updateInfo( $where, $update );
    }

    //查询收货地址
    public static function getAddressById($event)
    {
        $userId=$event->data['user_id'];
        $redis= Yii::$app->redis;
        //不取缓存数据
        if(!$event->data['cache'])
        {
         /*   $userAddressInfo=$redis->GET("user:$userId:Address");
            return json_decode($userAddressInfo,true);*/
            $sql=sprintf("select * from {{%%address}} where `user_id`=%d",$userId);
            $userAddress=Helps::createNativeSql($sql)->queryOne();
            $jsonFormat=$userAddress?json_encode($userAddress):'';
            $redisBack=$redis->SETEX("user:$userId:Address",1800,$jsonFormat);
            return $redisBack?true:'';
        }
    }
    //查询银行卡信息
    public static function checkBankCardInfo($event)
    {
        $redis= Yii::$app->redis;
        $cardId=$event->data['cardId'];
        $userId=$event->data['userId'];
 /*     $sql=sprintf("select `bank_name` from {{%%account_bank_app}} where `user_id`=%d and `card_bid`='%s' and `status`=1",$userId,$cardId);
        $cardInfo=Helps::createNativeSql($sql)->queryOne();*/
        $getRedisValue=$redis->GET("userId:$userId:cardId:$cardId");
        if($getRedisValue)
        {
            return $getRedisValue;
        }else
        {
            $getName=Helps::checkCardInfo($cardId);
            if($getName)
            {
                if(count($getName)>2)
                {
                    $cardName='('.$getName[2].')'.$getName[0].'--'.$getName[1];
                }
                else
                {
                    $cardName='('.$getName[1].')'.$getName[0];
                }

            }else
            {
                $cardName='中国银联';
            }
            $redis->SETEX("userId:$userId:cardId:$cardId",Yii::$app->params['expireCard'],$cardName);
            return $getRedisValue;
        }
    }

   /**
    * 计算所有用户
    * @return type
    */
    public static function countAllUser() {
        return self::find()->count('user_id');
    }

    public static function getNewUsers($count)
    {
        $info = self::find()->select('auser')->orderBy('user_id desc')->limit($count)->asArray()->all();
        return $info;
    }

    public static function getFriendList($userId,$page, $pageSize)
    {
        $page = $page < 1 ? 1 : $page;
        $offset = self::pageToLimit($page,$pageSize);
        $info = self::find()->alias('u')
            ->leftJoin('xx_borrow_tender t','t.user_id = u.user_id')
            ->select('u.real_phone, u.addtime, (case count(t.id) when 0 then 0 else 1 end) is_tender')
            ->where('u.pop_uid = :userId')
            ->params([':userId' => $userId])
            ->groupBy('u.user_id')
            ->orderBy('is_tender asc, u.addtime desc')
            ->limit($pageSize)
            ->offset($offset)
            ->asArray()
            ->all();
        $total =  self::find()->alias('u')
            ->where('u.pop_uid = :userId')
            ->params([':userId' => $userId])
            ->count();
        return ['total' => $total, 'list' => $info];
    }
    //查询是否借款用户
    public static function isBorrower($event)
    {
        $redis=Yii::$app->redis;
        $userId=$event->data['userId'];
        $isUser=$redis->GET('isBorrower:userId:'.$userId);
        if($isUser)
        {
            return $isUser;

        }else
        {
            $sql="select `to_userid` from xx_borrow where `to_userid`=$userId";
            $result=Helps::createNativeSql($sql)->queryOne();
            $result?$redis->SETNX('isBorrower:userId:'.$userId,'1'):$redis->SETNX('isBorrower:userId:'.$userId,'0');
        }
    }
    //根据时间查询投资人总数
    public static function getUsercountByTime($time)
    {
        $sql="";
    }

    /**
     * 是否为企业账户
     * @param type $gtuser
     * @return boolean
     */
    public static function isEnterprise($gtuser) {
        if ($gtuser == 20 || $gtuser == 25) {
            return true;
        }
        return false;
    }
}
