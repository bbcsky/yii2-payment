<?php
namespace bbcsky\payment;

use Yii;
use yii\base\Component;

class Weixin extends Component
{
    public $orderUrl = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
    public $searchOrderUrl = 'https://api.mch.weixin.qq.com/pay/orderquery';
    public $closeOrderUrl = 'https://api.mch.weixin.qq.com/pay/closeorder';
    public $refundUrl = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
    public $searchRefundUrl = 'https://api.mch.weixin.qq.com/pay/refundquery';
    public $order_pre = 'Weixin';

    private $appid;
    private $mch_id;
    private $key;
    private $key_path;
    private $secret;
    private $cert_path;
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