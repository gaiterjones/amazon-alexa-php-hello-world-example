<?php
/**
 *
 *  Copyright (C) 2017
 *
 *
 *  @who	   	PAJ
 *  @info   	paj@gaiterjones.com
 *  @license    blog.gaiterjones.com
 *
 *
 *
 */
namespace PAJ\Application\AmazonDev\Alexa;
use PAJ\Application\AmazonDev\Security;

class ValidateAlexaRequest implements Security
{
    public $__environment;
    protected $__config;
    protected $__log;

    public function __construct
	(


	)
    {
    }

    public function loadSecurity($_environment)
    {
        $this->loadEnvironment($_environment);

        if ($this->get('debug')){$this->debug();}

        try {

            $this->validateAlexaRequest();

        } catch (ValidateRequestException  $e) {

            $this->exceptionError($e,'ValidateRequestException');
        }
    }

    //
    // Validations based on API documentation at:
    // https://developer.amazon.com/appsandservices/solutions/alexa/alexa-skills-kit/docs/developing-an-alexa-skill-as-a-web-service#Checking%20the%20Signature%20of%20the%20Request
    //
    //
    //
    private function validateAlexaRequest()
    {

        $_alexaRequest=$this->get('alexarequest');

        $this->validateEnvironment();
        $this->validateSourceIPAddress();
        $this->validatePostRequest($_alexaRequest);
        $this->validateApplicationID($_alexaRequest);
        $this->validateSignature();
        $this->validateKeychainUri($_SERVER['HTTP_SIGNATURECERTCHAINURL']);
        $this->validateCertificate();
        $this->validateTimestamp($_alexaRequest);

    }

    private function validateEnvironment()
    {
        if (php_sapi_name() === 'cli') {exit;}
    }

