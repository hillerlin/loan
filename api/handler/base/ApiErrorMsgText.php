<?php
/**
 * Created by PhpStorm.
 * User: billy
 * Date: 2016/8/3
 */

namespace api\handler\base;


/**
 * API 公共错误返回
 *
 * Class ApiErrorMsgText
 * @package ApiBundle\Handler\Base
 */
class ApiErrorMsgText
{
    const SUCCESS_000000  = '成功';

    const ERROR_10000   = '非法访问';

    const ERROR_11001   = '系统错误';
    const ERROR_11004   = '非法操作';

    //12000~12999 数据库错误
    const ERROR_12000   = '数据库操作错误';
    const ERROR_12001   = '数据已存在';
    const ERROR_12002   = '数据不存在';

    //13000~13999 参数错误
    const ERROR_13000   = '参数错误';
    const ERROR_13001   = '参数不完整';
    const ERROR_13002   = '错误来源';
    const ERROR_13003   = '参数 {name} 必须提交';
    const ERROR_13004   = '参数 {name} 值类型错误';
    const ERROR_13005   = '参数 {name} 可选项不存在';
    const ERROR_13006   = '流水号不唯一';
    const ERROR_13007   = '版本号错误';
    const ERROR_13008   = '交易渠道错误';
    const ERROR_13009   = '验签失败';
    const ERROR_13010   = '请求时间超出系统时间';
    const ERROR_13011   = '请勿频繁请求';

    //15000~18999 业务错误
    const ERROR_15001   = '用户不存在';
    const ERROR_15002   = '手机号不存在';
    const ERROR_15003   = '用户已存在';
    const ERROR_15004   = '用户信息错误，与平台信息不匹配';
    const ERROR_15006   = '请先获取验证码';
    const ERROR_15007   = '请先激活账户';
    const ERROR_15008   = '请先完善用户信息';

    const ERROR_15021   = '年龄小于18周岁绑定失败';
    const ERROR_15022   = '该身份证已被绑定';
    const ERROR_15023   = '用户已申领过电子账号，且分配了授信额度';
    const ERROR_15024   = '用户已设置过交易密码';
    const ERROR_15025   = '年收入（万元）金额异常';

    const ERROR_15101   = '订单号不存在';

    const ERROR_16001   = '用户授信额度超上限';
    const ERROR_16002   = '企业授信额度超上限';
    const ERROR_16003   = '用户征信分值过低，无法进行借贷';
    const ERROR_16204   = '征信系统-参数格式不正确';
    const ERROR_16401   = '征信系统-授权失败';
    const ERROR_16403   = '征信系统-请求方式不正确';
    const ERROR_16500   = '征信系统-服务器出现异常';
    const ERROR_16502   = '征信系统-异常';

    const ERROR_17001   = '超过平台总体授信额度';
    const ERROR_17002   = '超过用户当前额度';
    const ERROR_17003   = '超过用户当前平台额度';
    const ERROR_17004   = '不在支持的借款期限单位范围内';
    const ERROR_17005   = '不在支持的借款期限范围内';
    const ERROR_17006   = '不在支持的还款方式';
    const ERROR_17007   = '交易订单不存在';
    const ERROR_17008   = '电子签章生成失败';
    const ERROR_17009   = '借款金额本阶段只接受100的整数倍';
    const ERROR_17010   = '借款人还有一笔借款订单待确认';
    const ERROR_17011   = '商户还未配置借款利率表';
    const ERROR_17012   = '低于此期限最低借款金额';

    const ERROR_17101   = '合同获取失败';

    const ERROR_18001 = '设置交易密码失败';
    const ERROR_18002 = '订单提交失败';
    const ERROR_18003 = '征信信息不存在';
    const ERROR_18004 = '验证失败，请重试';
}