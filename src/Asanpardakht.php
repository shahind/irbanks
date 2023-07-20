<?php

namespace IRbanks;

use IRbanks\Exceptions\AsanpardakhtException;
use SoapClient;

/**
 * Asanpardakht payment
 *
 * This class provides an easy way to establish and verify Asanpardakht payments.
 * First call the request() method to get the payment token. The user should
 * be redirected to the Asanpardakht payment page by a POST request, you can provide
 * a custom view with a hidden form which the end-user can submit it. Instead
 * you can call redirectToAsanpardakht() function. This will return a hidden form
 * with POST method and automatically submit it using JS.
 *  
 * Reference: https://github.com/larabook/gateway
 * 
 * Class Asanpardakht
 * @package IRbanks\Asanpardakht
 */
class Asanpardakht
{
    /**
     * @var string SOAP_URL url for initializing soap client
     */
    const SOAP_URL  = "https://services.asanpardakht.net/paygate/merchantservices.asmx?wsdl";

    /**
     * @var string INTERNAL_SOAP_URL url for initializing soap client for internal functions
     */
    const INTERNAL_SOAP_URL = "https://services.asanpardakht.net/paygate/internalutils.asmx?WSDL";

    /**
     * @var string PAY_URL Url for initializing payment by end-user
     */
    const PAY_URL   = "https://asan.shaparak.ir";

    /**
     * @var int $terminalId id of Asanpardakht terminal
     */
    private $merchantId;

    /**
     * @var string $userName username of Asanpardakht terminal
     */
    private $username;

    /**
     * @var string $userPassword password of Asanpardakht terminal
     */
    private $password;

    /**
     * @var string $key aes encryption key
     */
    private $key;

    /**
     * @var string $iv aes encryption vector
     */
    private $iv;

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
    public function __construct(int $merchantId, $username, $password, $aesKey, $aesIV)
    {
        $this->merchantId   = $merchantId;
        $this->username     = $username;
        $this->password = $password;
        $this->key          = $aesKey;
        $this->iv           = $aesIV;
    }

    /**
     * Get Asanpardakht soap client
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
     * This function requests a payment token from Asanpardakht using amount, order_id, and
     * call back url. The end-user should be redirected to the Asanpardakht payment page using
     * a POST request. you can provide a custom view with a hidden form which the 
     * end-user can submit it. Instead you can call redirectToAsanpardakht() function. This 
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
        $orderId = $orderId ?? $this->uniqueNumber();
        $client = $this->getSoapClient();

        $localDate = date("Ymd His");
        $additionalData = "";
        $req = "1,{$this->username},{$this->password},{$orderId},{$amount},{$localDate},{$additionalData},{$callback},0";
        $encryptedRequest = $this->encrypt($req);

        $payParam = [
            'merchantConfigurationID' => $this->merchantId,
            'encryptedRequest' => $encryptedRequest,
        ];
        try{
            $result = $client->RequestOperation($payParam);
            $response = $this->RequestOperationResult($result);
        }catch(\SoapFault $e){
            throw $e;
        }

        $responseCode = explode(",", $response)[0];
        if ($responseCode != '0') {
            throw new AsanpardakhtException((int)$responseCode);
        }else{
            $this->token = substr($response, 2);
            $res->refId = substr($response, 2);
            return $res;
        }
    }

    /**
     * Generate POST redirect JS script
     * 
     * This function generates a JS script which creates a hidden HTML form
     * with POST method to redirect the end-user to the Asanpardakht payment page.
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
     * which will automatically redirect the end-user to the Asanpardakht payment page using a
     * POST method.
     * 
     */
    public function redirectToAsanpardakht()
    {
        echo $this->redirectScript();
    }

    /**
     * Verify payment
     * 
     * This function gets the payment result from the Asanpardakht gateway and verifies the
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
        //get returning parameters
        $data = $postData ? $postData : $_POST;
        $parameters = $data['ReturningParams'];

        //decrypt parameters
        $parameters = $this->decrypt($parameters);

        //extract parameters
        $parameters = explode(",", $parameters);
        $amount = $paramsArray[0];
        $orderId = $paramsArray[1];
        $refId = $paramsArray[2];
        $resCode = $paramsArray[3];
        $resMessage = $paramsArray[4];
        $gatewayTransactionId = $paramsArray[5];
        $RRN = $paramsArray[6];
        $lastFourDigitOfPAN = $paramsArray[7];


        if ($resCode == '0' || $resCode == '00') {

            //verify payment
            $encryptedCredentials = $this->encrypt("{$this->username},{$this->password}");
            $parameters = [
                'merchantConfigurationID' => $this->merchantId,
                'encryptedCredentials' => $encryptedCredentials,
                'payGateTranID' => $gatewayTransactionId
            ];
            
            try{
                $client = $this->getSoapClient();
                $response = $client->RequestVerification($parameters);
                $response = $response->RequestVerificationResult;
            }catch(\SoapFault $e){
                throw $e;
            }

            //settle payment
            if ($response == '500') {
                $response = $client->RequestReconciliation($parameters);
                $response = $response->RequestReconciliationResult;
                
                if ($response == '600') {
                    $res->reference_id                = $refId;
                    $res->card_number                 = $lastFourDigitOfPAN;
                    $res->order_id                    = $orderId;
                    $res->asanpardakht_transaction_id = $gatewayTransactionId;
                    return $res;
                } else {
                    throw new AsanpardakhtException((int)$response);
                }
            } else {
                throw new AsanpardakhtException((int)$response);
            }
        } else {
            throw new AsanpardakhtException((int)$resCode);
        }
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

    /**
     * Encrypt string by key and iv from config
     *
     * @param string $string
     * @return string
     */
    private function encrypt($string = "")
    {
        try {

            $soap = new SoapClient(self::INTERNAL_SOAP_URL);
            $params = array(
                'aesKey' => $this->key,
                'aesVector' => $this->iv,
                'toBeEncrypted' => $string
            );
            $response = $soap->EncryptInAES($params);
            return $response->EncryptInAESResult;
        } catch (\SoapFault $e) {
            return "";
        }
    }


    /**
     * Decrypt string by key and iv from config
     *
     * @param string $string
     * @return string
     */
    private function decrypt($string = "")
    {
        try {

            $soap = new SoapClient(self::INTERNAL_SOAP_URL);
            $params = array(
                'aesKey' => $this->key,
                'aesVector' => $this->iv,
                'toBeDecrypted' => $string
            );
            $response = $soap->DecryptInAES($params);
            return $response->DecryptInAESResult;
        } catch (\SoapFault $e) {
            return "";
        }
    }
}
