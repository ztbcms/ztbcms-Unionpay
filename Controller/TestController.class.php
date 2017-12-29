<?php

/**
 * author: Jayin <tonjayin@gmail.com>
 */

namespace Unionpay\Controller;

use Common\Controller\Base;
use Content\Service\DgorderService;
use Unionpay\Service\UnionpayService;

class TestController extends Base {

    /**
     * 银联支付
     * 银联会自动跟浏览器环境跳转其网页PC/Wap
     * 根据自己的订单环境去补充参数
     */
    function unionpay(){
        //获取订单号
        $orderid = I('get.orderid');
        //参考：

        $total_pay = DgorderService::getOrderTotalPayRMB($orderid);//精确到分
        //构建表单
        $form = UnionpayService::createForm($orderid, $total_pay);

        //输出表单，自动提交表单
        echo $form;
        exit();
    }

}