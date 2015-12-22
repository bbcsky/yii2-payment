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
            'class' => 'bbcsky\payment\Payment',
            'xx' => 'xx',
            'xx' => 'xx',
        ],
    ]
];
```

