<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/13 0013
 * Time: 下午 3:21
 */
namespace manage\controllers;

use Yii;
class OrderController extends CommonController
{
    public function actions() {
        return [
            //错误页面
            'error' => [
                'class' => 'yii\web\ErrorAction',
                'view' => '404.html'
            ],
        ];
    }

    //借款信息
    public function actionBorrowlist()
    {
        return $this->render('borrowList.html',[]);
    }

    public function actionBorrowjson()
    {
        $session=Yii::$app->session;
        $userInfo=$session->get('userInfo');
        $activityObj = new \common\lib\Design\EntrustPattern\RenderData('\common\lib\Design\EntrustPattern\BorrowInfoBase', '');
        $list=$activityObj->singleRenderData('borrowerList',['merchant_id'=>$userInfo['merchant_id']]);
        $this->layuiJson($list);
    }

    //还款信息
    public function actionBorrowcollectionlist()
    {
        return $this->render('borrowCollection.html',[]);
    }

    public function actionBorrowcollectionjson()
    {
        $session=Yii::$app->session;
        $userInfo=$session->get('userInfo');
        $activityObj = new \common\lib\Design\EntrustPattern\RenderData('\common\lib\Design\EntrustPattern\BorrowInfoBase', '');
        $list=$activityObj->singleRenderData('borrowCollectionList',['merchant_id'=>$userInfo['merchant_id']]);
        $this->layuiJson($list);
    }

    /**
     * 放款信息列表
     * @return string
     */
    public function actionBorrowLendPayList()
    {
        return $this->render('borrowLendPayList.html',[]);
    }

    /**
     * 放款信息数据
     */
    public function actionBorrowLendPayJson()
    {
        $activityObj = new \common\lib\Design\EntrustPattern\RenderData('\common\lib\Design\EntrustPattern\BorrowInfoBase', '');
        $list=$activityObj->singleRenderData('borrowLendPayList',['merchant_id'=>$this->user_info['merchant_id']]);
        foreach ($list['list'] as & $value) {
            $value['accountId'] = $this->user_info['accountId'];
        }
        $this->layuiJson($list);
    }

    //表单回调测试
    public function actionResponse()
    {
        $request=Yii::$app->request;
        if($request->isPost)
        {
            return $this->ajaxRe(['error'=>2]);
        }else
        {
            return $this->render('responese.html',[]);
        }
    }
}