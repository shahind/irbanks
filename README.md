# IRbanks
Iranian banks payment gateways Interface

## Introduction
This library provides a simple and easy way to accept payments using Iranian banks with PHP.

## Installation
simply install IRbanks using composer:
```
composer require shahinsoft/irbanks
```

## How to use?
Each bank has its own payment process, end-points, and parameters. First of all you need to know
about the process of your bank. Then refer to your bank section in this document.

### Mellat
Mellat payment has 3 main steps; getting the payment toke, verifying the payment, and settling the payment.

#### 1. Get payment token
```php
try{
    $mellat = new \IRbanks\Mellat($terminalId, $userName, $userPassword);
    $response = $mellat->request($amount);
}catch(\Throwable $e){
    echo "error: ".$e->getMessage();
}
```

#### 2. Redirect user to payment page
```php
//use $response info like token($response->token) and orderId($response->order_id) to create a HTML form with POST method
//or automatically do it using redirectToMellat() function
    $response->redirectToMellat();
```

#### 3. Verify payment
```php
try{
    $mellat = new \IRbanks\Mellat($terminalId, $userName, $userPassword);
    $response = $mellat->verify();
    update_your_payment_with($response->reference_id,$response->order_id,$response->card_number);
    echo "successful payment";
}catch(\Throwable $e){
    //payment was unsuccessful or verification failed
    echo "error: ".$e->getMessage();
}
```

### Parsian
Parsian payment has 3 main steps; getting the payment token, redirecting user to payment page, and verifying the payment.
There is a possibility to reverse a transaction as well.

#### 1. Get payment token
```php
<?php 
use IRbanks\Parsian;

try{
    $parsian = new Parsian($pin);
    $response = $parsian->request($amount, $callbackUrl, $orderId, $additionalData);
}catch (\Throwable $exception){
    echo $exception->getMessage();
}
```

#### 2. Redirect user to the payment page
```php
//use payment URL ($parsian->paymentUrl()) to redirect user to the payment page with your project standards
//or call redirect function ($parsian->redirect()) for automatic redirect using header location

//manual approach
$payment_url = $parsian->paymentUrl();
return redirect($payment_url);

//automatic approach
$parsian->redirect();
```

#### 3. Verify payment
```php
<?php
use IRbanks\Parsian;

try{
    $parsian = new Parsian($pin);
    $response = $parsian->verify();
    update_your_payment_with($response->token,$response->order_id,$response->RNN,$response->hash_card_number);
    echo "Successful payment";
}catch (\Throwable $exception){
    //payment was unsuccessful or verification failed
    echo $exception->getMessage();
}
```

#### 4. Reversing a transaction
In case you do not verify a transaction you can reverse it.
```php
<?php
use IRbanks\Parsian;

try{
    $parsian = new Parsian($pin);
    $parsian->reverse($token);
    echo "Transaction reversed successfully";
}catch (\Throwable $exception){
    echo $exception->getMessage();
}
```