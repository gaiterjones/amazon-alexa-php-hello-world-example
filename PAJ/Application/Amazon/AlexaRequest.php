<?php
/**
 *  
 *  Copyright (C) 2016
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
		
		catch (\Exception $e)
	    
		{
	    	$this->set('errorMessage', 'ERROR : '. $e->getMessage(). "\n". $this->getExceptionTraceAsString($e));
			

			$_logToFile=new \PAJ\Library\Log\LogToFile(
				array(
					'logfile' => $this->get('amazonLogFile'),
					'data' => $this->get('errorMessage')
				));
					unset($_logToFile);	
					
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
				$this->respond('Exception error, '. $e->getMessage());				
			}
		
			exit;
	    }
	}

	private function loadConfig()
	{
		$this->__config= new config();
		
		$_version='BETA v0.0.1';
		$_versionNumber=explode('-',$_version);
		$_versionNumber=$_versionNumber[0];
		
		$this->set('version',$_version);
		$this->set('versionNumber',$_versionNumber);
		$this->set('amazonLogFile',$this->__config->get('amazonCacheFolder'). 'home-1-0');
		$this->set('amazonLogFolder',$this->__config->get('amazonCacheFolder'));
		$this->set('applicationURL',$this->__config->get('applicationURL'));
		$this->set('applicationName',$this->__config->get('applicationName'));
	}
	
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
						
					//
					// debug alexa request to log file
					//
					$_request   = date(DATE_RFC2822) . "\n";
			
					//
					// Log Apache headers
					//
					$_headers = apache_request_headers();
			
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
	
		private function validateAlexaRequest()
		{
			if (php_sapi_name() === 'cli') {exit;}
			
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
				//
				if ($_userId != $this->__config->get('amazonUserId')) throw new \Exception('Invalid User id: ' . $userId);

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
				$_ssl_check = openssl_verify($this->get('alexajsonrequest'), base64_decode($_SERVER['HTTP_SIGNATURE']), $_pem);
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
					throw new \Exception('timestamp validation failure.. Current time: ' . $_now->getTimestamp() . ' vs. Timestamp: ' . $_alexaRequestTimestamp);

			} // request does not originate from public ip space
			
		}
	
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
				$_alexaRenderClass=__NAMESPACE__. '\\Alexa\\Intent\\'.ucfirst($_alexaIntent);
				
				if (!class_exists($_alexaRenderClass)) { throw new \Exception('Requested intent class '. $_alexaRenderClass. ' is not valid.'); }
				
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
					$this->respond($_response);
					
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
		private function respond($_alexaResponse, $_endSession = true, $_card=false) {

			// End session
			//
			$_shouldEndSession = $_endSession ? 'true' : 'false';
			
			// JSON
			$_json = '{
						"version" : "1.0",
						"response" : {
							"outputSpeech" : {"type" : "PlainText","text" : "'.$_alexaResponse.'"},
							"card": {
							  "type": "Standard",
							  "title": "'. $this->get('applicationName'). '",
							  "text": "'. $_alexaResponse. '",
							  "image": {
								"smallImageUrl": "'. $this->get('applicationURL'). 'images/card-small.png",
								"largeImageUrl": "'. $this->get('applicationURL'). 'images/card-large.png"
							  }
							},		
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
		
		public function set($key,$value)
		{
			$this->__[$key] = $value;
		}
			
	  	public function get($variable)
		{
			return $this->__[$variable];
		}
		
	}
