<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/12 0012
 * Time: 下午 3:11
 */
namespace common\lib\Design\EntrustPattern;
use manage\models\BorrowCollection;
use manage\models\LoanOrder;
class BorrowInfoBase{

    /**
     * 借款信息列表
     * @param $request
     * @param string|array $params
     * @return array
     */
    public function borrowerList($request, $params='')
    {
        $merchantId=$params['merchant_id'];
        $page=$request->get('page');
        $limit=$request->get('limit','30');
        if($request->isPost)
        {
            /***
            <option value="">--请选择--</option>
            <option value="name">姓名</option>
            <option value="card">身份证</option>
            <option value="orderId">订单号</option>
            ***/
            $startTime=$request->post('startTime');
            $endTime=$request->post('endTime');
            $funcType=$request->post('funcType');//判断类型
            $filter=$request->post('filter');
            $where='';
            if($startTime && $endTime)
            {
                $startTime=strtotime($startTime);
                $endTime=strtotime($endTime);
                $where.=" and lo.addtime>=$startTime and lo.addtime<=$endTime ";
            }
            if($filter)
            {
                if($funcType=='name')
                {
                    $where.=" and u.real_name='$filter'";
                }elseif($funcType=='card')
                {
                    $where.=" and u.real_card='$filter'";
                }elseif($funcType=='orderId')
                {
                    $where.=" and lo.order_id='$filter'";
                }
            }
            return LoanOrder::getOrderListByMerchantId($merchantId,$page,$limit,$where);
        }
        return LoanOrder::getOrderListByMerchantId($merchantId,$page,$limit);
    }

    /**
     * 还款信息列表
     * @param $request
     * @param string|array $params
     * @return array
     */
    public function borrowCollectionList($request, $params='')
    {
        $merchantId=$params['merchant_id'];
        $page=$request->get('page');
        $limit=$request->get('limit','30');
        if($request->isPost)
        {
            /***
            <option value="">--请选择--</option>
            <option value="name">姓名</option>
            <option value="card">身份证</option>
            <option value="orderId">订单号</option>
             ***/
            $startTime=$request->post('startTime');
            $endTime=$request->post('endTime');
            $funcType=$request->post('funcType');//判断类型
            $filter=$request->post('filter');
            $where='';
            if($startTime && $endTime)
            {
                $startTime=strtotime($startTime);
                $endTime=strtotime($endTime);
                $where.=" and lo.addtime>=$startTime and lo.addtime<=$endTime ";
            }
            switch ($funcType) {
                case 'name':
                    $where.=" and u.real_name='$filter'";
                    break;
                case 'card':
                    $where.=" and u.real_card='$filter'";
                    break;
                case 'orderId':
                    $where.=" and lo.borrow_id='$filter'";
                    break;
                default:
                    break;
            }
            return BorrowCollection::getBorrowCollectionListByMerchantId($merchantId,$page,$limit,$where);
        }
        return BorrowCollection::getBorrowCollectionListByMerchantId($merchantId,$page,$limit);
    }

    //还款信息列表

    /**
     * 还款信息列表
     * @param $request
     * @param string|array $params
     * @return array
     */
    public function borrowLendPayList($request, $params='')
    {
        $merchantId=$params['merchant_id'];
        $page = $request->get('page');
        $limit = $request->get('limit','30');
        $where = 'lb.merchant_id = ' . $merchantId;
        if($request->isPost)
        {
            $page = $request->post('page');
            $limit = $request->post('limit','30');
            $startTime = $request->post('startTime');
            $endTime = $request->post('endTime');
            $orderId = $request->post('orderId');
            if($startTime && $endTime)
            {
                $startTime=strtotime($startTime);
                $endTime=strtotime($endTime);
                $where .= " and b.lendpay_time between $startTime and $endTime ";
            }
            if ($orderId) {
                $where .= " and lo.order_id = $orderId";
            }
            return LoanOrder::getLendPayListByMerchantId($page, $limit, $where);
        }
        return LoanOrder::getLendPayListByMerchantId($page, $limit, $where);
    }


}