<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace api\controllers;

use api\handler\base\ApiErrorMsgText;
use yii\web\Controller;

/**
 * Description of MyController
 * @author Administrator
 */
class MyController extends Controller {

    /**
     * 分配
     * @param int $retCode
     * @param string $retMsg
     * @param array $retData
     */
    private function dispatch($retCode = 0, $retMsg = '', $retData = []) {
        $data = array(
            'retCode' => $retCode, //必选。状态码(ok = 0, error = 1)
            'retMsg' => $retMsg, //可选。信息内容。
            'retData' => $retData, //可选。跳转到某个url。
        );
        $this->ajaxRe($data);
    }

    protected function json_success($retData = [], $retMsg = '成功') {
        $this->dispatch(0, $retMsg, $retData);
    }

    protected function json_error($retCode = 11001, $retMsg = ApiErrorMsgText::ERROR_11001, $retData = []) {
        $this->dispatch($retCode, $retMsg, $retData);
    }

    /**
     * ajax返回数据
     * @param $data
     * @return void
     */
    protected function ajaxRe($data) {
        header('Content-Type:application/json; charset=utf-8');
        exit(json_encode($data));
    }

    protected function url_redirect( $url, $message = '' ){
        if( $message )
            echo "<script>alert('" . $message . "')</script>";
        echo "<script>window.location='" . $url . "'</script>";
    }

    function convertUrlQuery($query)
    {
        $queryParts = explode('&', $query);
        $params = array();
        foreach ($queryParts as $param) {
            $item = explode('=', $param);
            $params[$item[0]] = $item[1];
        }
        return $params;
    }
}
