<?php

/**
 * author: Jayin <tonjayin@gmail.com>
 */

namespace Unionpay\Controller;

use Common\Controller\Base;
use Daogou\Model\DgorderModel;
use Payment\Service\PaymentService;
use Think\Log;
use Unionpay\Service\UnionpayService;

class PaymentController extends Base {

    //后台通知以标准的HTTP协议的POST方法向商户的后台通知URL发送，超时时间为10秒。
    //
    //由于网络等原因，商户可能会收到重复的后台通知，商户应能正确识别并处理。
    //
    //商户返回码为200或302时，银联判定为通知成功，其他返回码为通知失败。
    //
    //如10秒内未收到应答，银联判定为通知失败。
    //
    //第一次通知失败后，银联会重发，最多发送五次，每次的间隔时间为0,1,2,4分钟。
    /**
     * 后台异步通知处理
     */
    function pay_notify(){
        Log::write('银联支付：通知！');

        $post = $_POST;
        Log::write('银联支付：pay_notify=>raw==>'.json_encode($post));

        $result = UnionpayService::validateNotify($_POST);

        if($result){
            //验签成功
            Log::write('银联支付：pay_notify=>验签成功');

            $orderid = $post['orderId'];
            $pay_no = $post['traceNo'];
            $payment = PaymentService::PAYMENT_UNOIN_PAY;
            $pay_price = $post['txnAmt']/100;

            $order = M('dgorder')->where(['orderid' => $orderid])->find();

            //是否存在订单
            if (empty($order)) {
                Log::write('银联支付异步通知:PaymentController:pay_notify:找不到该订单,订单号:'.$orderid);
                echo 'SUCCESS';
                exit();
            }

            //是否已经支付、订单已完成、订单已取消
            if ($order['pay_status'] == DgorderModel::PAY_STATUS_PAYID || $order['order_status'] == DgorderModel::ORDER_STATUS_FINISH || $order['order_status'] == DgorderModel::ORDER_STATUS_INVALID) {
                Log::write('银联支付异步通知:PaymentController:pay_notify:订单已经支付或订单已完成或订单已取消');
                echo 'SUCCESS';
                exit();
            }

            //支付成功 //判断respCode=00、A6后，对涉及资金类的交易，请再发起查询接口查询，确定交易成功后更新数据库。
            if($post['respCode'] == '00'){
                Log::write('银联支付：pay_notify=>handlePaySuccess');
                PaymentService::handlePaySuccess($orderid, $pay_no, $payment, $pay_price);
            }

            echo 'SUCCESS';
            exit();
        }

        Log::write('银联支付：pay_notify=>验签失败');

    }

    /**
     * 支付完毕跳转
     */
    function pay_return (){
        $redirectURL = U('Content/Index/index');
        $this->success('支付完毕，感谢你的支持!', $redirectURL);
    }

    /**
     * 支付取消跳转
     */
    function pay_cancel_return (){
        $this->success('支付已取消，感谢你的支持!', U('Content/Index/index'));
    }

}