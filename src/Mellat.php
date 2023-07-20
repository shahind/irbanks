<?php

namespace IRbanks;

use IRbanks\Exceptions\MellatException;
use SoapClient;

/**
 * Mellat payment
 *
 * This class provides an easy way to establish and verify Mellat payments.
 * First call the request() method to get the payment token. The user should
 * be redirected to the Mellat payment page by a POST request, you can provide
 * a custom view with a hidden form which the end-user can submit it. Instead
 * you can call redirectToMellat() function. This will return a hidden form
 * with POST method and automatically submit it using JS.
 * 
 * Reference : https://github.com/dpsoft-official/mellat
 * 
 * Class Mellat
 * @package IRbanks\Mellat
 */
class Mellat
{
    /**
     * @var string SOAP_URL url for initializing soap client
     */
    const SOAP_URL  = "https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl";

    /**
     * @var string PAY_URL Url for initializing payment by end-user
     */
    const PAY_URL   = "https://bpm.shaparak.ir/pgwchannel/startpay.mellat";

    /**
     * @var string NAMESPACE Url for Mellat namespace
     */
    const NAMESPACE = "http://interfaces.core.sw.bps.com/";

    /**
     * @var int $terminalId id of Mellat terminal
     */
    private $terminalId;

    /**
     * @var string $userName username of Mellat terminal
     */
    private $userName;

    /**
     * @var string $userPassword password of Mellat terminal
     */
    private $userPassword;

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
    public function __construct(int $terminalId, $userName, $userPassword)
    {
        $this->terminalId   = $terminalId;
        $this->userName     = $userName;
        $this->userPassword = $userPassword;
    }

    /**
     * Get Mellat soap client
     * @return SoapClient
     * @throws \Exception
     */
    public function getSoapClient()
    {
        try {
            return $this->client ? $this->client : new SoapClient(self::SOAP_URL);
        } catch (\SoapFault $e) {
            throw new \Exception('SoapFault: '.$e->getMessage().' #'.$e->getCode(), $e->getCode());
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
     * This function requests a payment token from Mellat using amount, order_id, and
     * call back url. The end-user should be redirected to the Mellat payment page using
     * a POST request. you can provide a custom view with a hidden form which the 
     * end-user can submit it. Instead you can call redirectToMellat() function. This 
     * will return a hidden form with POST method and automatically submit it using JS.
     * 
     * @param  int  $amount
     * @param $callback
     * @param  int  $orderId
     * @return object
     * @throws \Exception
     */
    public function request(int $amount, $callback, int $orderId = null)
    {
        $client = $this->getSoapClient();
        $payParam = [
            'terminalId' => $this->terminalId,
            'userName' => $this->userName,
            'userPassword' => $this->userPassword,
            'orderId' => $orderId ? $orderId : $this->uniqueNumber(),
            'amount' => $amount,
            'localDate' => date("Ymd"),
            'localTime' => date("His"),
            'additionalData' => "",
            'callBackUrl' => $callback,
            'payerId' => 0,
        ];
        $result = $client->bpPayRequest($payParam, self::NAMESPACE);

        $response = $this->getResponse($result);

        if ($response[0] == 0) {
            $this->token   = $response[1];
            $res->order_id = $payParam['orderId'];
            $res->token    = $response[1];
            return $res;
        } else {
            throw new MellatException($response[0]);
        }

    }

    /**
     * Generate POST redirect JS script
     * 
     * This function generates a JS script which creates a hidden HTML form
     * with POST method to redirect the end-user to the Mellat payment page.
     * 
     * @return string
     */
    public function redirectScript()
    {
        $jsScript = /** @lang XHTML */
            <<<JS
<!DOCTYPE html><html lang="fa"><body>
                <script>
var form = document.createElement("form");
form.setAttribute("method", "POST");
form.setAttribute("target", "_self");

var hiddenField = document.createElement("input");
hiddenField.setAttribute("type", "hidden");
hiddenField.setAttribute("value", "%s");
form.setAttribute("action", "%s");
hiddenField.setAttribute("name", "RefId");

form.appendChild(hiddenField);
document.body.appendChild(form);
form.submit();
</script></body></html>
JS;

        return sprintf($jsScript, $this->token, self::PAY_URL);
    }


    /**
     * Echo redirect script
     * 
     * This function returns the automatic JS POST redirect generated by redirectScript()
     * which will automatically redirect the end-user to the Mellat payment page using a
     * POST method.
     * 
     */
    public function redirectToMellat()
    {
        echo $this->redirectScript();
    }

    /**
     * Verify payment
     * 
     * This function gets the payment result from the Mellat gateway and verifies the
     * payment if the payment was successful. After delivery of the service to the end-user
     * the payment should be settled. This method settles successful payment and you need
     * to provide the end-user with requested services or products.
     * 
     * @param  array|null  $postData
     * @return object
     * @throws \Exception
     */
    public function verify(array $postData = null)
    {
        $data = $postData ? $postData : $_POST;

        $RefId = $data['RefId'] ?? null;
        $ResCode = intval($data['ResCode'] ?? null);
        $saleOrderId = intval($data['SaleOrderId'] ?? null);
        $SaleReferenceId = $data['SaleReferenceId'] ?? null;

        if ($ResCode == 0) {

            $parameters = [
                'terminalId' => $this->terminalId,
                'userName' => $this->userName,
                'userPassword' => $this->userPassword,
                'orderId' => $saleOrderId,
                'saleOrderId' => $saleOrderId,
                'saleReferenceId' => $SaleReferenceId,
            ];

            $client = $this->getSoapClient();
            $result = $client->bpVerifyRequest($parameters, self::NAMESPACE);

            $response = $this->getResponse($result);

            if ($response[0] == 0) {
                $result = $client->bpSettleRequest($parameters, self::NAMESPACE);
                $response = $this->getResponse($result);
                if ($response[0] == 0) {
                    $res->reference_id = $RefId;
                    $res->card_number  = null;
                    $res->order_id     = $saleOrderId;
                    return $res;
                } else {
                    throw new MellatException($response[0]);
                }
            } else {
                throw new MellatException($response[0]);
            }
        } else {
            throw new MellatException($ResCode);
        }
    }

    /**
     * Get response from Mellat
     * 
     * Checks if the response is valid and returns the return values.
     * 
     * @param $result
     * @return array
     * @throws \Exception
     */
    private function getResponse($result)
    {
        if (!isset($result->return)) {
            throw new MellatException(0);
        }

        return explode(',', $result->return);
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
