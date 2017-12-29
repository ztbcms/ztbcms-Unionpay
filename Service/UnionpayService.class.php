<?php

/**
 * author: Jayin <tonjayin@gmail.com>
 */

namespace Unionpay\Service;

use Common\Service\BaseService;

//必须指定北京时间
date_default_timezone_set('Asia/Shanghai');

include_once dirname(__FILE__ ) . '/../Sdk/acp_service.php';
include_once dirname(__FILE__ ) . '/../Sdk/SDKConfig.php';

class UnionpayService extends BaseService {

    /**
     * 获取商户号
     * @return string
     */
    static function getMerId(){
        //测试环境
        return '777290058110048';

        // 正式环境商户号
//        return '777290058110048';
    }


    /**
     * 构建银联支付HTML表单
     *
     * @param $orderid   string 订单号
     * @param $total_pay int 支付金额，单位：分
     * @return string
     */
    static function createForm($orderid, $total_pay){
        /**
         * 重要：联调测试时请仔细阅读注释！
         *
         * 产品：跳转网关支付产品<br>
         * 交易：消费：前台跳转，有前台通知应答和后台通知应答<br>
         * 日期： 2015-09<br>

         * 版权： 中国银联<br>
         * 说明：以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己需要，按照技术文档编写。该代码仅供参考，不提供编码性能规范性等方面的保障<br>
         * 提示：该接口参考文档位置：open.unionpay.com帮助中心 下载  产品接口规范  《网关支付产品接口规范》，<br>
         *              《平台接入接口规范-第5部分-附录》（内包含应答码接口规范，全渠道平台银行名称-简码对照表)<br>
         *              《全渠道平台接入接口规范 第3部分 文件接口》（对账文件格式说明）<br>
         * 测试过程中的如果遇到疑问或问题您可以：1）优先在open平台中查找答案：
         * 							        调试过程中的问题或其他问题请在 https://open.unionpay.com/ajweb/help/faq/list 帮助中心 FAQ 搜索解决方案
         *                             测试过程中产生的7位应答码问题疑问请在https://open.unionpay.com/ajweb/help/respCode/respCodeList 输入应答码搜索解决方案
         *                          2） 咨询在线人工支持： open.unionpay.com注册一个用户并登陆在右上角点击“在线客服”，咨询人工QQ测试支持。
         * 交易说明:1）以后台通知或交易状态查询交易确定交易成功,前台通知不能作为判断成功的标准.
         *       2）交易状态查询交易（Form_6_5_Query）建议调用机制：前台类交易建议间隔（5分、10分、30分、60分、120分）发起交易查询，如果查询到结果成功，则不用再查询。（失败，处理中，查询不到订单均可能为中间状态）。也可以建议商户使用payTimeout（支付超时时间），过了这个时间点查询，得到的结果为最终结果。
         */

        $params = array(

            //以下信息非特殊情况不需要改动
            'version' => \com\unionpay\acp\sdk\SDKConfig::getSDKConfig()->version,                 //版本号
            'encoding' => 'utf-8',				  //编码方式
            'txnType' => '01',				      //交易类型
            'txnSubType' => '01',				  //交易子类
            'bizType' => '000201',				  //业务类型
            'frontUrl' =>  \com\unionpay\acp\sdk\SDKConfig::getSDKConfig()->frontUrl,  //前台通知地址
            'backUrl' => \com\unionpay\acp\sdk\SDKConfig::getSDKConfig()->backUrl,	  //后台通知地址
            'signMethod' => \com\unionpay\acp\sdk\SDKConfig::getSDKConfig()->signMethod,	              //签名方法
            'channelType' => '08',	              //渠道类型，07-PC，08-手机
            'accessType' => '0',		          //接入类型
            'currencyCode' => '156',	          //交易币种，境内商户固定156

            //TODO 以下信息需要填写
            'merId' => self::getMerId(),		//商户代码，请改自己的测试商户号，此处默认取demo演示页面传递的参数
            'orderId' => $orderid,	//商户订单号，8-32位数字字母，不能含“-”或“_”，此处默认取demo演示页面传递的参数，可以自行定制规则
            'txnTime' => date('YmdHis'),	//订单发送时间，格式为YYYYMMDDhhmmss，取北京时间，此处默认取demo演示页面传递的参数
            'txnAmt' => $total_pay,	//交易金额，单位分，此处默认取demo演示页面传递的参数

            // 订单超时时间。
            // 超过此时间后，除网银交易外，其他交易银联系统会拒绝受理，提示超时。 跳转银行网银交易如果超时后交易成功，会自动退款，大约5个工作日金额返还到持卡人账户。
            // 此时间建议取支付时的北京时间加15分钟。
            // 超过超时时间调查询接口应答origRespCode不是A6或者00的就可以判断为失败。
            'payTimeout' => date('YmdHis', strtotime('+60 minutes')),

            // 请求方保留域，
            // 透传字段，查询、通知、对账文件中均会原样出现，如有需要请启用并修改自己希望透传的数据。
            // 出现部分特殊字符时可能影响解析，请按下面建议的方式填写：
            // 1. 如果能确定内容不会出现&={}[]"'等符号时，可以直接填写数据，建议的方法如下。
            //    'reqReserved' =>'透传信息1|透传信息2|透传信息3',
            // 2. 内容可能出现&={}[]"'符号时：
            // 1) 如果需要对账文件里能显示，可将字符替换成全角＆＝｛｝【】“‘字符（自己写代码，此处不演示）；
            // 2) 如果对账文件没有显示要求，可做一下base64（如下）。
            //    注意控制数据长度，实际传输的数据长度不能超过1024位。
            //    查询、通知等接口解析时使用base64_decode解base64后再对数据做后续解析。
            //    'reqReserved' => base64_encode('任意格式的信息都可以'),

            //TODO 其他特殊用法请查看 special_use_purchase.php
        );

        \com\unionpay\acp\sdk\AcpService::sign ( $params );
        $uri = \com\unionpay\acp\sdk\SDKConfig::getSDKConfig()->frontTransUrl;
        $html_form = \com\unionpay\acp\sdk\AcpService::createAutoFormHtml( $params, $uri );

        return $html_form;
    }

    /**
     * 异步请求结果验证签名
     *
     * @param array $post http post 的内容 一般传递 $_POST 即可
     * @return bool \com\unionpay\acp\sdk\是否成功，成功true 失败false
     */
    static function validateNotify(array $post){
        $logger = \com\unionpay\acp\sdk\LogUtil::getLogger();
        $logger->LogInfo("receive back notify: " . \com\unionpay\acp\sdk\createLinkString ( $post, false, true ));
        return \com\unionpay\acp\sdk\AcpService::validate ( $post );
    }

}