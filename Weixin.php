<?php
namespace bbcsky\payment;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\base\InvalidValueException;

class Weixin extends Component
{
    public $order_url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
    public $search_order_url = 'https://api.mch.weixin.qq.com/pay/orderquery';
    public $close_order_url = 'https://api.mch.weixin.qq.com/pay/closeorder';
    public $refund_url = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
    public $search_refund_url = 'https://api.mch.weixin.qq.com/pay/refundquery';
    public $order_pre = 'Weixin';
    public $notify_url = '';

    private $appid;
    private $mch_id;
    private $key;
    private $key_path;
    private $secret;
    private $cert_path;
    private $notify_data;
    private $curl_proxy_host;
    private $curl_proxy_port;

    public function setAppid($appid)
    {
        $this->appid = $appid;
    }

    public function setKey($key)
    {
        $this->key = $key;
    }

    public function setSecret($secret)
    {
        $this->secret = $secret;
    }

    public function setKey_path($key_path)
    {
        $this->key_path = Yii::getAlias($key_path);
    }
    public function setCert_path($cert_path)
    {
        $this->cert_path = Yii::getAlias($cert_path);
    }

    public function setMch_id($mch_id)
    {
        $this->mch_id = $mch_id;
    }

    public function init()
    {
        parent::init();
        $needs = array('appid','mch_id','key','secret');
        foreach($needs as $need)
        {
            if(empty($this->{$need}))
            {
                throw new InvalidConfigException(get_class($this) . " must define weixin's params {$need}.");
            }
        }
    }

    private function _checkRefund()
    {
        $needs = array('key_path','cert_path');
        foreach($needs as $need)
        {
            if(empty($this->{$need}))
            {
                throw new InvalidConfigException(get_class($this) . " must define weixin's params {$need}.");
            }
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
        $needs = array('title','order_sn','body','total_fee');
        foreach($needs as $need)
        {
            if(!isset($order[$need]))
            {
                throw new InvalidConfigException(get_class($this) . " \$order 中必须包含键 {$need}.");
            }
        }
        $paras = [
            'trade_type'        =>'APP',
            'time_start'        =>empty($order['time_start'])? '':$order['time_start'],
            'time_expire'       =>empty($order['time_expire'])? '':$order['time_expire'],
            'goods_tag'         =>empty($order['goods_tag'])? '':$order['goods_tag'],
            'device_info'       =>empty($order['device_info'])? '':$order['device_info'],
            'out_trade_no'      =>$this->order_pre.$order['order_sn'],
            'detail'            =>$order['body'],
            'total_fee'         =>(int)($order['total_fee'] * 100),
            'body'              =>$order['title'],
            'fee_type'          =>empty($order['fee_type'])? '':$order['fee_type'],
            'product_id'        =>empty($order['product_id'])? '':$order['product_id'],
            'openid'            =>empty($order['openid'])? '':$order['openid'],
            'attach'            =>empty($order['attach'])? '':$order['attach'],
        ];
        $timeout = empty($order['timeout'])? 6 :$order['timeout'];
        if($order['total_fee'] <= 0)
        {
            throw new InvalidValueException(get_class($this) . " 支付金额必须大于0");
        }
        $trade = $this->createOrder($paras,$timeout);
        if(isset($trade['return_code']) && $trade['return_code'] == 'SUCCESS')
        {
            if(isset($trade['result_code']) && $trade['result_code'] == 'SUCCESS')
            {
                $trade['total_fee'] = $order['total_fee'];
                return $this->getSign($trade);
            }
            else
            {
                throw new InvalidValueException(get_class($this) . $trade['err_code_des']);
            }
        }
        else
        {
            throw new InvalidValueException(get_class($this) . $trade['return_msg']);
        }
    }

    public function getSign($order)
    {
        $total_fee = $order['total_fee'];
        $keys = ['appid','partnerid','prepayid','package','noncestr','timestamp','sign'];
        $order['partnerid'] = $order['mch_id'];
        $order['prepayid'] = $order['prepay_id'];
        $order = array_intersect_key($order,array_fill_keys($keys,''));
        $order['package'] = 'Sign=WXPay';
        $order['timestamp'] = time();
        $order['noncestr'] = $this->randomStr(30);
        $order['sign'] = $this->sign($order);
        $order['total_fee'] = $total_fee;
        return $order;
    }

    public function notify()
    {
        //获取通知的数据
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        //如果返回成功则验证签名
        try {
            $result = $this->fromXml($xml);
            if($result['return_code'] == 'SUCCESS')
            {
                $sign = $this->sign($result);
                if($sign == $result['sign'])
                {
                    return $result;
                }
                else
                {
                    throw new InvalidValueException(get_class($this) . '验证签名失败');
                }
            }
            else
            {
                throw new InvalidValueException(get_class($this) . $result['return_msg']);
            }
        } catch (\Exception $e){
            throw new InvalidValueException(get_class($this) . $e->errorMessage());
        }
    }

    /**
     * 退款接口
     * @param $order
     * @return mixed
     */
    public function refund($order)
    {
        $this->_checkRefund();
        $needs = array('order_sn','total_fee');
        foreach($needs as $need)
        {
            if(!isset($order[$need]))
            {
                throw new InvalidConfigException(get_class($this) . " \$order 中必须包含键 {$need}.");
            }
        }
        $order['out_trade_no'] = $this->order_pre.$order['order_sn'];
        $order['total_fee'] = round($order['total_fee'],2) * 100;
        $order['refund_fee'] = $order['total_fee'];
        $order['op_user_id'] = $this->mch_id;

        $need = array('out_trade_no','out_refund_no','total_fee','refund_fee','op_user_id');
        $keys = ['device_info','refund_fee_type','transaction_id'];
        foreach($need as $key)
        {
            if(empty($order[$key])) {
                throw new InvalidConfigException("缺少退款申请接口必填参数{$key}！");
            }
        }

        $order = array_intersect_key($order,array_fill_keys(array_merge($need, $keys),''));
        $order['appid'] = $this->appid;
        $order['mch_id'] = $this->mch_id;
        $order['nonce_str'] = $this->randomStr();
        $order['sign'] = $this->sign($order);
        $xml = $this->toXml($order);
        $response = $this->postXmlCurl($xml, $this->refund_url);
        $result = $this->convertResponse($response);

        return $result;
    }

    /**
     * Notify处理完成接口
     * @return mixed
     */
    public function finish()
    {
        $arr = ['return_code'=>'SUCCESS'];
        $xml = $this->toXml($arr);
        return $xml;
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
            return round($total_fee/100,2,PHP_ROUND_HALF_DOWN);
        }
        if(isset($this->notify_data['total_fee']))
        {
            return round($this->notify_data['total_fee']/100,2,PHP_ROUND_HALF_DOWN);
        }
        return false;
    }

