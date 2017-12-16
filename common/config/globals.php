<?php

/**
 * This is the shortcut to Yii::$app
 */
function app() {
    return Yii::$app;
}

/**
 * This is the shortcut to Yii::$app->request.
 */
function request() {
    return Yii::$app->request();
}

/**
 * This is the shortcut to Yii::$app->createUrl()
 */
function URL($route, $scheme = false) {
    return \yii\helpers\Url::toRoute($route, $scheme);
}

/**
 * Returns the named application parameter.
 * This is the shortcut to Yii::$app->params[$name].
 */
function param($name) {
    return Yii::$app->params[$name];
}

//判断数据是否有小数，有根据参数保留位数，没有则不显示小数位
function my_number_format($number, $decimals) {
    $number = $number / 10000;
    if (floor($number) === $number) {
        return $number;
    } else {
        return number_format($number, $decimals);
    }
}

/**
 * 去掉所有html的标签
 * @param type $content
 * @return type
 */
function get_string_replace($content) {
    $content = str_replace("\n", '', $content);
    $content = str_replace("\r", '', $content);
    $content = preg_split('/<[^>]+>/iU', $content);
    return implode('', $content);
}

/**
 * 分配
 * @param type $statusCode
 * @param type $message
 * @param type $jumpUrl
 * @return void
 */
function dispatch($statusCode = 0, $message = '', $jumpUrl = '') {
    $data = array(
        'statusCode' => $statusCode, //必选。状态码(ok = 0, error = 1)
        'message' => $message, //可选。信息内容。
        'forward' => $jumpUrl, //可选。跳转到某个url。
    );
    ajaxRe($data);
}

function json_success($message = '', $jumpUrl = '') {
    dispatch(0, $message, $jumpUrl);
}

function json_error($message = '', $jumpUrl = '') {
    dispatch(1, $message, $jumpUrl);
}

/**
 * ajax返回数据
 * @param type $data 要返回的数据
 * @return void
 */
function ajaxRe($data) {
    header('Content-Type:application/json; charset=utf-8');
    exit(json_encode($data));
}

function NumToCNMoney($num,$mode = true,$sim = true){
    if(!is_numeric($num)) return '含有非数字非小数点字符！';
    $char    = $sim ? array('零','一','二','三','四','五','六','七','八','九')
        : array('零','壹','贰','叁','肆','伍','陆','柒','捌','玖');
    $unit    = $sim ? array('','十','百','千','','万','亿','兆')
        : array('','拾','佰','仟','','萬','億','兆');
    $retval  = $mode ? '元':'点';
    //小数部分
    if(strpos($num, '.')){
        list($num,$dec) = explode('.', $num);
        $dec = strval(round($dec,2));
        if($mode){
            if($dec==0)
            {
                $retval.='整';
            }else
            {
                $retval .= "{$char[$dec['0']]}角{$char[$dec['1']]}分";
            }
        }else{
            for($i = 0,$c = strlen($dec);$i < $c;$i++) {
                $retval .= $char[$dec[$i]];
            }
        }
    }
    //整数部分
    $str = $mode ? strrev(intval($num)) : strrev($num);
    for($i = 0,$c = strlen($str);$i < $c;$i++) {
        $out[$i] = $char[$str[$i]];
        if($mode){
            $out[$i] .= $str[$i] != '0'? $unit[$i%4] : '';
            if($i>1 and $str[$i]+$str[$i-1] == 0){
                $out[$i] = '';
            }
            if($i%4 == 0){
                $out[$i] .= $unit[4+floor($i/4)];
            }
        }
    }
    $retval = join('',array_reverse($out)) . $retval;
    return $retval;
}

function getCrmConfigType($key,$value)
{
    $params=Yii::$app->params;
    return $params[$key][$value];
}
function getCrmInfo($id)
{
    $sql="select follow_user_id,addtime from xx_customer_msg where id=$id";
    $help=new \admin\lib\Helps();
    return $help::createNativeSql($sql)->queryOne();
}
function formatCrmCustomer($list)
{
   foreach ($list as $key=>&$value)
   {
       if($value['follow_last_id'])
       {
           $customerInfo=getCrmInfo($value['follow_last_id']);
           $value['last_follow_time']=$customerInfo['addtime'];
           $value['follow_user']=$customerInfo['follow_user_id'];
       }else
       {
           $value['last_follow_time']='';
           $value['follow_user']='';
       }

   }
   return $list;
}
function getCountCustomer($userId)
{
    $sql="select count(id) as _count from xx_customer_msg where ident_id=$userId";
    $help=new \admin\lib\Helps();
    $count=$help::createNativeSql($sql)->queryOne();
    return $count['_count']>0?$count['_count']:1;
}

