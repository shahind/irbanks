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
    $mellat = new \IRbanks\Mellat\Mellat($terminalId, $userName, $userPassword);
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
    $mellat = new \IRbanks\Mellat\Mellat($terminalId, $userName, $userPassword);
    $response = $mellat->verify();
    
    //successful payment. save $response info like reference id($response->reference_id)
    echo "successful payment";
}catch(\Throwable $e){
    echo "error: ".$e->getMessage();
}
```