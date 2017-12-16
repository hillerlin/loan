<?php
/**
 * 工具类，存放公用函数
 * Created by PhpStorm.
 * User: lmj
 * Date: 2017/3/9
 * Time: 15:55
 */

namespace common\lib;

use Yii;
use dosamigos\qrcode\QrCode;

class Helps{

	const MOBILE='M';
	const PC='p';
	
	public static function createNativeSql($sql)
	{
	   return	\Yii::$app->db->createCommand($sql);
	}


    //用户ID求余
	public static function _mod($userId)
	{
		$__mod=$userId % 100;
		return $__mod;
	}

	//返回唯一订单号
	public static function uniqOrderId($userId)
	{
		return date('Ymd',time()).$userId.substr(microtime(true),0,-5);
	}


	//查询银行卡归属地
	public static function checkCardInfo($cardNum)
	{
		$url='http://www.cardcn.com/search.php?word='.$cardNum.'&submit=';
		$html=file_get_contents($url);
		$reg='/<font class="con_sub_title">([\x{4e00}-\x{9fa5}]+)\：<\/font>([\x{4e00}-\x{9fa5}]+)<\/dt>/u';
		preg_match_all($reg,$html,$result);
		return $result?$result[2]:false;
	}

    public static function head_info( $title='',$keywords='',$description='' ){
        if(empty($title)){
            $title = "大麦理财";
        }
        if(empty($keywords)){
            $keywords = "大麦理财,P2P理财,P2P,投资理财,P2B理财,长城证券,长城长富,p2p平台排名";
        }
        if(empty($description)){
            $description = "大麦理财（www.damailicai.com）是由上市公司升达林业注资打造的国资理财平台，已完成C轮融资，借款项目直接来源于上市公司。大麦理财为投资理财用户提供13%收益的安全理财产品。";
        }

        return array(
            'title'=>$title,
            'keywords'=>$keywords,
            'description'=>$description
        );
	}

    //格式化时间显示--- 一分钟前  二分钟前  一天前
    public static function foreachTimeFor($list)
    {
        foreach ($list as $k=>$v)
        {
            $list[$k]['addtime']=self::formatTenderTime($v['addtime']);
        }
        return $list;
    }

    //时间搓转化
    public static function formatTime($time){
        $now=time();
        $day=date('Y-m-d',$time);
        $today=date('Y-m-d');

        $dayArr=explode('-',$day);
        $todayArr=explode('-',$today);

//距离的天数，这种方法超过30天则不一定准确，但是30天内是准确的，因为一个月可能是30天也可能是31天
        $days=($todayArr[0]-$dayArr[0])*365+(($todayArr[1]-$dayArr[1])*30)+($todayArr[2]-$dayArr[2]);
//距离的秒数
        $secs=$now-$time;

        if($todayArr[0]-$dayArr[0]>0 && $days>3){//跨年且超过3天
            return date('Y-m-d',$time);
        }else{

            if($days<1){//今天
                if($secs<60)return $secs.'秒前';
                elseif($secs<3600)return floor($secs/60)."分钟前";
                else return floor($secs/3600)."小时前";
            }else if($days<2){//昨天
                $hour=date('h',$time);
                return "昨天".$hour.'点';
            }elseif($days<3){//前天
                $hour=date('h',$time);
                return "前天".$hour.'点';
            }else{//三天前
                return date('m月d号',$time);
            }
        }
    }

    public static function Limit($page,$limit=30)
    {
        if(intval($page)===1 || intval($page)===0)
        {
            return ['start'=>0,'end'=>$limit];
        }else
        {
            return ['start'=>($page-1)*$limit,'end'=>$limit];
        }
    }


    /**
     * 模拟提交参数，支持https提交 可用于各类api请求
     * @param string $url ： 提交的地址
     * @param array|string $data :POST数组
     * @param string $method : POST/GET，默认GET方式
     * @param string $bType
     * @return mixed
     */
    public static function http($url, $data='', $method='GET', $bType = 'json'){
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在
        // curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer

        if($method=='POST'){
            curl_setopt($curl, CURLOPT_POST, count($data)); // 发送一个常规的Post请求
            if ($data != ''){
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
            }
        }

        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        $tmpInfo = curl_exec($curl); // 执行操作
        Yii::info('curl-' . $url . ',request-' . $tmpInfo, 'curl');

        curl_close($curl); // 关闭CURL会话
        if($bType == 'json'){
            return json_decode($tmpInfo,true); // 返回数据
        }else if($bType == 'str'){
            return $tmpInfo;
        }
    }

    public static function getDecodeOrderId($token)
    {
        return substr(base64_decode($token), 4, -4);
    }

    public static function getEncodeOrderId($borrowerId)
    {
        return base64_encode('dmlc' . $borrowerId . 'dmlc');
    }


}