    private function validateSourceIPAddress()
    {
        if (!filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE))
        {
            throw new ValidateRequestException('Request source IP address is not allowed.');
        }
    }

    private function validatePostRequest($_alexaRequest)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET')
        {
            throw new ValidateRequestException('HTTP GET when POST was expected');
        }

        if (!is_array($_alexaRequest)) { throw new ValidateRequestException('Invalid alexa request data.'); }
    }

    private function validateApplicationID($_alexaRequest)
    {
        $_applicationId      = @$_alexaRequest['session']['application']['applicationId'];

        $_myApplicationIds=explode(',',$this->__config->get('amazonSkillId'));
        if (!in_array($_applicationId,$_myApplicationIds))
        {
            throw new ValidateRequestException('Invalid Application id: ' . $_applicationId);
        }
    }

    private function validateSignature()
    {
        // validate signature data key
        //
        if (!isset($_SERVER['HTTP_SIGNATURECERTCHAINURL']))
        {
            throw new ValidateRequestException('HTTP_SIGNATURECERTCHAINURL key not present');
        }

        // validate signature data
        // for testing see https://github.com/AreYouFreeBusy/AlexaSkillsKit.NET/issues/5

        if (empty($_SERVER['HTTP_SIGNATURECERTCHAINURL']) ||
            is_null($_SERVER['HTTP_SIGNATURECERTCHAINURL']) ||
            $_SERVER['HTTP_SIGNATURECERTCHAINURL']=='null' ||
            $_SERVER['HTTP_SIGNATURECERTCHAINURL']==''
            )
        {
            throw new ValidateRequestException('HTTP_SIGNATURECERTCHAINURL data not present');
        }

    }

    private function validateCertificate()
    {
        // Determine if we need to download a new Signature Certificate Chain from Amazon
        //
        $_md5pem = md5($_SERVER['HTTP_SIGNATURECERTCHAINURL']);
        $_md5pem = $_md5pem . '.pem';

        // If we haven't received a certificate with this URL before, store it as a cached copy
        //
        if (!file_exists($this->get('amazonLogFolder').$_md5pem)) {
            file_put_contents($this->get('amazonLogFolder').$_md5pem, file_get_contents($_SERVER['HTTP_SIGNATURECERTCHAINURL']));
        }

        // Validate certificate chain and signature
        //
        $_pem = file_get_contents($this->get('amazonLogFolder').$_md5pem);
        $_ssl_check = openssl_verify($this->get('alexajsonrequest'), base64_decode($_SERVER['HTTP_SIGNATURE']), $_pem, 'sha1');
        if ($_ssl_check != 1)
        {
            throw new ValidateRequestException(openssl_error_string());
        }

        // Parse certificate
        //
        $_parsedCertificate = openssl_x509_parse($_pem);
        if (!$_parsedCertificate)
        {
            throw new ValidateRequestException('x509 certificate parse failed');
        }

        // Check that the domain echo-api.amazon.com is present in the Subject Alternative Names (SANs) section of the signing certificate
        //
        if(strpos($_parsedCertificate['extensions']['subjectAltName'], $this->__config->get('amazonEchoServiceDomain')) === false)
        {
            throw new ValidateRequestException('subjectAltName Check Failed');
        }

        // Check that the signing certificate has not expired (examine both the Not Before and Not After dates)
        //
        $_validFrom = $_parsedCertificate['validFrom_time_t'];
        $_validTo   = $_parsedCertificate['validTo_time_t'];

        $_now = new \DateTime(null, new \DateTimeZone('UTC'));
        $_time = $_now->getTimestamp();

        if (!($_validFrom <= $_time && $_time <= $_validTo)) {
            throw new ValidateRequestException('certificate expiration check failed');
        }

    }

    // Validate proper format of Amazon provided certificate chain url
    //
	private function validateKeychainUri($keychainUri){


		$uriParts = parse_url($keychainUri);

		if (strcasecmp($uriParts['host'], 's3.amazonaws.com') != 0)
		{
			throw new ValidateRequestException('The host for the Certificate provided in the header is invalid');
		}

		if (strpos($uriParts['path'], '/echo.api/') !== 0)
		{
			throw new ValidateRequestException('The URL path for the Certificate provided in the header is invalid');
		}

		if (strcasecmp($uriParts['scheme'], 'https') != 0)
		{
			throw new ValidateRequestException('The URL is using an unsupported scheme. Should be https');
		}

		if (array_key_exists('port', $uriParts) && $uriParts['port'] != '443')
		{
			throw new ValidateRequestException('The URL is using an unsupported https port');
		}

	}

    private function validateTimestamp($_alexaRequest)
    {
            if (!isset($_alexaRequest['request']['timestamp'])) {
                throw new ValidateRequestException('No timestamp detected.');
            }

			// Check the timestamp of the request and ensure it was within the past minute
			//
			$_alexaRequestTimestampData=$_alexaRequest['request']['timestamp'];
			if (strlen($_alexaRequestTimestampData) === 13)
			{
				$_alexaRequestTimestampData=$_alexaRequest['request']['timestamp'] / 1000;
				$_alexaRequestTimestamp=\DateTime::createFromFormat("U", (int)$_alexaRequestTimestampData);
			} else {
				$_alexaRequestTimestamp = new \DateTime($_alexaRequestTimestampData, new \DateTimeZone('UTC'));
			}

            $_now = new \DateTime(null, new \DateTimeZone('UTC'));

			if (($_now->getTimestamp() - $_alexaRequestTimestamp->getTimestamp()) > 60)
			{
				throw new ValidateRequestException('timestamp validation failure: current time: ' . $_now->format('Y-m-d\TH:i:s\Z') . ' vs. timestamp: ' . $_alexaRequestTimestamp->format('Y-m-d\TH:i:s\Z'));
			}
    }

    private function debug()
    {
        $_alexaRequest=$this->get('alexarequest');
        $_now = new \DateTime(null, new \DateTimeZone($this->__config->get('timezone')));

        //
        // debug alexa request to log file
        //
        $_request = $_now->format(\DateTime::RFC1123) . "\n";

        //
        // Log request headers apache/nginx
        //
        $_headers = $this->get_request_headers();

        foreach ($_headers as $_header => $_value) {
           $_request .= "$_header: $_value \n";
        }

        // HTTP POST Data
        $_request .= "HTTP Raw Data: ";
        $_request .= $this->get('alexajsonrequest');

        // PHP Array from JSON
        $_request .= "\n\nPHP Array from JSON: ";
        $_request .= print_r($this->get('alexarequest'), true);

        if (isset($_alexaRequest['request']['timestamp']))
        {
            // TIMESTAMP DEBUG
            //
            //
            $_now = new \DateTime(null, new \DateTimeZone('UTC'));

            $_alexaRequestTimestampData=$_alexaRequest['request']['timestamp'];
            if (strlen($_alexaRequestTimestampData) === 13)
            {
                $_alexaRequestTimestampData=$_alexaRequest['request']['timestamp'] / 1000;
                $_alexaRequestTimestamp=\DateTime::createFromFormat("U", (int)$_alexaRequestTimestampData);
            } else {
                $_alexaRequestTimestamp = new \DateTime($_alexaRequestTimestampData, new \DateTimeZone('UTC'));
            }
            $_request .= "\n\nTIMESTAMPS: ";
            $_request .= "AMAZON:". $_alexaRequestTimestamp->getTimestamp(). ' ME:'. $_now->getTimestamp()."\n";
            $_request .= "TIMESTAMP COMPARISON:". $_alexaRequestTimestamp->format('Y-m-d\TH:i:s\Z'). ' = '.  $_now->format('Y-m-d\TH:i:s\Z')."\n\n";
        }

            $this->__log->writeLogFile($_request,$this->get('amazonLogFile'));
    }


    private function get_request_headers() {

		// Based on: http://www.iana.org/assignments/message-headers/message-headers.xml#perm-headers
		//
		$arrCasedHeaders = array(
			// HTTP
			'Dasl'             => 'DASL',
			'Dav'              => 'DAV',
			'Etag'             => 'ETag',
			'Mime-Version'     => 'MIME-Version',
			'Slug'             => 'SLUG',
			'Te'               => 'TE',
			'Www-Authenticate' => 'WWW-Authenticate',
			// MIME
			'Content-Md5'      => 'Content-MD5',
			'Content-Id'       => 'Content-ID',
			'Content-Features' => 'Content-features',
		);
		$arrHttpHeaders = array();

		foreach($_SERVER as $strKey => $mixValue) {
			if('HTTP_' !== substr($strKey, 0, 5)) {
				continue;
			}

			$strHeaderKey = strtolower(substr($strKey, 5));

			if(0 < substr_count($strHeaderKey, '_')) {
				$arrHeaderKey = explode('_', $strHeaderKey);
				$arrHeaderKey = array_map('ucfirst', $arrHeaderKey);
				$strHeaderKey = implode('-', $arrHeaderKey);
			}
			else {
				$strHeaderKey = ucfirst($strHeaderKey);
			}

			if(array_key_exists($strHeaderKey, $arrCasedHeaders)) {
				$strHeaderKey = $arrCasedHeaders[$strHeaderKey];
			}

			$arrHttpHeaders[$strHeaderKey] = $mixValue;
		}

		return $arrHttpHeaders;

	}

    private function exceptionError($e,$_type='Exception')
    {

        if ($this->get('debug'))
        {
            // log errors
            //
            $this->__log->writeLogFile($e->getMessage(),$this->get('amazonLogFile'));

        }

        if(isset($_GET['debug']))
        {
            $_now = new \DateTime(null, new \DateTimeZone('UTC'));

            // show debug info
            //
            echo '
<h1>'. __CLASS__. '</h1>
<h1>debug</h1>
<pre>
    time : '. $_now->format('Y-m-d\TH:i:s\Z'). '
    error :	'.  $e->getMessage(). '
    debug :	logfolder '. (is_writable($this->get('amazonLogFolder')) ? 'is writeable' : 'is NOT writeable'). '
</pre>
<p>Error <em>HTTP GET when POST was expected</em> is normal when browsing this page, use curl from command line to debug validation with POST data :</p>
<pre style="white-space: pre-wrap; white-space: -moz-pre-wrap; white-space: -pre-wrap; white-space: -o-pre-wrap; word-wrap: break-word;">
curl -H "Content-Type: application/json" -H "SignatureCertChainUrl: https://s3.amazonaws.com/echo.api/echo-api-cert-2.pem" -H "Signature: OMEN68E8S0H9vTHRBVQMmWxeXLV8hpQoodoU6NdLAUB12BjGVvOAgCq7LffPDKCW7zXI6wRc3dx0pklYWqZHXbNsMfx8xSN3lqJTYw6zLZGwt2MgcjajHa1AnMbTnZOjrq9WPZuFG0pyJj9ucKB0w/k4r123vOLzVI0pEISo3WTIDsfKMycIpGiNcDHdJIc2LQGG5Bum9TFJuUllpt5c5LQC9g1rKIS2nj55QCQ8a3EeeqDe3N85Sw6OT7k7oPkKVLPee5fAWfkQQqW1fmA7sGIWKDpVTi1Jq46I2MiJM+48m+rxOVEPXky3j8u8+lPWg6vOnKogoXTb52foAurmAA==" -X POST -d "{\"version\":\"1.0\",\"session\":{\"new\":true,\"sessionId\":\"amzn1.echo-api.session\",\"application\":{\"applicationId\":\"'.$this->__config->get('amazonSkillId').'\"},\"user\":{\"userId\":\"'. $this->__config->get('amazonUserId'). '\",\"accessToken\":\"token\"}},\"request\":{\"type\":\"'. $_type. '\",\"requestId\":\"amzn1.echo-api.request\",\"timestamp\":\"'. $_now->format('Y-m-d\TH:i:s\Z'). '\"}}" --verbose https://'
    . $_SERVER['HTTP_HOST'].str_replace('?'.$_SERVER['QUERY_STRING'],'',$_SERVER['REQUEST_URI']). '
</pre>
<a href="http://blog.gaiterjones.com">blog.gaiterjones.com</a>
            ';

        exit;

        }

        // validation failure
        //
        header('alexa request validation error : ' .$e->getMessage(), true, 400);
        exit;
    }

    //
    //
    // ENVIRONMENT
    //
    //
    /**
     * load the environment
     *
     * @param  array $_data envrionment data and objects
     * @return void
     */
        protected function loadEnvironment($_environment)
        {

            //
            // load class variables into $this->__environment['data']
            //
            $_data=$_environment['data'];
            if ($_data)
            {
                foreach ($_data as $key => $value)
                {
                    $this->set($key,$value);
                }
            }

            //
            // load objects
            //
            $this->loadObjects($_environment['objects']);
        }

    /**
     * Load envrionment objects i.e. $this->__config
     *
     * @param  array $objects array of objects
     * @return void
     */
        protected function loadObjects($objects)
        {
            foreach($objects as $key => $value)
            {
                $obj='__'. $key;
                $this->$obj=$value;
            }

            $this->setObjects($objects);
        }
    /**
     * Set environment objects for injection into other method_exists
     *
     * @param array $objects array of objects name $key, object $value
     */
        public function setObjects($objects)
        {
            foreach($objects as $key => $value)
            {
                $this->__environment['objects'][$key] = $value;
            }
        }

        public function set($key,$value)
        {
            $this->__environment['data'][$key] = $value;
        }

        public function get($variable)
        {
            return $this->__environment['data'][$variable];
        }
}
