<?php
namespace bbcsky\payment;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;

class Alipay extends Component
{
    public $notify_url = '';
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

    private function checkParams()
    {

    }

    public function init()
    {
        parent::init();
        $needs = array('partner','key','key_path','ali_pub_path');
        foreach($needs as $need)
        {
            if(empty($this->{$need}))
            {
                throw new InvalidConfigException(get_class($this) . " must define alipay's {$need}.");
            }
        }
        if(empty($this->seller_id))
        {
            $this->seller_id = $this->partner;
        }
    }

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
        $paras = ['subject' => $order['title']];
        $paras['out_trade_no'] = $this->order_pre.$order['order_sn'];
        $paras['body'] = $order['body'];
        $paras['total_fee'] = round($order['total_fee'],2);
        if($paras['total_fee'] <= 0)
        {
            return '支付金额必须大于0';
        }
        $paras['notify_url'] = $this->notify_url;
        return $paras;

    }

    /**
     * 退款接口
     * @param $order
     * @return mixed
     */
    public function refund($order)
    {
        $paras = [
            'service'           =>$this->service_refund,
            'partner'           =>$this->partner,
            '_input_charset'    =>$this->input_charset,
            //'sign_type'         =>$this->sign_type,
            'sign_type'         =>'MD5',
            'notify_url'        =>$this->notify_url,
            //'seller_email'      =>'',
            'seller_user_id'    =>$this->partner,
            'refund_date'       =>date('Y-m-d H:i:s'),
            'batch_no'          =>$order['batch_no'],
            'batch_num'         =>$order['batch_num'],
            'detail_data'       =>$order['detail_data'],
        ];

        ksort($paras);
        reset($paras);
        $app_str = '';
        foreach($paras as $key => $val)
        {
            if($key == "sign" || $key == "sign_type" || $val == "")
            {
                if($val == "")
                {
                    unset($paras[$key]);
                }
                continue;
            }
            $app_str .= $key.'='.$val.'&';
        }
        if(get_magic_quotes_gpc()){
            $app_str = stripslashes($app_str);
        }
        $paras['sign'] = $this->md5Sign(substr($app_str,0,-1));
        return $this->buildRequestForm($paras, 'POST', 'submit');
    }

    public function notify()
    {
        $paras = $_POST;
        ksort($paras);
        reset($paras);
        $sign_str = '';
        foreach($paras as $key => $val)
        {
            if($key == "sign" || $key == "sign_type" || $val == "")
            {
                if($val == "")
                {
                    unset($paras[$key]);
                }
                continue;
            }
            $sign_str .= $key.'='.$val.'&';
        }
        if(strtolower($paras['sign_type']) == 'md5')
        {
            if(get_magic_quotes_gpc()){
                $sign_str = stripslashes($sign_str);
            }
            $sign = $this->md5Verify(substr($sign_str,0,-1),$paras['sign']);
        }
        else
        {
            $sign = $this->rsaVerify(substr($sign_str,0,-1),$paras['sign']);
        }
        if($sign)
        {
            if(empty($paras['trade_status']))
            {
                $paras['total_fee'] = 0;
            }
            $paras['trade_status'] = isset($paras['trade_status'])? trim($paras['trade_status']) : '';
            if(!in_array($paras['trade_status'],['TRADE_SUCCESS','TRADE_FINISHED']))
            {
                $paras['total_fee'] = 0;
            }
            if(!empty($paras['refund_status']))
            {
                $paras['total_fee'] = 0;
            }
            return $paras;
        }
        else
        {
            return '签名失败';
        }
    }

    /**
     * Notify处理完成接口
     * @return mixed
     */
    public function finish()
    {
        return 'success';
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
        return $_POST;
    }

    private function rsaSign($data)
    {
        $priKey = file_get_contents($this->key_path);
        $res = openssl_get_privatekey($priKey);
        openssl_sign($data, $sign, $res);
        openssl_free_key($res);
        //base64编码
        $sign = base64_encode($sign);
        return $sign;
    }

    private function rsaVerify($data, $sign)
    {
        $pubKey = file_get_contents($this->ali_pub_path);
        $res = openssl_get_publickey($pubKey);
        $result = (bool)openssl_verify($data, base64_decode($sign), $res);
        openssl_free_key($res);
        return $result;
    }

    private function rsaDecrypt($content)
    {
        $priKey = file_get_contents($this->key_path);
        $res = openssl_get_privatekey($priKey);
        //用base64将内容还原成二进制
        $content = base64_decode($content);
        //把需要解密的内容，按128位拆开解密
        $result  = '';
        for($i = 0; $i < strlen($content)/128; $i++  ) {
            $data = substr($content, $i * 128, 128);
            openssl_private_decrypt($data, $decrypt, $res);
            $result .= $decrypt;
        }
        openssl_free_key($res);
        return $result;
    }

    private function md5Sign($prestr)
    {
        $prestr = $prestr . $this->key;
        return md5($prestr);
    }

    private function md5Verify($prestr, $sign)
    {
        $prestr = $prestr . $this->key;
        $mysgin = md5($prestr);
        if($mysgin == $sign) {
            return true;
        }
        else {
            return false;
        }
    }
    private function buildRequestForm($para, $method, $button_name)
    {
        $sHtml = "<form id='alipaysubmit' name='alipaysubmit' action='".$this->gateway_refund."' method='".$method."'>";
        foreach($para as $key=>$val){
            $sHtml.= "<input type='hidden' name='".$key."' value='".$val."'/>";
        }
        $sHtml = $sHtml."<input type='submit' value='".$button_name."'></form>";
        $sHtml = $sHtml."<script>document.forms['alipaysubmit'].submit();</script>";
        return $sHtml;
    }
}