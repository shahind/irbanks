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

//This function generates a JS script which creates a hidden HTML form with POST method to redirect the end-user to the Mellat payment page.
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

### Asan Pardakht
Asan Pardakht payment has 3 main steps; getting the payment toke, verifying the payment, and settling the payment.

#### 1. Get payment token
```php
try{
    $AP = new \IRbanks\Asanpardakht($merchantId, $username, $password, $aesKey, $aesIV);
    $response = $AP->request($amount, $callback_url, $order_id);
}catch(\Throwable $e){
    echo "error: ".$e->getMessage();
}
```

#### 2. Redirect user to payment page
```php
//use $response info like token($response->token) and refId($response->refID) to create a HTML form with POST method
//or automatically do it using redirectToAsanpardakht() function.
    $response->redirectToAsanpardakht();

//This function generates a JS script which creates a hidden HTML form with POST method to redirect the end-user to the Asanpardakht payment page.
```

#### 3. Verify payment
```php
try{
    $AP = new \IRbanks\Asanpardakht($merchantId, $username, $password, $aesKey, $aesIV);
    $optional_REQUEST_parameter = Request::input('ReturningParams'); //This is an optional parameter, if not set, the $_POST will be used
    $response = $AP->verify($optional_REQUEST_parameter);

    // OR
    $response = $AP->verify();

    update_your_payment_with($response->reference_id,$response->order_id,$response->card_number,$response->asanpardakht_transaction_id);
    echo "successful payment";
}catch(\Throwable $e){
    //payment was unsuccessful or verification failed
    echo "error: ".$e->getMessage();
}
```

### Sadad (Bank Melli)
Sadad payment has 3 main steps; getting the payment toke, redirecting user to payment page, and verifying the payment.

#### 1. Get payment token
Note: Your callback url should contain order_id cause Sadad does not return it to call back url.
```php
try{
    $Sadad = new \IRbanks\Sadad($terminalId, $merchant, $transactionKey);
    $response = $Sadad->request($amount, $callback_url, $order_id);
}catch(\Throwable $e){
    echo "error: ".$e->getMessage();
}
```

#### 2. Redirect user to payment page
```php
//use $response info like token($response->token) and refId($response->refID) to create a HTML form with POST method
//or automatically do it using redirectToAsanpardakht() function.
    $response->redirectToAsanpardakht();

//This function generates a JS script which creates a hidden HTML form with POST method to redirect the end-user to the Asanpardakht payment page.
```

#### 3. Verify payment
```php
try{
    $AP = new \IRbanks\Asanpardakht($merchantId, $username, $password, $aesKey, $aesIV);
    $optional_REQUEST_parameter = Request::input('ReturningParams'); //This is an optional parameter, if not set, the $_POST will be used
    $response = $AP->verify($optional_REQUEST_parameter);

    // OR
    $response = $AP->verify();
    
    update_your_payment_with($response->reference_id,$response->order_id,$response->card_number,$response->asanpardakht_transaction_id);
    echo "successful payment";
}catch(\Throwable $e){
    //payment was unsuccessful or verification failed
    echo "error: ".$e->getMessage();
}
```