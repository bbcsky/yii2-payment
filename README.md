Payments Alipay, Weixin and Balance for Yii 2
===============================================

The yii2 payment extension for alipay, weixin and balance

For license information check the [LICENSE](LICENSE.md)-file.


Requirements
------------

None

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist bbcsky/yii2-payment
```

or add

```json
"bbcsky/yii2-payment": "*"
```

to the require section of your composer.json.


Configuration
-------------

To use this extension, you have to configure the Connection class in your application configuration:

```php
return [
    //....
    'components' => [
        'payment' => [
            'class'=>'bbcsky\payment\Instance',
                'alipay_config' => [
                    'code'         => 1,
                    'partner'      => '112345654323',
                    'key_path'     => '@app/cert/key.pem',
                    'ali_pub_path' => '@app/cert/alipay.pem',
                    'ali_ca_path'  => '@app/cert/cacert.pem',
                    'key'          => 'sbf6tj8cn6zsdqweqeqeazbigiqcibext',
                ],
                'weixin_config' => [
                    'code'      => 2,
                    'appid'     => 'wxb7d65asd123131338',
                    'secret'    => 'cb0b13123131231231sfasfe945db4',
                    'mch_id'    => '124567887',
                    'key'       => '99a4cb12313131ffsdfasfqcc392e5',
                    'cert_path' => '@app/cert/weixin_cert.pem',
                    'key_path'  => '@app/cert/weixin_key.pem',
                ],
                'weixins_config' => [
                    'code'      => 4,
                    'appid'     => 'wx312313131352asssss',
                    'secret'    => 'cb0b13123131231231sfasfe945db4',
                    'mch_id'    => '124567887',
                    'key'       => '99a4cb12313131ffsdfasfqcc392e5',
                    'cert_path' => '@app/cert/weixins_cert.pem',
                    'key_path'  => '@app/cert/weixins_key.pem',
                ],
                'balance_config' => [
                    'code' => 3,
                    'balance_callable'=>'\app\models\Account::balance',
                    'balance_callable_cost'=>'\app\models\Account::balanceCost',
                    'balance_callable_refund'=>'\app\models\Account::balanceRefund',
                ],
        ],
    ]
];
```

