<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/15 0015
 * Time: 下午 5:47
 */
namespace pc\modules\custody\controllers;
use admin\lib\Helps;
use Yii;
use yii\web\Controller;
use common\lib\elecsign\checkSign;
use common\lib\elecsign\doPostByObjModule;
use common\lib\elecsign\elecSignMain;
use common\lib\elecsign\customFuns\person;
use common\lib\elecsign\customFuns\company;
use common\lib\elecsign\companyElecSign;
use common\lib\elecsign\personElecSign;
class ElecsignController extends controller
{
    public $enableCsrfValidation = false;       //接收外部数据，不做csrf校验
    public function actionCallback()
    {
        $data = Yii::$app->request->post();
/*        $data['applyNo']='APL897707072395808768';
        $data['identityCard']='152201199901013451';*/
        Yii::info('elecsign callback: ' . json_encode($data), 'notify');
        //$checkSign=new checkSign();
        $checkResult=true;//$checkSign->main($data);
        if($checkResult===true)//签名通过
        {
           $sql="select count(id) as total from xx_elecsign where `contract_no`='".$data['applyNo']."'";
           $count=Helps::createNativeSql($sql)->queryOne();
           $orderNum=Helps::createNativeSql("select `order_num` from xx_elecsign where `contract_no`='".$data['applyNo']."' and `identitycard`='".$data['identityCard']."'")->queryOne();
           $updataStatus=Helps::createNativeSql("UPDATE `xx_elecsign` SET `status`='1' WHERE (`contract_no`='".$data['applyNo']."' and `identitycard`='".$data['identityCard']."')")->execute();
           if($count===false || $orderNum===false || $updataStatus===false)
           {
               var_dump('end');die;
               exit;
           }
           if($orderNum['order_num']<$count['total'])
           {
               $nextOrderNum=$orderNum['order_num']+1;
               Yii::info('elecsign orderNum: ' . $nextOrderNum, 'notify');
               $nextNoticePerson=Helps::createNativeSql("select `identify_flag`,`mobile` from xx_elecsign where `contract_no`='".$data['applyNo']."' and `order_num`=$nextOrderNum")->queryOne();
               Yii::info('elecsign update---: ' . $nextNoticePerson, 'notify');
               if(!$nextNoticePerson)
               {
                   var_dump('endtoo');die;
                   exit;
               }else
               {
                   $contractNo=$data['applyNo'];
                   $mobile=$nextNoticePerson['mobile'];
                   $elecSign = new elecSignMain();
                   $userInfo=\common\models\Elecsign::findAllByMobileAndContractNo($mobile,$contractNo);
                   if($userInfo['identify_flag']=='1') {
                       $company = new company();
                       $companyElecSign = new companyElecSign();
                       $companyList = $company->customCompany($userInfo['signperson_name'], $userInfo['signperson_name'], $userInfo['identitycard'], $userInfo['email'], [], ['mobile' => $mobile])->render();
                       $paramsList = $elecSign->testbuild($companyElecSign, $companyList[$userInfo['signperson_name']]);
                   }else
                   {
                       $person = new person();
                       $personList= $person->customPerson(\common\lib\Helps::elecSignMapping($userInfo),$userInfo['identitycard'],[])->render();
                       $personElecSign = new personElecSign();
                       $paramsList= $elecSign->testbuild($personElecSign,$personList[$userInfo['identitycard']]);
                   }
                   $createContractIdObj = new doPostByObjModule();
                   $createContractId = $createContractIdObj->createMessage($paramsList, $contractNo);
                   //var_dump($createContractId);die;

                   if($createContractId['success'])
                   {
                       echo 'success';
                   }
               }
           }
        }



    }
}