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
 *  process and respond to amazon alexa requests
 *
 */
namespace PAJ\Application\Amazon;

/* ALEXA REQUEST */

class AlexaRequest
{
	
	protected $__;
	protected $__config;
	
	public function __construct() {
		
		try
		{
			$this->set('errorMessage','');
			
			$this->loadConfig();
			
			$this->getAlexaRequest();
			
			$this->validateAlexaRequest();	

			$this->renderAlexaResponse();
		
		}
		
		// catch errors
		//
		catch (\Exception $e)
	    
		{
	    	$this->set('errorMessage', 'ERROR : '. $e->getMessage(). "\n". $this->getExceptionTraceAsString($e));
			
			// log errors
			//
			$_logToFile=new \PAJ\Library\Log\LogToFile(
				array(
					'logfile' => $this->get('amazonLogFile'),
					'data' => $this->get('errorMessage')
				));
					unset($_logToFile);	
					
			// debug
			//
			if(isset($_GET['debug']))
			{
				echo '
					<h1>'. __CLASS__. '</h1>
					<pre>
						error :
						'.  $e->getMessage(). '
						debug : 
						logfolder '. (is_writable($this->get('amazonLogFolder')) ? 'is writeable' : 'is NOT writeable'). ' 
					</pre>
				';

			} else {
				
				if ($this->get('validalexarequest') === 'true')
				{
					// error response to alexa
					//
					$this->respond('Sorry, an Error has occured. The error has been logged.');
					
				} else {
					
					// validation failure
					//
					exit;
				}
			
			}
		
			exit;
	    }
	}
	
	//
	// load config data
	//
	private function loadConfig()
	{
		$this->__config= new config();
		
		$_version='BETA v0.0.1';
		$_versionNumber=explode('-',$_version);
		$_versionNumber=$_versionNumber[0];
		
		$this->set('version',$_version);
		$this->set('versionNumber',$_versionNumber);
		$this->set('amazonLogFile',$this->__config->get('amazonCacheFolder'). 'alexarequest');
		$this->set('amazonLogFolder',$this->__config->get('amazonCacheFolder'));
		$this->set('applicationURL',$this->__config->get('applicationURL'));
		$this->set('applicationName',$this->__config->get('applicationName'));
	}

	//
	// get alexa request data
	//	
	private function getAlexaRequest()
	{
		$this->set('alexarequest','false');
		
		// Get Amazon POST JSON data
		//
		$_jsonRequest    = file_get_contents('php://input');
		$_data           = json_decode($_jsonRequest, true);
		
		$this->set('alexarequest',$_data);
		$this->set('alexajsonrequest',$_jsonRequest);
		
			$_debug=true; // enable for debug logging
			
			if ($_debug)
			{
					
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
				
				$_logToFile=new \PAJ\Library\Log\LogToFile(
					array(
						'logfile' => $this->get('amazonLogFile'),
						'data' => $_request
					));
						unset($_logToFile);	
			}			
	}
	
