<?php
namespace bbcsky\payment;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\InvalidValueException;

class Balance extends Payment
{
    /**
     * @var callable return balance as numbers
     */
    public $balance_callable;
    /**
     * @var callable must return true on success
     */
    public $balance_callable_cost;
    /**
     * @var callable must return true on success
     */
    public $balance_callable_refund;

    public $order_pre = 'Balanc';

    private function _getBalance($uid,$order)
    {
        if(is_callable($this->balance_callable))
        {
            $balance = call_user_func_array($this->balance_callable,[$uid,$order]);
            if(is_numeric($balance))
            {
                return $balance;
            }
        }
        return 0;
    }
    /**
     * 支付接口
     * @param $order
     * @return mixed
     */
    public function pay($order)
    {
        $res = $this->prepay($order);
        if($res['success'])
        {
            $order['balance'] = $res['balance'];
            $order['total_fee'] = $res['total_fee'];
            if (is_callable($this->balance_callable_cost)) {
                if (call_user_func_array($this->balance_callable_cost, [$order['uid'], $order]) === true) {
                    $res['msg'] = '支付成功';
                    return $res;
                }
            }
        }
        else
        {
            throw new InvalidValueException(get_class($this) . " 余额不足");
        }
        $res['success'] = 0;
        $res['msg'] = '支付失败';
        return $res;
    }

    /**
     * 预支付接口，在APP上发起支付
     * @param $order
     * @return mixed
     */
    public function prepay($order)
    {
        $needs = array('order_sn','total_fee','uid');
        foreach($needs as $need)
        {
            if(!isset($order[$need]))
            {
                throw new InvalidConfigException(get_class($this) . " \$order 中必须包含键 {$need}.");
            }
        }
        $order['total_fee'] = round($order['total_fee'],2);
        $paras = ['success'=>0,'total_fee'=>$order['total_fee']];
        $balance = $this->_getBalance($order['uid'],$order);
        $paras['balance'] = $balance;
        if($balance >= $paras['total_fee'])
        {
            $paras['success'] = 1;
            $paras['msg'] = '余额足够';
        }
        else
        {
            $paras['msg'] = '余额不足';
        }
        return $paras;
    }

    /**
     * 退款接口
     * @param $order
     * @return mixed
     */
    public function refund($order)
    {
        $needs = array('order_sn','total_fee','uid');
        foreach($needs as $need)
        {
            if(!isset($order[$need]))
            {
                throw new InvalidConfigException(get_class($this) . " \$order 中必须包含键 {$need}.");
            }
        }
        if (is_callable($this->balance_callable_refund)) {
            if (call_user_func_array($this->balance_callable_refund, [$order['uid'], $order]) === true) {
                return true;
            }
        }
        return false;
    }

    /**
     * Notify处理完成接口
     * @return mixed
     */
    public function finish()
    {
        return true;
    }

    /**
     * 设置Notify回调接口
     * @return mixed
     */
    public function setNotifyUrl($url)
    {
        $this->notify_url = $url;
    }

    /**
     * 获得Notify返回的支付金额
     * @return mixed
     */
    public function getTotalFee($total_fee = null)
    {
        if($total_fee)
        {
            return $total_fee;
        }
        return false;
    }

    /**
     * 获得Notify返回的交易号
     * @return mixed
     */
    public function getSerialNo()
    {
        return false;
    }

    /**
     * 获得Notify返回的原始数据
     * @return mixed
     */
    public function getNotifyRaw()
    {
        return false;
    }
}