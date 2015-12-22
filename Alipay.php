<?php
namespace bbcsky\payment;

use Yii;

class Alipay extends Payment
{
    public $sign_type = 'RSA';
    public $input_charset = 'utf-8';
    public $transport = 'http';
    public $service = 'mobile.securitypay.pay';
    public $service_refund = 'refund_fastpay_by_platform_pwd';
    public $gateway_refund = 'https://mapi.alipay.com/gateway.do';
    public $order_pre = 'Alipay';

    private $partner;
    private $seller_id;
    private $key;
    private $key_path;
    private $ali_pub_path;
    private $ali_ca_path;
    private $cacert;
    private $curl_proxy_host;
    private $curl_proxy_port;

    /**
     * 支付接口
     * @param $order
     * @return mixed
     */
    public function pay($order)
    {

    }

    /**
     * 预支付接口，在APP上发起支付
     * @param $order
     * @return mixed
     */
    public function prepay($order)
    {

    }

    /**
     * 退款接口
     * @param $order
     * @return mixed
     */
    public function refund($order)
    {

    }

    /**
     * Notify处理完成接口
     * @return mixed
     */
    public function finish()
    {

    }

    /**
     * 设置Notify回调接口
     * @return mixed
     */
    public function setNotifyUrl()
    {

    }

    /**
     * 获得Notify返回的支付金额
     * @return mixed
     */
    public function getTotalFee()
    {

    }

    /**
     * 获得Notify返回的交易号
     * @return mixed
     */
    public function getSerialNo()
    {

    }

    /**
     * 获得Notify返回的原始数据
     * @return mixed
     */
    public function getNotifyRaw()
    {

    }
}