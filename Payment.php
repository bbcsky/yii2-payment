<?php
namespace bbcsky\payment;

use yii\base\Component;

class Payment extends Component
{
    public $code;
    public $notify_url;
    public $order_pre;

}