<?php namespace Hansvn\Icepay;

/**
 * Basicmode
 *  
 * The Basicmode class allows you to start a basicmode payment.
 * 
 * An example of how to start a basicmode payment
 * 
 * <pre>
 * $paymentObj = new PaymentObject();
 * $paymentObj->setPaymentMethod('IDEAL')
 *            ->setAmount(1000)
 *            ->setCountry("NL")
 *            ->setLanguage("NL")
 *            ->setReference("ICEPAY Test Payment")
 *            ->setDescription("ICEPAY Test Payment")
 *            ->setCurrency("EUR")
 *            ->setIssuer('ING')
 *            ->setOrderID('icetest01');
 * 
 * $basicmode = Basicmode::getInstance();
 * $basicmode->setMerchantID(MERCHANTID) // Int
 *           ->setSecretCode(SECRETCODE) // String
 *           ->setProtocol('https')
 *           ->validatePayment($paymentObj); // Required
 * 
 * $url = $basicmode->getURL(); // This is the payment URL you must redirect your customers to.
 * </pre>
 * 
 * @version 1.0.1
 * 
 * @package API_Basicmode_Basicmode
 * @author Wouter van Tilburg 
 * @author Olaf Abbenhuis 
 * @copyright Copyright (c) 2011-2012, ICEPAY  
 */
class Basicmode extends \Hansvn\Icepay\API\Api {

    private static $instance;
    protected $_basicmodeURL = "pay.icepay.eu/basic/";
    protected $_postProtocol = "https";
    protected $_basicMode = false;
    protected $_fingerPrint = null;
    private $_checkout_version = 2;
    protected $_webservice = false;
    protected $data = null;
    protected $version = "1.0.1";
    protected $_readable_name = "Basicmode";
    protected $_api_type = "basicmode";
    private $_defaultCountryCode = "00";
    private $_generatedURL = "";
    protected $paymentObj;

    /**
     * Create an instance
     * @since version 1.0.0
     * @access public
     * @return instance of self
     */
    public static function getInstance() {
        if (!self::$instance)
            self::$instance = new self();
        return self::$instance;
    }

    /**
     * Ensure the class data is set
     * @since version 1.0.0
     * @access public
     */
    public function __construct() {
        $this->data = new \stdClass();
        //$this->setPaymentMethodsFolder(DIR . '/paymentmethods/');
    }

    /**
     * Required for using the basicmode
     * @since version 2.1.0
     * @access public
     * @param PaymentObject_Interface_Abstract $payment
     */
    public function validatePayment(PaymentObjectInterface $payment) {
        /* Clear the generated URL */
        $this->resetURL();

        $this->data = (object) array_merge((array) $this->data, (array) $payment->getData());

        if (!$payment->getPaymentMethod())
            return $this;

        $paymentmethod = $payment->getBasicPaymentmethodClass();

        if (!$this->exists($payment->getCountry(), $paymentmethod->getSupportedCountries()))
            throw new \Exception('Country not supported');

        if (!$this->exists($payment->getCurrency(), $paymentmethod->getSupportedCurrency()))
            throw new \Exception('Currency not supported');

        if (!$this->exists($payment->getLanguage(), $paymentmethod->getSupportedLanguages()))
            throw new \Exception('Language not supported');

        if (!$this->exists($payment->getIssuer(), $paymentmethod->getSupportedIssuers()) && $payment->getPaymentMethod() != null)
            throw new \Exception('Issuer not supported');

        /* used for webservice call */
        $this->paymentObj = $payment;

        return $this;
    }

    public function resetURL() {
        $this->_generatedURL = '';

        return $this;
    }

