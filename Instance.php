<?php

namespace bbcsky\payment;

use yii\base\Component;

class Instance extends Component{
    private $weixin_config;
    private $weixins_config;
    private $alipay_config;
    private $balance_config;
    private $_weixin = null;
    private $_weixins = null;
    private $_alipay = null;
    private $_balance = null;

    public function setWeixin_config($config)
    {
        $this->weixin_config = $config;
    }
    public function setWeixins_config($config)
    {
        $this->weixins_config = $config;
    }
    public function setAlipay_config($config)
    {
        $this->alipay_config = $config;
    }
    public function setBalance_config($config)
    {
        $this->balance_config = $config;
    }
    /**
     * 获得支付宝支付
     * @param null $notify_url
     * @return mixed|object
     */
    public function getAlipay($notify_url = null)
    {
        $this->alipay_config = array_merge(['class'=>Alipay::className()],$this->alipay_config);
        return $this->_getPayment('_alipay',$notify_url);
    }

    /**
     * 获得微信app c端支付
     * @param null $notify_url
     * @return mixed|object
     */
    public function getWeixin($notify_url = null)
    {
        $this->weixin_config = array_merge(['class'=>Weixin::className()],$this->weixin_config);
        return $this->_getPayment('_weixin',$notify_url);
    }

    /**
     * 获得微信app s端支付
     * @param null $notify_url
     * @return mixed|object
     */
    public function getWeixins($notify_url = null)
    {
        $this->weixins_config = array_merge(['class'=>Weixin::className()],$this->weixins_config);
        return $this->_getPayment('_weixins',$notify_url);
    }

    /**
     * 获得余额支付
     * @return mixed|object
     */
    public function getBalance()
    {
        $this->balance_config = array_merge(['class'=>Balance::className()],$this->balance_config);
        return $this->_getPayment('_balance',null);
    }

    /**
     * @param $name
     * @param $notify_url
     * @return mixed|object
     * @throws \yii\base\InvalidConfigException
     */
    private function _getPayment($name, $notify_url)
    {
        $config = substr($name.'_config',1);
        if(is_null($this->{$name}))
        {
            $this->{$name} = \Yii::createObject($this->{$config});
        }
        if(!is_null($notify_url))
        {
            $this->{$name}->setNotifyUrl($notify_url);
        }
        return $this->{$name};
    }
}