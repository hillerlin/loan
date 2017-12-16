<?php

namespace common\models;
use Yii;

class EmailVerify extends DmActiveRecord{

    const STATUS_UNVERIFIED = 0;
    const STATUS_VERIFIED = 1;
    const STATUS_EXPIRED = 2;

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return '{{%email_verify}}';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['email', 'user_id'] , 'require'],
            [['user_id'], 'integer'],
            [['email'], 'email'],
        ];
    }

    /**
     * 发送邮件
     * @param $email
     *
     * @return array
     */
    public function sendEmail( $email ){
        $pattern = "/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i";
        if ( !preg_match( $pattern, $email ) ){
            return ['code'=>12,'msg'=>'邮箱格式不正确'];
        }

        $user_info = Yii::$app->user->getUserCache();
        if( $email == $user_info['real_mail'] ){
            return ['code'=>12,'msg'=>'您已绑定了当前邮箱，无需再次绑定'];
        }
        if(User::findByEmail($email)){
            return ['code'=>12,'msg'=>'邮箱已被绑定过，请勿重复绑定'];
        }

        $userId = $user_info['user_id'];
        $userName = $user_info['auser'];
        $now = time();
        $where = "user_id = $userId and status = :status and endtime > $now";
        $bind = [ ':status' => self::STATUS_UNVERIFIED ];
        $exist = $this->getOneInfo($where,$bind);
        if( $exist ){
            $whereUpdate = [ 'id' => $exist['id']];
            $update = $this->updateInfo( $whereUpdate, ['status'=>self::STATUS_EXPIRED] );
            if( !$update ){
                return ['code'=>13,'msg'=>'数据库繁忙'];
            }
        }
        $verifyCode = $this->getEmailVarifyCode( $userId, $email );
        $insert = [
            'user_id'     => $userId,
            'email'       => $email,
            'verify_code' => $verifyCode,
            'addtime'     => time(),
            'endtime'     => time() + Yii::$app->params['emailVerifyTime'],
        ];
        $res = Yii::$app->db->createCommand()->insert(self::tableName(),$insert)->execute();
        if( $res ){
            return $this->sendVerifyEmail( $verifyCode, $userName, $email );
        }else{
            return ['code'=>13,'msg'=>'数据库繁忙'];
        }

    }

    public function updateInfo( $where, $update ){
        return Yii::$app->db->createCommand()->update(self::tableName(), $update, $where)->execute();
    }

    public function getOneInfo( $where, $bind = [] ){
        return static::find()->select('*')->where($where)->params($bind)->asArray()->one();
    }

    private function getEmailVarifyCode( $user_id, $email ){
        $user_id = md5($user_id); //加密密码
        $email = trim($email); //邮箱
        $add_time = time();

        return md5($user_id.$email.$add_time); //创建用于激活识别码
    }

    private function sendVerifyEmail($verifyCode, $userName, $email){
        $mail = Yii::$app->mailer->compose('emailVerify-html',['verifyCode'=>$verifyCode,'userName'=>$userName]);
        $mail->setTo( $email );
        $mail->setSubject( '邮箱认证' );
        if( $mail->send() ){
            return ['code'=>10,'msg'=>'发送成功'];
        }else{
            return ['code'=>13,'msg'=>'发送失败'];
        }
    }

}