    /**
     * Post the fields and return the URL generated by ICEPAY
     * @since version 1.0.0
     * @access public
     * @return string URL or Error message
     */
    public function getURL() {

        if ($this->_generatedURL != "")
            return $this->_generatedURL;

        if (!isset($this->_merchantID))
            throw new \Exception('Merchant ID not set, use the setMerchantID() method');
        if (!isset($this->_secretCode))
            throw new \Exception('Secretcode ID not set, use the setSecretCode() method');

        if (!isset($this->data->ic_country)) {
            if (count($this->_country) == 1) {
                $this->data->ic_country = current($this->_country);
            } else
                $this->data->ic_country = $this->_defaultCountryCode;
        }

        if (!isset($this->data->ic_issuer) && isset($this->data->ic_paymentmethod)) {
            if (count($this->_issuer) == 1) {
                $this->data->ic_issuer = current($this->_issuer);
            } else
                throw new \Exception('Issuer not set, use the setIssuer() method');
        }

        if (!isset($this->data->ic_language)) {
            if (count($this->_language) == 1) {
                $this->data->ic_language = current($this->_language);
            } else
                throw new \Exception('Language not set, use the setLanguage() method');
        }

        if (!isset($this->data->ic_currency)) {
            if (count($this->_currency) == 1) {
                $this->data->ic_currency = current($this->_currency);
            } else
                throw new \Exception('Currency not set, use the setCurrency() method');
        }

        if (!isset($this->data->ic_amount))
            throw new \Exception('Amount not set, use the setAmount() method');

        if (!isset($this->data->ic_orderid))
            throw new \Exception('OrderID not set, use the setOrderID() method');

        if (!isset($this->data->ic_reference))
            $this->data->ic_reference = "";
        if (!isset($this->data->ic_description))
            $this->data->ic_description = "";

        /*
         * Dynamic URLs
         * @since 1.0.1
         */
        if (!isset($this->data->ic_urlcompleted))
            $this->data->ic_urlcompleted = "";
        if (!isset($this->data->ic_urlerror))
            $this->data->ic_urlerror = "";

        $this->data->ic_version = $this->_checkout_version;
        $this->data->ic_merchantid = $this->_merchantID;
        $this->data->chk = $this->generateCheckSumDynamic();

        /* @since version 1.0.2 */
        if ($this->_webservice) {
            if (!isset($this->data->ic_issuer) || $this->data->ic_issuer == "")
                throw new \Exception("Issuer not set");

            $ws = \Webservice\Api::getInstance()->paymentService();
            $ws->setMerchantID($this->_merchantID)
                    ->setSecretCode($this->_secretCode)
                    ->setSuccessURL($this->data->ic_urlcompleted)
                    ->setErrorURL($this->data->ic_urlerror);
            try {
                $this->_generatedURL = $ws->checkOut($this->paymentObj, true);
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
            return $this->_generatedURL;
        }

        if (isset($this->data->ic_paymentmethod)) {
            $this->_generatedURL = $this->postRequest($this->basicMode(), $this->prepareParameters());
        } else {
            $this->_generatedURL = sprintf("%s&%s", $this->basicMode(), $this->prepareParameters());
        }

        return $this->_generatedURL;
    }

    /**
     * Calls the API to generate a Fingerprint
     * @since version 1.0.0
     * @access protected
     * @return string SHA1 hash
     */
    public function generateFingerPrint() {
        if ($this->_fingerPrint != null)
            return $this->fingerPrint;
        $this->fingerPrint = sha1($this->getVersion());
        return $this->fingerPrint;
    }

    /**
     * Generates a URL to the ICEPAY basic API service
     * @since version 1.0.0
     * @access protected
     * @return string URL
     */
    protected function basicMode() {
        if (isset($this->data->ic_paymentmethod)) {
            $querystring = http_build_query(array(
                'type' => $this->data->ic_paymentmethod,
                'checkout' => 'yes',
                'ic_redirect' => 'no',
                'ic_country' => $this->data->ic_country,
                'ic_language' => $this->data->ic_language,
                'ic_fp' => $this->generateFingerPrint()
                    ), '', '&');
        } else {
            $querystring = http_build_query(array(
                'ic_country' => $this->data->ic_country,
                'ic_language' => $this->data->ic_language,
                'ic_fp' => $this->generateFingerPrint()
                    ), '', '&');
        }

        return sprintf("%s://%s?%s", $this->_postProtocol, $this->_basicmodeURL, $querystring);
    }

    /**
     * Used to connect to the ICEPAY servers
     * @since version 1.0.0
     * @access protected
     * @param string $url
     * @param array $data
     * @return string Returns a response from the specified URL
     */
    protected function postRequest($url, $data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response)
            throw new \Exception("Error reading $url");

        if (( substr(strtolower($response), 0, 7) == "http://" ) || ( substr(strtolower($response), 0, 8) == "https://" )) {
            return $response;
        }
        else
            throw new \Exception("Server response: " . strip_tags($response));
    }

    /**
     * Generate checksum for basicmode checkout
     * @since version 1.0.0
     * @access protected
     * @return string SHA1 encoded
     */
    protected function generateCheckSum() {
        return sha1(
                        sprintf("%s|%s|%s|%s|%s|%s|%s", $this->_merchantID, $this->_secretCode, $this->data->ic_amount, $this->data->ic_orderid, $this->data->ic_reference, $this->data->ic_currency, $this->data->ic_country
                        )
        );
    }

    /**
     * Generate checksum for basicmode checkout using dynamic urls
     * @since version 1.0.1
     * @access protected
     * @return string SHA1 encoded
     */
    protected function generateCheckSumDynamic() {
        return sha1(
                        sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s", $this->_merchantID, $this->_secretCode, $this->data->ic_amount, $this->data->ic_orderid, $this->data->ic_reference, $this->data->ic_currency, $this->data->ic_country, $this->data->ic_urlcompleted, $this->data->ic_urlerror
                        )
        );
    }

    /**
     * Create the query string
     * @since version 1.0.0
     * @access protected
     * @return string
     */
    protected function prepareParameters() {
        return http_build_query($this->data, '', '&');
    }

    /**
     * Set the protocol for local testing
     * @since version 1.0.0
     * @access public
     * @param string $protocol [http|https]
     */
    public function setProtocol($protocol = "https") {
        $this->_postProtocol = $protocol;
        return $this;
    }

    /**
     * Use the webservice for the Call
     * @since version 1.0.2
     * @access public
     */
    public function useWebservice() {
        $this->_webservice = true;
        return $this;
    }

}