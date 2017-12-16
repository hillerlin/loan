<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace common\models;

use common\models\DmActiveRecord;
use Yii;

class EnterUsers extends DmActiveRecord {

    public $errorMsg = '';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%enter_users}}';
    }

    //查找担保列表
    public static function getList( $where = 'guarantee = 1' ) {
        $result = self::find()
                ->select('id, name')
                ->where($where)
                ->asArray()
                ->all();
        return $result;
    }

    public function saveOrganize( $id, $info, $createUser, $userInfo ){
        $db = Yii::$app->db;
        $userModel = new User();
        if ($createUser) {
            unset($info['user_id']);
            $valid = $this->validUserInfo($userInfo);
            if( $valid === -1 ){
                return $valid;
            }
            $user = [
                'auser' => $userModel->generateUserName(),
                'apwd' => md5($userInfo['real_phone']),
                'real_status' => 2,
                'real_name' => $info['name'],
                'card_status' => time(),
                'real_card' => $userInfo['real_card'],
                'phone_status' => 2,
                'real_phone' => $userInfo['real_phone'],
                'gtuser' => $userInfo['gtuser'],
                'addtime' => time(),
                'accountId' => $userInfo['accountId'],
            ];
            $transaction = $db->beginTransaction();
            if(! $db->createCommand()->insert( User::tableName(), $user)->execute()){
                $transaction->rollBack();
                $this->errorMsg = '数据库繁忙';
                return - 1;
            }
            $userId = $db->getLastInsertID();
            $info['user_id'] = $userId;
            $account = new Account();
            //开账户
            if (!$account->addUser(['user_id' => $userId])) {
                $transaction->rollBack();
                $this->errorMsg = '数据库繁忙';
                return - 1;
            }
            $account_banck = new \common\models\AccountBank();
            $bankInfo = [
                'bank_card' => $userInfo['bid_card'],
                'real_name' => $info['name'],
                'user_id' => $userId,
            ];
            if($account_banck->setDefaultBankCard($bankInfo) === false){
                $transaction->rollBack();
                $this->errorMsg = '数据库繁忙';
                return - 1;
            }
            $transaction->commit();
        }else{
            if ($info['user_id']) {
                $oldInfo = $userModel->getUserInfo($info['user_id']);
                $valid = $this->validUserInfo($userInfo, $oldInfo);
                if( $valid === -1 ){
                    return $valid;
                }
                $user = [
                    'real_phone' => $userInfo['real_phone'],
                    'real_name' => $info['name'],
                    'gtuser' => $userInfo['gtuser'],
                    'accountId' => $userInfo['accountId'],
                    'real_card' => $userInfo['real_card'],
                ];
                if( $db->createCommand()->update( User::tableName(), $user, ['user_id' => $info['user_id']] )->execute() === false){
                    $this->errorMsg = '数据库繁忙';
                    return - 1;
                }
                if ($userInfo['bid_card']!=$oldInfo['bank_card']) {
                    $account_banck = new \common\models\AccountBank();
                    $bankInfo = [
                        'bank_card' => $userInfo['bid_card'],
                        'real_name' => $info['name'],
                        'user_id' => $info['user_id'],
                    ];
                    if($account_banck->setDefaultBankCard($bankInfo) === false){
                        $this->errorMsg = '数据库繁忙';
                        return - 1;
                    }
                }
            }
        }
        $info = $this->validInfo( $info );
        if( $info === -1 ){
            return $info;
        }
        if($id){
            if( $db->createCommand()->update( self::tableName(), $info, ['id' => $id] )->execute() === false ){
                $this->errorMsg = '数据库繁忙';
                return - 1;
            }
        }else{
            if( ! $db->createCommand()->insert( self::tableName(), $info )->execute() ){
                $this->errorMsg = '数据库繁忙';
                return - 1;
            }
        }
        return true;
    }

    /**
     * 验证企业信息
     * @param $info
     * @return int
     */
    public function validInfo($info ){
        if( $info['userType'] == 1 && ! ( $info['agentName'] && $info['agentIdNo'] ) ){
            $this->errorMsg = '当注册类型为 代理人注册 时，代理人信息必填';
            return - 1;
        }
        if( $info['userType'] == 2 && ! ( $info['legalName'] && $info['legalIdNo'] ) ){
            $this->errorMsg = '当注册类型为 法人注册 时，法人信息必填';
            return - 1;
        }
        if( isset($info['user_id']) && $info['user_id'] ){
            $userAcc = User::getAccountIdByUid( $info['user_id'] );
            if( !(isset( $userAcc['accountId'] ) && $userAcc['accountId']) ){
                $this->errorMsg = '关联的用户必须开通存管账户';
                return - 1;
            }
        }
        return $info;
    }

    /**
     * 验证新增用户的信息
     * @param $userInfo
     * @param array $oldInfo
     * @return int
     */
    public function validUserInfo($userInfo, $oldInfo=[] )
    {
        if (!($userInfo['real_phone'] && $userInfo['accountId'] && $userInfo['real_card'] && $userInfo['bid_card'])) {
            $this->errorMsg = '必填字段不能为空';
            return - 1;
        }
        if ($oldInfo) {
            if ($userInfo['real_phone']!=$oldInfo['real_phone'] && User::findByPhone($userInfo['real_phone'])) {
                $this->errorMsg = '手机号已被注册';
                return - 1;
            }
            if ($userInfo['accountId']!=$oldInfo['accountId']){
                $count = User::find()
                    ->where(['accountId' => $userInfo['accountId']])
                    ->count();
                if ($count>0) {
                    $this->errorMsg = '电子账号已被使用';
                    return - 1;
                }
            }
        }else{
            if (User::findByPhone($userInfo['real_phone'])) {
                $this->errorMsg = '手机号已被注册';
                return - 1;
            }
            $count = User::find()
                ->where(['accountId' => $userInfo['accountId']])
                ->count();
            if ($count>0) {
                $this->errorMsg = '电子账号已被使用';
                return - 1;
            }
        }

        return $userInfo;
    }

    /**
     * 返回企业机构列表
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public static function getIndexList($page = 1, $pageSize = 30 ){
        $order = 'eu.id desc';
        $offset = self::pageToLimit( $page, $pageSize );
        $list = self::find()
                    ->alias( 'eu' )
                    ->select( 'eu.*, u.accountId' )
                    ->leftJoin( 'xx_user u', 'u.user_id = eu.user_id' )
                    ->orderBy( $order )
                    ->limit( $pageSize )
                    ->offset( $offset )
                    ->asArray()
                    ->all();
        $total = self::find()
                     ->count();

        return [
            'list' => $list,
            'total' => $total,
        ];

    }
    
    /**
     *  根据id获取担保账户信息
     * @param type $id 
     * @param type $info 要查询的信息
     * @return type
     */
    public static function getGuaranteeInfoById($id, $info = '*') {
        return self::find()->select($info)
                ->from(self::tableName() . ' AS eu')
                ->leftJoin('{{%user}} as u', 'u.user_id=eu.user_id')
                ->where("eu.id=$id")
                ->asArray()
                ->one();
    }
    
    /**
     *  根据borrow_id获取担保账户信息
     * @param type $bid 
     * @param type $info 要查询的信息
     * @return type
     */
    public static function getGuaranteeInfoByBId($bid, $info = '*') {
        return self::find()->select($info)
                ->from(self::tableName() . ' AS eu')
                ->leftJoin('{{%borrow}} as b', 'b.receipt_id=eu.id')
                ->leftJoin('{{%user}} as u', 'u.user_id=eu.user_id')
                ->where('bid=:bid')
                ->params([':bid' => $bid])
                ->asArray()
                ->one();
    }

    /**
     * 根据企业用户的user_id查询手机号
     * @param $userId
     * @return mixed|string
     */
    public static function getMobileByUserId($userId)
    {
        $info = self::find()->select('mobile')->where(['user_id' => $userId])->asArray()->one();
        return isset($info['mobile']) ? $info['mobile'] : '';
    }
    //插入本库模块
    public static function inserSelf($info)
    {
        $infos = [
            'user_id' => '0',
            'mobile' => trim( $info['inputProMobile']),
            'email' => trim( $info['inputEmail'] ),
            'name' => trim( $info['inputProName'] ),
            'organType' => '0',
            'regType' => '1',
            'organCode' => trim($info['inputOrganizationCode']),
            'regCode' => $info['inputOrganizationRegNo'],
            'userType' => '1',
            'agentName' => '',
            'agentIdNo' => '0',
            'legalName' => trim($info['legalName']),
            'legalIdNo' => trim($info['legalIdNo']),
            'legalArea' => '0',
            'sealData' => json_encode(['BusinessLicense'=>$info['BusinessLicense'],'TaxRegistrationCertificate'=>$info['TaxRegistrationCertificate'],'organizationRegImg'=>$info['organizationRegImg'],'ApplicationForSigning'=>$info['ApplicationForSigning']]),
            'hText' => '',
            'qText' => '',
            'guarantee' => '0',
            'comaddr' => $info['inputProAddr'],
            'keys' => '电子签章企业',
        ];
       $db=Yii::$app->db;
       $insertinto=$db->createCommand()->insert(self::tableName(),$infos)->execute();
       return $insertinto;
    }
    /**
     * Finds user by username
     *
     * @param string $phone
     * @return static|null
     */
    public static function findByPhone($phone) {
        return static::findOne(['mobile' => $phone]);
    }
    
    /**
     * 根据组织机构代码查询企业信息
     * @param type $organCode
     * @param type $select
     * @return type
     */
    public static function findByOrganCode($organCode, $select = '*') {
        return self::find()->select($select)->where('organCode=:organCode', [':organCode' => $organCode])->asArray()->one();
    }
    
    /**
     *  根据id获取企业账户信息
     * @param type $id 
     * @param type $info 要查询的信息
     * @return type
     */
    public static function getInfoById($id, $info = '*') {
        return self::find()->select($info)
                ->where("id=$id")
                ->asArray()
                ->one();
    }
}
