<?php
namespace bbcsky\payment;

use yii\base\Component;

abstract class Payment extends Component
{
    public $notifyUrl = '';
    /**
     * 支付接口
     * @param $order
     * @return mixed
     */
    abstract public function pay($order);

    /**
     * 预支付接口，在APP上发起支付
     * @param $order
     * @return mixed
     */
    abstract public function prepay($order);

    /**
     * 退款接口
     * @param $order
     * @return mixed
     */
    abstract public function refund($order);

    /**
     * Notify处理完成接口
     * @return mixed
     */
    abstract public function finish();

    /**
     * 设置Notify回调接口
     * @return mixed
     */
    abstract public function setNotifyUrl();

    /**
     * 获得Notify返回的支付金额
     * @return mixed
     */
    abstract public function getTotalFee();

    /**
     * 获得Notify返回的交易号
     * @return mixed
     */
    abstract public function getSerialNo();

    /**
     * 获得Notify返回的原始数据
     * @return mixed
     */
    abstract public function getNotifyRaw();
}