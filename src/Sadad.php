<?php

namespace IRbanks;

use IRbanks\Exceptions\SadadException;
use SoapClient;

/**
 * Sadad payment
 *
 * This class provides an easy way to establish and verify Sadad payments.
 * First call the request() method to get the payment token. The user should
 * be redirected to the Sadad payment page by a POST request, you can provide
 * a custom view with a hidden form which the end-user can submit it. Instead
 * you can call redirectToSadad() function. This will return a hidden form
 * with POST method and automatically submit it using JS.
 * 
 * Reference : https://github.com/larabook/gateway
 * 
 * Class Sadad
 * @package IRbanks\Sadad
 */
class Sadad
{
    /**
     * @var string SOAP_URL url for initializing soap client
     */
    const SOAP_URL  = "https://sadad.shaparak.ir/services/MerchantUtility.asmx?wsdl";

    /**
     * @var int $terminalId id of Sadad terminal
     */
    private $terminalId;

    /**
     * @var string $userName username of Sadad terminal
     */
    private $merchant;

    /**
     * @var string $userPassword password of Sadad terminal
     */
    private $transactionKey;

    /**
     * @var string $token payment token
     */
    private $token;

    /**
     * @var SoapClient $client Soap Client
     */
    private $client = null;

    /**
     * @param  int  $terminalID
     * @param  string  $userName
     * @param  string  $userPassword
     */
    public function __construct(int $terminalId, $merchant, $transactionKey)
    {
        $this->terminalId     = $terminalId;
        $this->merchant       = $merchant;
        $this->transactionKey = $transactionKey;
    }

    /**
     * Get Sadad soap client
     * @return SoapClient
     * @throws \Exception
     */
    public function getSoapClient()
    {
        try {
            return $this->client ? $this->client : new SoapClient(self::SOAP_URL);
        } catch (\SoapFault $e) {
            throw $e;
        }
    }

    /**
     * Set soap client
     * 
     * @param SoapClient $client
     */
    public function setSoapClient($client)
    {
        $this->client = $client;
    }

    /**
     * Request a payment
     * 
     * This function requests a payment token from Sadad using amount, order_id, and
     * call back url. The end-user should be redirected to the Sadad payment page using
     * a POST request. The HTML form which contains such a request will be sent by Sadad.
     * You can show this form($result->form) in your view or automatically submit it by
     * calling redirectToSadad() function.
     * 
     * @param  int  $amount
     * @param $callback
     * @param  int  $orderId
     * @return object
     * @throws \Exception
     */
    public function request(int $amount, $callback, int $orderId = null)
    {
        $orderId = $orderId ?? $this->uniqueNumber();
        //call Sadad
        try{
            $client = $this->getSoapClient();
            $response = $client->PaymentUtility(
                $this->merchant,
                $amount,
                $orderId,
                $this->transactionKey,
                $this->terminalId,
                $callback
            );
        }catch(\SoapFault $e){
            throw $e;
        }
        if (!isset($response['RequestKey']) || !isset($response['PaymentUtilityResult'])) {
			throw new SadadException('invalid_response', -2541);
		}else{
            $this->token = $response['PaymentUtilityResult'];
            $res = new \stdClass();
            $res->form   = $response['PaymentUtilityResult'];
		    $res->refId  = $response['RequestKey'];
            return $res;
        }

    }

    /**
     * Generate POST redirect JS script
     * 
     * This function generates a HTML page with payment confirmation form returned by
     * Sadad and submits the form automatically.
     * 
     * @return string
     */
    public function redirectScript()
    {
        $htmlCode = /** @lang XHTML */
        <<<HTML
<!DOCTYPE html><html lang="fa"><body>
%s
    <script type="text/javascript">
document.getElementById('paymentUTLfrm').submit();
</script></body></html>
HTML;

        return sprintf($htmlCode, $this->token);
    }


    /**
     * Echo redirect script
     * 
     * This function returns the automatic JS POST redirect generated by redirectScript()
     * which will automatically redirect the end-user to the Sadad payment page using a
     * POST method.
     * 
     */
    public function redirectToSadad()
    {
        echo $this->redirectScript();
    }

    /**
     * Verify payment
     * 
     * This function gets the payment result from the Sadad gateway and verifies the
     * payment if the payment was successful. After delivery of the service to the end-user
     * the payment should be settled. This method settles successful payment and you need
     * to provide the end-user with requested services or products.
     * 
     * @param  array|null  $postData
     * @return object
     * @throws \Exception
     */
    public function verify($transactionId,$refId,$amount)
    {
        try{
            $client = $this->getSoapClient();
            $result = $client->CheckRequestStatusResult(
                $transactionId,
                $this->merchant,
                $this->terminalId,
                $this->transactionKey,
                $refId,
                $amount
            );
        }catch(\SoapFault $e){
            throw $e;
        }

        if (empty($result) || !isset($result->AppStatusCode))
			throw new SadadException('unable_to_connect',-2542);

		$statusResult = strval($result->AppStatusCode);
		$appStatus = strtolower($result->AppStatusDescription);


		if ($statusResult != 0 || $appStatus !== 'commit') {
			throw new SadadException($statusResult, $appStatus);
		}
        $res = new \stdClass();
        $res->reference_id  = $refId;
		$res->tracking_code = $result->TraceNo;
		$res->card_number   = $result->CustomerCardNumber;
		return $res;
    }

    /**
     * Generate a unique number
     * 
     * It will be used in case there is no payment id
     * 
     * @return int
     */
    public function uniqueNumber()
    {
        return hexdec(uniqid());
    }
}