    /**
     * 获得Notify返回的交易号
     * @return mixed
     */
    public function getSerialNo($arr = null)
    {
        if(isset($arr['transaction_id']))
        {
            return $arr['transaction_id'];
        }
        if(isset($this->notify_data['transaction_id']))
        {
            return $this->notify_data['transaction_id'];
        }
        return false;
    }

    /**
     * 获得Notify返回的原始数据
     * @return mixed
     */
    public function getNotifyRaw()
    {
        return $GLOBALS['HTTP_RAW_POST_DATA'];
    }

    public function randomStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {
            $str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        return $str;
    }

    public function getIp()
    {
        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $strIp = $arr[0];
        } elseif (isset($_SERVER["HTTP_CLIENT_IP"])) {
            $strIp = $_SERVER["HTTP_CLIENT_IP"];
        } elseif (isset($_SERVER["REMOTE_ADDR"])) {
            $strIp = $_SERVER["REMOTE_ADDR"];
        } else {
            $strIp = "0.0.0.0";
        }
        return $strIp;
    }

    private function toXml($values)
    {
        if(!is_array($values) || count($values) <= 0)
        {
            throw new InvalidValueException("数组数据异常！");
        }
        $xml = "<xml>";
        foreach ($values as $key=>$val)
        {
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }

    private function fromXml($xml)
    {
        if(!$xml){
            throw new InvalidValueException("xml数据异常！");
        }
        try
        {
            $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        }
        catch(\Exception $e)
        {
            throw new InvalidValueException("xml数据异常！");
        }
        return $values;
    }

    public function sign($values)
    {
        ksort($values);
        $string = "";
        foreach ($values as $k => $v)
        {
            if($k != "sign" && $v != "" && !is_array($v)){
                $string .= $k . "=" . $v . "&";
            }
        }

        $string = trim($string, "&");
        $string = $string . "&key=".$this->key;
        $string = md5($string);
        return strtoupper($string);
    }

    public function checkSign($values)
    {
        if($this->sign($values) == $values['sign']){
            return true;
        }
        throw new InvalidValueException("验证签名错误！");
    }

    private function convertResponse($xml)
    {
        $result = $this->fromXml($xml);
        if($result['return_code'] != 'SUCCESS')
        {
            throw new InvalidValueException($result['return_msg']);
        }
        if($this->checkSign($result) === true){
            return $result;
        }else{
            return false;
        }
    }

    public function searchOrder($out_trade_no, $transaction_id = '',$timeOut = 6)
    {
        if(empty($out_trade_no) && empty($transaction_id)) {
            throw new InvalidValueException("缺少订单查询接口必填参数out_trade_no或transaction_id！");
        }
        $order = ['out_trade_no'=>$out_trade_no,'transaction_id'=>$transaction_id];
        $order['appid'] = $this->appid;
        $order['mch_id'] = $this->mch_id;
        $order['nonce_str'] = $this->randomStr();
        $order['sign'] = $this->sign($order);

        $xml = $this->toXml($order);
        $response = $this->postXmlCurl($xml, $this->search_order_url, false, $timeOut);
        $result = $this->convertResponse($response);

        return $result;
    }

    public function closeOrder($out_trade_no, $timeOut = 6)
    {

        if(empty($out_trade_no)) {
            throw new InvalidValueException("缺少订单查询接口必填参数out_trade_no！");
        }
        $order = ['out_trade_no'=>$out_trade_no];
        $order['appid'] = $this->appid;
        $order['mch_id'] = $this->mch_id;
        $order['nonce_str'] = $this->randomStr();
        $order['sign'] = $this->sign($order);

        $xml = $this->toXml($order);
        $response = $this->postXmlCurl($xml, $this->close_order_url, false, $timeOut);
        $result = $this->convertResponse($response);

        return $result;
    }

    public function createOrder(array $order, $timeOut = 6)
    {
        //检测必填参数
        $need = array('out_trade_no','body','total_fee','trade_type');
        $keys = array('appid','mch_id','device_info','nonce_str','sign','detail','attach','fee_type',
            'spbill_create_ip','time_start','time_expire','goods_tag','notify_url','product_id','openid');
        $keys = array_merge($need,$keys);
        foreach($need as $key)
        {
            if(empty($order[$key])) {
                throw new InvalidValueException("缺少统一下单接口必填参数{$key}！");
            }
        }

        //关联参数
        if($order['trade_type'] == "JSAPI" && empty($order['openid'])){
            throw new InvalidValueException("统一支付接口中，缺少必填参数openid！trade_type为JSAPI时，openid为必填参数！");
        }
        if($order['trade_type'] == "NATIVE" && empty($order['product_id'])){
            throw new InvalidValueException("统一支付接口中，缺少必填参数product_id！trade_type为JSAPI时，product_id为必填参数！");
        }

        $order = array_intersect_key($order,array_fill_keys($keys,''));
        $order['appid'] = $this->appid;
        $order['mch_id'] = $this->mch_id;
        $order['notify_url'] = $this->notifyUrl;
        $order['spbill_create_ip'] = $this->getIp();
        $order['nonce_str'] = $this->randomStr();
        $order['sign'] = $this->sign($order);
        $xml = $this->toXml($order);
        $response = $this->postXmlCurl($xml, $this->order_url, false, $timeOut);
        $result = $this->convertResponse($response);
        return $result;
    }

    public function searchRefund($order, $timeOut = 6)
    {
        $keys = ['out_refund_no','out_trade_no','transaction_id','refund_id'];
        if(empty($order['out_trade_no']) && empty($order['transaction_id']) && empty($order['out_refund_no']) && empty($order['refund_id'])) {
            throw new InvalidValueException("退款查询接口中，out_refund_no、out_trade_no、transaction_id、refund_id四个参数必填一个！");
        }
        $order = array_intersect_key($order,array_fill_keys($keys,''));
        $order['appid'] = $this->appid;
        $order['mch_id'] = $this->mch_id;
        $order['nonce_str'] = $this->randomStr();
        $order['sign'] = $this->sign($order);
        $xml = $this->toXml($order);
        $response = $this->postXmlCurl($xml, $this->search_refund_url, true, $timeOut);
        $result = $this->convertResponse($response);

        return $result;
    }

    private function postXmlCurl($xml, $url, $useCert = false, $second = 30)
    {
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);

        //如果有配置代理这里就设置代理
        if($this->curl_proxy_host != "0.0.0.0" && $this->curl_proxy_port != 0){
            curl_setopt($ch,CURLOPT_PROXY, $this->curl_proxy_host);
            curl_setopt($ch,CURLOPT_PROXYPORT, $this->curl_proxy_port);
        }
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        if($useCert == true && !empty($this->cert_path) && !empty($this->key_path)){
            //设置证书
            //使用证书：cert 与 key 分别属于两个.pem文件
            curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLCERT, $this->cert_path);
            curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLKEY, $this->key_path);
        }
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if($data){
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            throw new InvalidValueException("curl出错，错误码:{$error}");
        }
    }
}