	//
	// Validate Alexa request
	//
	private function validateAlexaRequest()
	{
		if (php_sapi_name() === 'cli') {exit;}
		
		$this->set('validalexarequest','false');
		
		//
		// Validations based on API documentation at:
		// https://developer.amazon.com/appsandservices/solutions/alexa/alexa-skills-kit/docs/developing-an-alexa-skill-as-a-web-service#Checking%20the%20Signature%20of%20the%20Request
		//

		// validate post request
		//
		if ($_SERVER['REQUEST_METHOD'] == 'GET') throw new \Exception('HTTP GET when POST was expected');

		// validate public ip address space
		//
		if (filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)) {
			
			$_alexaRequest=$this->get('alexarequest');
			
			$_sessionId          = @$_alexaRequest['session']['sessionId'];
			$_applicationId      = @$_alexaRequest['session']['application']['applicationId'];
			$_userId             = @$_alexaRequest['session']['user']['userId'];
			$_requestTimestamp   = @$_alexaRequest['request']['timestamp'];				
			
			if (!is_array($_alexaRequest)) { throw new \Exception('Invalid alexa request data.'); }

			// validate application id
			//
			if ($_applicationId != $this->__config->get('amazonSkillId')) throw new \Exception('Invalid Application id: ' . $_applicationId);

			// validate user id
			// for private skill dev
			//
			//if ($_userId != $this->__config->get('amazonUserId')) throw new \Exception('Invalid User id: ' . $userId);

			// validate signature data key
			//
			if (!isset($_SERVER['HTTP_SIGNATURECERTCHAINURL']))
			{
				throw new \Exception('HTTP_SIGNATURECERTCHAINURL key not present');
			}
			
			// validate signature data
			//			
			if (empty($_SERVER['HTTP_SIGNATURECERTCHAINURL']))
			{
				throw new \Exception('HTTP_SIGNATURECERTCHAINURL data not present');
			}
			
			// Determine if we need to download a new Signature Certificate Chain from Amazon
			//			
			$_md5pem = md5($_SERVER['HTTP_SIGNATURECERTCHAINURL']);
			$_md5pem = $_md5pem . '.pem';

			// If we haven't received a certificate with this URL before, store it as a cached copy
			//
			if (!file_exists($this->get('amazonLogFolder').$_md5pem)) {
				file_put_contents($this->get('amazonLogFolder').$_md5pem, file_get_contents($_SERVER['HTTP_SIGNATURECERTCHAINURL']));
			}

			// Validate proper format of Amazon provided certificate chain url
			//
			$this->validateKeychainUri($_SERVER['HTTP_SIGNATURECERTCHAINURL']);

			// Validate certificate chain and signature
			//
			$_pem = file_get_contents($this->get('amazonLogFolder').$_md5pem);
			$_ssl_check = openssl_verify($this->get('alexajsonrequest'), base64_decode($_SERVER['HTTP_SIGNATURE']), $_pem, 'sha1');
			if ($_ssl_check != 1)
			{
				throw new \Exception(openssl_error_string());
			}

			// Parse certificate
			//
			$_parsedCertificate = openssl_x509_parse($_pem);
			if (!$_parsedCertificate)
			{
				throw new \Exception('x509 certificate parse failed');
			}

			// Check that the domain echo-api.amazon.com is present in the Subject Alternative Names (SANs) section of the signing certificate
			//
			if(strpos($_parsedCertificate['extensions']['subjectAltName'], $this->__config->get('amazonEchoServiceDomain')) === false)
			{
				throw new \Exception('subjectAltName Check Failed');
			}

			// Check that the signing certificate has not expired (examine both the Not Before and Not After dates)
			//
			$_validFrom = $_parsedCertificate['validFrom_time_t'];
			$_validTo   = $_parsedCertificate['validTo_time_t'];
			
			$_now=new \DateTime();
			
			$_time = $_now->getTimestamp();
			if (!($_validFrom <= $_time && $_time <= $_validTo)) {
				throw new \Exception('certificate expiration check failed');
			}

			// Check the timestamp of the request and ensure it was within the past minute
			//
			$_alexaRequestTimestamp   = @$_alexaRequest['request']['timestamp'];
			if ($_now->getTimestamp() - strtotime($_alexaRequestTimestamp) > 60)
				throw new \Exception('timestamp validation failure.. Current time: ' . $_now->format('Y-m-d\TH:i:s\Z') . ' vs. Timestamp: ' . $_alexaRequestTimestamp);
			
			
			$this->set('validalexarequest','true');

		} // request does not originate from public ip space
		
	}

	//
	// render alexa response
	//	
	private function renderAlexaResponse()
	{
		// ouput methods
		// 1. JSON RESPONSE
		
		// get alexa data
		//
		$_alexaRequest=$this->get('alexarequest');
		
		// get intent
		//
		if (isset($_alexaRequest['request']['intent']))
		{
			// render response from intent class
			//
			$_alexaIntent=$_alexaRequest['request']['intent']['name'];
			
			$_alexaRenderClass = __NAMESPACE__ . '\\Alexa\\Intent\\'.ucfirst($_alexaIntent);
			
			if (!class_exists($_alexaRenderClass)) { throw new \Exception('Requested intent class '. $_alexaRenderClass. ' is not valid.'); }
			
			// set app name to intent
			//
			$this->set('applicationName',ucfirst($_alexaIntent));
			
			
			// call render class
			//
			$_obj = new $_alexaRenderClass(array(
			  "alexarequest"		 	=> 		$_alexaRequest,
			  "amazonLogFile"	 		=> 		$this->get('amazonLogFile'),
			  "version"	 				=> 		$this->get('version'),
			  "versionnumber"			=> 		$this->get('versionNumber'),			  
			  "applicationname"		 	=> 		$this->get('applicationName'),
			  "errorMessage" 		 	=>		$this->__['errorMessage']
			));	
			
			$_success=$_obj->get('success');
			$_output=$_obj->get('output');

			unset($_obj);
			
			$_response='ERROR';
			
			if ($_success)
			{
				$_response=$_output['intent'][$_alexaIntent]['response'];
				$_card=$_output['intent'][$_alexaIntent]['card'];
				$_endSession=$_output['intent'][$_alexaIntent]['endsession'];
				$_sessionAttributes=$_output['intent'][$_alexaIntent]['sessionattributes'];
				$_outputSSML=$_output['intent'][$_alexaIntent]['outputssml'];
				
				$this->respond($_response,$_card,$_endSession,$_sessionAttributes,$_outputSSML);
				
			} else {
				
				$this->respond($_response);
			}
			
			// log
			//
			$_logToFile=new \PAJ\Library\Log\LogToFile(
				array(
					'logfile' => $this->get('amazonLogFile'),
					'data' => 'INTENT RESPONSE ARRAY: '. print_r($_output,true). "\n". 'JSON RESPONSE: '.$this->get('jsonresponse')
				));
					unset($_logToFile);	
			
			
			// fin
			exit;
		}
	}
	
	//
	// Validate keychainUri data from Amazon
	//
	private function validateKeychainUri($keychainUri){
		
	
		$uriParts = parse_url($keychainUri);

		if (strcasecmp($uriParts['host'], 's3.amazonaws.com') != 0)
		{
			throw new \Exception('The host for the Certificate provided in the header is invalid');
		}
		
		if (strpos($uriParts['path'], '/echo.api/') !== 0)
		{
			throw new \Exception('The URL path for the Certificate provided in the header is invalid');
		}
		
		if (strcasecmp($uriParts['scheme'], 'https') != 0)
		{
			throw new \Exception('The URL is using an unsupported scheme. Should be https');
		}

		if (array_key_exists('port', $uriParts) && $uriParts['port'] != '443')
		{
			throw new \Exception('The URL is using an unsupported https port');
		}

	}

	//
	// return json response to amazon
	// 
	private function respond($_alexaResponse, $_card=false, $_endSession=true, $_sessionAttributes=false, $_outputSSML=false) {

		$_cardJSON='';
		
		if (is_array($_card))
		{
			if (!$_card['text']) {$_card['text']=$_alexaResponse;}
			if (!$_card['title']) {$_card['title']=$this->get('applicationName');}
			if (!$_card['image']) {$_card['image']='default';}
			
			// custom card
			//
			$_cardJSON='
					"card" : 
					{
					  "type": "Standard",
					  "title": "'. $_card['title']. '",
					  "text": "'. $_card['text']. '",
						"image": 
						{
							"smallImageUrl": "'. $this->get('applicationURL'). 'alexaCardImage.php?size=small&image='. $_card['image']. '",
							"largeImageUrl": "'. $this->get('applicationURL'). 'alexaCardImage.php?size=large&image='. $_card['image']. '"
						}
					},
				';				
			
		} else {
			$_card=false;
		}
		
		if ($_card===false)
		{
			// default card
			//
			$_cardJSON='
					"card" : 
					{
					  "type": "Standard",
					  "title": "'. $this->get('applicationName'). '",
					  "text": "'. $_alexaResponse. '",
						"image": 
						{
							"smallImageUrl": "'. $this->get('applicationURL'). 'alexaCardImage.php?size=small&image=default",
							"largeImageUrl": "'. $this->get('applicationURL'). 'alexaCardImage.php?size=large&image=default"
						}
					},
				';
		}
		
		// End session
		//
		$_shouldEndSession = $_endSession ? 'true' : 'false';
		
		$_sessionAttributeJSON='';
		
		if ($_sessionAttributes)
		{
			$_sessionAttributeJSON='
				"sessionAttributes" : '. json_encode ($_sessionAttributes).
				',				
			';
		}
		
		// JSON
		//
		$_json = '{
					"version" : "1.0",
					'. $_sessionAttributeJSON. '
					"response" :
						{
							"outputSpeech" : 
							{
								"type" : "'. ($_outputSSML ? 'SSML' : 'PlainText').'",
								"'. ($_outputSSML ? 'ssml' : 'text').'" : "'.$_alexaResponse.'"
							},
						'. $_cardJSON. '
						"shouldEndSession" : '.$_shouldEndSession.'
						}
					}
		';
		
		// minify
		//
		$_json=\PAJ\Library\Minify\JSON::minify($_json);
		$this->set('jsonresponse',$_json);
		
		// header
		//
		header('Content-Type: application/json;charset=UTF-8');			
		header('Content-Length: ' . strlen($_json));
		echo $_json;

	}
	
	
	
	
	public function getExceptionTraceAsString($exception) {
		$rtn = "";
		$count = 0;
		foreach ($exception->getTrace() as $frame) {
			$args = "";
			if (isset($frame['args'])) {
				$args = array();
				foreach ($frame['args'] as $arg) {
					if (is_string($arg)) {
						$args[] = "'" . $arg . "'";
					} elseif (is_array($arg)) {
						$args[] = "Array";
					} elseif (is_null($arg)) {
						$args[] = 'NULL';
					} elseif (is_bool($arg)) {
						$args[] = ($arg) ? "true" : "false";
					} elseif (is_object($arg)) {
						$args[] = get_class($arg);
					} elseif (is_resource($arg)) {
						$args[] = get_resource_type($arg);
					} else {
						$args[] = $arg;
					}   
				}   
				$args = join(", ", $args);
			}
			$rtn .= sprintf( "#%s %s(%s): %s(%s)\n",
									 $count,
									 $frame['file'],
									 $frame['line'],
									 $frame['function'],
									 $args );
			$count++;
		}
		
		return $rtn;
	}			

	protected function get_request_headers() {

		// Based on: http://www.iana.org/assignments/message-headers/message-headers.xml#perm-headers
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
		
	public function set($key,$value)
	{
		$this->__[$key] = $value;
	}
		
	public function get($variable)
	{
		return $this->__[$variable];
	}
		
}