function getMobileProvince($mobile)
{
    if(preg_match("/^1[34578]{1}\d{9}$/",$mobile)){
        $curl=new \admin\lib\AsynReturn();
        $curl->init('http://tcc.taobao.com/cc/json/mobile_tel_segment.htm?tel='.$mobile,[]);
        $mobileInfo=$curl->request_post();
        preg_match("/province:\'(.*)\',/",$mobileInfo,$match);
        if($match[1]=="广东")
        {
            return $mobile;
        }else
        {
            return '0'.$mobile;
        }

    }else{
        return 0;
    }

}
//计算一个月有多少天
function  getTotalDayFromMonth($startUnix)
{
    $month = date('m', strtotime($startUnix));
    $year = date('Y', strtotime($startUnix));
    $nextMonth = (($month+1)>12) ? 1 : ($month+1);
    $year      = ($nextMonth>12) ? ($year+1) : $year;
    $string=mktime(0,0,0,$nextMonth,0,$year);
    $days   = date('d',$string);
    return $days;
}
//计算两个时间间隔多少天
function getDaysFromInterval($startTime,$endTime)
{
    return ($startTime-$endTime)/(24*3600);
}
//计算两个时间间隔年月日，时分秒
function diffDate($date1,$date2)
{
    $datetime1 = new DateTime(date('Y-m-d H:i:s',$date1));
    $datetime2 = new DateTime(date('Y-m-d H:i:s',$date2));
    $interval = $datetime1->diff($datetime2);
    $time['y']         = $interval->format('%Y');
    $time['m']         = $interval->format('%m');
    $time['d']         = $interval->format('%d');
    $time['h']         = $interval->format('%H');
    $time['i']         = $interval->format('%i');
    $time['s']         = $interval->format('%s');
    $time['a']         = $interval->format('%a');    // 两个时间相差总天数
    return $time;
}
function betweenCollection($startTime,$endTime,$borrowType='0')
{
    $startTime=strtotime($startTime);
    $endTime=strtotime($endTime);
    switch ($borrowType)
    {
        case 1:
              $where=" b.borrow_type in(1,2,3,4)";
            break;
        case 2:
            $where=" b.borrow_type in(2,4)";
            break;
        case 3:
            $where=" b.borrow_type in(1,3)";
            break;
        case 4:
            $where="b.borrow_type in(8)";
            break;
        case 5:
            $where="b.borrow_type in(5,6,7,9)";
            break;
        default:
            $where='1';
            break;
    }
    return "select SUM(c.repay_account) as account,SUM(c.repay_interest) as interest,FROM_UNIXTIME($startTime,'%Y/%m/%d') as starttime,FROM_UNIXTIME($endTime,'%Y/%m/%d') as endtime 
             from xx_borrow as b left join xx_borrow_collection as c on c.borrow_id=b.bid
             where c.repay_time >=$startTime and $endTime>c.repay_time and $where
             UNION ALL  ";

}


function betweenIncome($startTime,$endTime,$borrowType='0')
{

    $startTime=strtotime($startTime);
    $endTime=strtotime($endTime);
    return "select SUM(money) as totalmoney, FROM_UNIXTIME($startTime,'%Y/%m/%d') as starttime,FROM_UNIXTIME($endTime,'%Y%m%d') as endtime 
             from xx_borrow_tender
             where addtime >=$startTime and $endTime>addtime
             UNION ALL  ";
}

function cashDaySub($startTime,$endTime,$startPage='0',$pageNum='50',$borrowType='0')
{
    switch ($borrowType)
    {
        case 1:
            $where=" b.borrow_type in(1,2,3,4)";
            break;
        case 2:
            $where=" b.borrow_type in(2,4)";
            break;
        case 3:
            $where=" b.borrow_type in(1,3)";
            break;
        case 4:
            $where="b.borrow_type in(8)";
            break;
        case 5:
            $where="b.borrow_type in(5,6,7,9)";
            break;
        default:
            $where='1';
            break;
    }
    return "select SUM(c.repay_account) as account,SUM(c.repay_interest) as interest,FROM_UNIXTIME(c.repay_time,'%Y/%m/%d') as _time
             from xx_borrow as b left join xx_borrow_collection as c on c.borrow_id=b.bid
             where c.repay_time >=$startTime and $endTime>c.repay_time and $where group by date(FROM_UNIXTIME(c.repay_time,'%Y%m%d')) limit $startPage,$pageNum ";
}

function betweenIncomeday($startTime,$endTime,$startPage='0',$pageNum='50')
{
    return "select SUM(money) as totalmoney, FROM_UNIXTIME(addtime,'%Y/%m/%d') as _time
             from xx_borrow_tender
             where addtime >=$startTime and $endTime>addtime group by date(FROM_UNIXTIME(addtime,'%Y%m%d')) limit $startPage,$pageNum";
}


function commonReportForm($startMonth,$startYear,$endYear,$days,$borrowType,$funciton)
{
    $sql='';
    if(12-$startMonth>=$days['m'])//开始时间和结束时间在同一年
    {
        for($ii=$startMonth;$ii<=$days['m']+$startMonth;$ii++)
        {
            $_startTime=$startYear.'-'.$ii.'-1 0:0:0';
            $_centerTime=$startYear.'-'.$ii.'-16 0:0:0';
            $_endTime=$endYear.'-'.$ii.'-'.getTotalDayFromMonth($_startTime).' 23:59:59';
            $sql.=$funciton($_startTime,$_centerTime,$borrowType).$funciton($_centerTime,$_endTime,$borrowType);
        }
    }
    else
    {
        $netYestMoths=$days['m']-(12-$startMonth);
        for($jj=$startMonth;$jj<=12;$jj++)
        {
            $_startTime=$startYear.'-'.$jj.'-1 0:0:0';
            $_centerTime=$startYear.'-'.$jj.'-16 0:0:0';
            $_endTime=$startYear.'-'.$jj.'-'.getTotalDayFromMonth($_startTime).' 23:59:59';
            $sql.=$funciton($_startTime,$_centerTime,$borrowType).$funciton($_centerTime,$_endTime,$borrowType);
        }
        for($oo=1;$oo<=$netYestMoths+1;$oo++)
        {
            $__startTime=$endYear.'-'.$oo.'-1 0:0:0';
            $__centerTime=$endYear.'-'.$oo.'-16 0:0:0';
            $__endTime=$endYear.'-'.$oo.'-'.getTotalDayFromMonth($__startTime).' 23:59:59';
            $sql.=$funciton($__startTime,$__centerTime,$borrowType).$funciton($__centerTime,$__endTime,$borrowType);
        }

    }
    return $sql;
}