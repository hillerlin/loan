<?php

namespace manage\controllers;

use common\lib\Helps;
use Yii;
use yii\web\Controller;
use yii\helpers\Url;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class CommonController extends Controller {

    public $pageDefaultSize;
    protected $mainModel;
    protected $is_supper;
    protected $is_pmd_boss;
    protected $is_boss;

    protected $user_info;

    public function beforeAction($action) {
        $this->check_login();
       return parent::beforeAction($action);
    }

    //检查权限
    public function checkAuth()
    {
        $adminInfo=$this->getAdminInfo();
        $redis=Yii::$app->redis;
        $url=Yii::$app->request->url;
        $addString=strstr($url,'?type')==false?'':'?type='.$_GET['type'];
        $controller=Yii::$app->controller->id;
        $actionName=Yii::$app->controller->action->id.$addString;
        startCheck:
        $cacheControllerNames=$redis->GET('controllerNames:'.$adminInfo['admin_id']);
        $cacheActionNames=$redis->GET('actionNames:'.$adminInfo['admin_id']);
        if($cacheControllerNames && $cacheActionNames)
        {
             if(!in_array($controller,json_decode($cacheControllerNames,true)) || !in_array($actionName,json_decode($cacheActionNames,true)))
             {
                 $this->json_error('你没有权限！');
             }else
             {
                 return true;
             }

        }
        else
        {
            //缓存不存在
            $roleAuthIds=$adminInfo['role_auth_ids'];
            $sql="select module_name,action_name from xx_menu where menu_id in ($roleAuthIds) and module_name!='' and action_name!=''";
            $list=Yii::$app->db->createCommand($sql)->queryAll();
            //默认加载的权限，每个账号都需要有
            $module_name=array_merge(array_column($list,'module_name'),['index']);
            $action_name=array_merge(array_column($list,'action_name'),['index-layout']);
            $redis->SET('controllerNames:'.$adminInfo['admin_id'],json_encode($module_name));
            $redis->SET('actionNames:'.$adminInfo['admin_id'],json_encode($action_name));
            goto startCheck;
        }
    }

    public function check_login() {
        $actionId = Yii::$app->controller->action->id;
        $controllerId = Yii::$app->controller->id;
        $route = $controllerId . '/' . $actionId;
        if (!in_array($route, array('login/index', 'login/logout', 'login/captcha'))) {
            if (Yii::$app->user->getIsGuest()) {
                $this->redirect(Url::toRoute(['/login']));
                Yii::$app->end();
            }
            //权限检查
           // $this->checkAuth();
        }
        $session=Yii::$app->session;
        $this->user_info=$session->get('userInfo');
    }

    protected function getAdminInfo($cache=false)
    {
        $session=Yii::$app->session;
        if(!$cache)
        {
            $realPhone=$session->get('userInfo')['real_phone'];
            $sql = <<<SQL
SELECT u.real_phone,u.real_card,u.auser,u.apwd,u.real_name,u.accountId,u.set_pwd,u.auto_bid,u.accountId,
        eu.name AS companyName,eu.organType,eu.legalName,
        lm.*
FROM  xx_user as u 
LEFT JOIN xx_enter_users as eu on eu.user_id=u.user_id
LEFT JOIN xx_loan_merchant as lm on lm.user_id=u.user_id
where u.real_phone={$realPhone}
SQL;
            $userInfo= Helps::createNativeSql($sql)->queryOne();
            return $userInfo;
        }
        return $session->get('userInfo');
    }

    /**
     * 规则设置
     * array('action_name' => array('type' => 'XX', 'operation' => 'xxx'))
     * .e.g
     * array('del'  => array('type' => 'project', 'oepration' => Privilege::DEL))
     * @return array
     */
    protected function rules() {
        return array();
    }

    /**
     * 发送数组给前端
     * @param array $data
     * @return void
     */
    protected function sendData($data) {
        $params = array(
            'statusCode' => 200,
            'content' => $data
        );
        $this->ajaxRe($params);
    }

    /**
     * 分配
     * @param int $statusCode
     * @param string $message
     * @param bool $closeCurrent
     * @param string $jumpUrl
     * @param string $forwardConfirm
     * @param array $reload
     * @param string $special
     * @param string $layoutUrl
     * @return void
     */
    public function dispatch($statusCode = 200, $message = '', $closeCurrent = false, $jumpUrl = '', $forwardConfirm = '', $reload = array(),$special='',$layoutUrl='') {
        $data = array(
            'statusCode' => $statusCode,    //必选。状态码(ok = 200, error = 300, timeout = 301)，可以在BJUI.init时配置三个参数的默认值。
            'closeCurrent' => $closeCurrent,        //可选。是否关闭当前窗口(navtab或dialog)。
            'message' => $message,          //可选。信息内容。
            'tabid' => isset($reload['tabid']) ? $reload['tabid'] : '',              //可选。待刷新navtab id，多个id以英文逗号分隔开，当前的navtab id不需要填写，填写后可能会导致当前navtab重复刷新。
            'dialogid' => isset($reload['dialogid']) ? $reload['dialogid'] : '',        //可选。待刷新div id，多个id以英文逗号分隔开，请不要填写当前的div id，要控制刷新当前div，请设置该div中表单的reload参数。
            'divid' => isset($reload['divid']) ? $reload['divid'] : '',              //可选。待刷新div id，多个id以英文逗号分隔开，请不要填写当前的div id，要控制刷新当前div，请设置该div中表单的reload参数。
            'tabName'=>isset($reload['tabName'])?$reload['tabName']:'',//新增属性tab名，用来做局部刷新
            'tabTitle'=>isset($reload['tabTitle'])?$reload['tabTitle']:'',
            'width'=>isset($reload['width'])?$reload['width']:'',
            'height'=>isset($reload['height'])?$reload['height']:'',
            'forward' => $jumpUrl,          //可选。跳转到某个url。
            'layoutUrl'=>$layoutUrl,//刷新底层的页面，弹出框并刷新底层的时候用
            'forwardConfirm' => $forwardConfirm,    //可选。跳转url前的确认提示信息。
            'special'=>$special,//1是普通刷新模式 2是弹出框并刷新底层模式
        );
        $this->ajaxRe($data);
    }

    protected function json_success($message = '', $jumpUrl = '',$forwardConfirm = '', $closeCurrent = false, $reload = array(),$special='',$layoutUrl='') {
        $this->dispatch(200, $message, $closeCurrent, $jumpUrl, $forwardConfirm, $reload,$special,$layoutUrl);
    }

    protected function json_error($message = '', $jumpUrl = '',$forwardConfirm = '', $closeCurrent = false, $reload = array(),$special='',$layoutUrl='') {
        $this->dispatch(300, $message, $closeCurrent, $jumpUrl, $forwardConfirm, $reload,$special,$layoutUrl);
    }

    /**
     * ajax返回数据
     * @param array $data 要返回的数据
     * @return void
     */
    protected function ajaxRe($data) {
        header('Content-Type:application/json; charset=utf-8');
        exit(json_encode($data));
    }

    protected function layuiJson(array $data = [], $code = 0,  $msg = '')
    {
        if (empty($data) || !isset($data['total']) || empty($data['list'])) {
            $_data = ['code' => 1, 'msg' => '暂时没有数据', 'count' => 0, 'data' => ''];
        } else {
            $_data = ['code' => $code, 'msg' =>$msg, 'count' => $data['total'], 'data' => $data['list']];
        }
        $this->ajaxRe($_data);
    }

}
