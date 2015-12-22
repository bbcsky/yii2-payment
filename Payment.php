<?php
namespace bbcsky\payment;

use yii\base\Component;

class Payment extends Component
{
    public $weixin_config = [];
    public $alipay_config = [];
    public $balance_config = [];
    private $_weixin = null;
    private $_alipay = null;
    private $_balance = null;

    public function getAlipay($notify_url = null)
    {
        $this->alipay_config = array_merge(['class'=>Alipay::className()],$this->alipay_config);
        return $this->_getPayment('_alipay',$notify_url);
    }

    public function getWeixin($notify_url = null)
    {
        $this->weixin_config = array_merge(['class'=>Weixin::className()],$this->weixin_config);
        return $this->_getPayment('_weixin',$notify_url);
    }

    public function getBalance()
    {
        $this->balance_config = array_merge(['class'=>Balance::className()],$this->balance_config);
        return $this->_getPayment('_balance',null);
    }

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