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
use PAJ\Application\AmazonDev\Data;
use PAJ\Application\AmazonDev\Alexa\IntentException;
use PAJ\Library\Minify\JSON;

class IntentFactory implements Data
{

    public $__environment;
    protected $__config;
    protected $__log;

    public function __construct
	(

	)
    {
    }

/**
 * getData
 * @return void
 */
    public function getData($_environment)
    {
        $this->loadEnvironment($_environment);

        try
		{
            $this->renderAlexaResponse();
        }
        /**
         * IntentException Errors
         */
        catch (IntentException $e)
        {
            $this->exceptionError($e,'Intent');
        }
        /**
         * Exception Errors
         */
        catch (\Exception $e)
        {
            $this->exceptionError($e);
        }

    }

/**
 * Render Exceptions
 * @param  $e     Exception
 * @param  string $_type Type of exception
 * @return void
 */
    private function exceptionError($e,$_type='Exception')
    {
        $this->set('errorMessage', $_type. ' ERROR : '. $e->getMessage(). "\n". $this->getExceptionTraceAsString($e));

        if ($this->get('debug'))
        {
            // log errors
            //
            $this->__log->writeLogFile($this->get('errorMessage'),$this->get('amazonLogFile'));

        }

        // semi friendly error response to alexa when intent exception occurs
        //
        $this->respond('Sorry, an Error has occurred. The error has been logged and we are working on it!');
        exit;
    }
/**
 * Render the response from the intent
 * @return void
 */
    public function renderAlexaResponse()
    {
        // ouput methods
		// 1. JSON RESPONSE

        // get alexa data
		//
		$_alexaRequest=$this->get('alexarequest');

		// buld classes for rendering
		//
		// Intent
		$_alexaIntent=false;
        if (isset($_alexaRequest['request']['intent']['name'])){$_alexaIntent=$_alexaRequest['request']['intent']['name'];}
        $_alexaIntentClass = __NAMESPACE__ . '\\Intent\\'.ucfirst($_alexaIntent);

        // launch / Help
        //
        $_alexaLaunchClass=false;
		$_alexaHelpClass=false;
		$_applicationID=explode('-',$_alexaRequest['session']['application']['applicationId']);
		if (isset($_applicationID[4])){$_alexaLaunchClass = __NAMESPACE__ . '\\Launch\\launch'.$_applicationID[4];}
		if (isset($_applicationID[4])){$_alexaHelpClass = __NAMESPACE__ . '\\Help\\help'.$_applicationID[4];}

		// render response for type LaunchRequest
		//
		if ($_alexaRequest['request']['type']=='LaunchRequest')
		{

			if (class_exists($_alexaLaunchClass))
			{
				$_launchResponse=$_alexaLaunchClass::launch($_alexaRequest);
				$_response=$_launchResponse['response'];
				$_card=$_launchResponse['card'];
				$_endSession=$_launchResponse['endsession'];
				$_sessionAttributes=$_launchResponse['sessionattributes'];
				$_outputSSML=$_launchResponse['outputssml'];

			} else {


				$_response='Hello, how can I be of assistance?';
				$_card=false;
				$_endSession=false;
				$_sessionAttributes=false;
				$_outputSSML=false;


			}

			$this->respond($_response,$_card,$_endSession,$_sessionAttributes,$_outputSSML);
			exit;
		}


		// render response for type IntentRequest
		//
		if ($_alexaRequest['request']['type']=='IntentRequest' && isset($_alexaRequest['request']['intent']))
		{
            // render default responses
			//
			// STOP // CANCEL
			if ($_alexaIntent=='AMAZON.StopIntent' || $_alexaIntent=='AMAZON.CancelIntent')
			{
				$_response='Goodbye.';
				$_card=false;
				$_endSession=true;
				$_sessionAttributes=false;
				$_outputSSML=false;

				$this->respond($_response,$_card,$_endSession,$_sessionAttributes,$_outputSSML);

			}

			// render default responses
			//
			// HELP
			if ($_alexaIntent==='AMAZON.HelpIntent')
			{

				if (class_exists($_alexaHelpClass))
				{
					// Render Custom HELP response
				    //
					$_helpResponse=$_alexaHelpClass::help();
					$_response=$_helpResponse['response'];
					$_card=$_helpResponse['card'];
					$_endSession=$_helpResponse['endsession'];
					$_sessionAttributes=$_helpResponse['sessionattributes'];
					$_outputSSML=$_helpResponse['outputssml'];

				} else {

					$_response='Sorry, there is no help configured for this skill.';
					$_card=false;
					$_endSession=true;
					$_sessionAttributes=false;
					$_outputSSML=false;

				}

				$this->respond($_response,$_card,$_endSession,$_sessionAttributes,$_outputSSML);

			}

			// render custom response from intent class
			//
			//
			if (!class_exists($_alexaIntentClass)) { throw new IntentException('Requested intent class '. $_alexaIntentClass. ' is not valid.'); }

			// set app name to intent
			//
			$this->set('applicationName',ucfirst($_alexaIntent));

			// instantiate intent class
			//
			$_obj = new $_alexaIntentClass
            (
                $this->__environment
            );

			$_success=$_obj->get('success');
                $_output=$_obj->get('output');
                    unset($_obj);

			$_response='ERROR';

			if ($_success)
			{
                // build response
                //
                $_response=$_output['intent'][$_alexaIntent]['response'];
				$_card=$_output['intent'][$_alexaIntent]['card'];
				$_endSession=$_output['intent'][$_alexaIntent]['endsession'];
				$_sessionAttributes=$_output['intent'][$_alexaIntent]['sessionattributes'];
				$_outputSSML=$_output['intent'][$_alexaIntent]['outputssml'];

				$this->respond($_response,$_card,$_endSession,$_sessionAttributes,$_outputSSML);

			} else {

				$this->respond($_response);
			}

            if ($this->get('debug'))
			{
				// log intent response data
				//
				$this->__log->writeLogFile(
                    'INTENT RESPONSE ARRAY: '. print_r($_output,true). "\n". 'JSON RESPONSE: '.$this->get('jsonresponse'). "\n". 'errorMessage: '. $this->get('errorMessage')
                    ,$this->get('amazonLogFile')
                );
			}

            // fin!
            exit;
		}

    }


/**
 * Return json response to amazon
 * @param  string  $_alexaResponse     Alexa response text
 * @param  boolean $_card              Enable/Disable card
 * @param  boolean $_endSession        End Session
 * @param  boolean $_sessionAttributes Include Session Attributes data
 * @param  boolean $_outputSSML        Use SSML true/false
 * @return void
 */
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

        if ($_card==='default')
        {
            // default card or NO card
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

        $this->set('jsonresponse',$_json);

        // minify
        //
        $_json=JSON::minify($_json);


        // header
        //
        header('Content-Type: application/json;charset=UTF-8');
        header('Content-Length: ' . strlen($_json));

        // data
        //
        echo $_json;

    }

    /**
	 * Format an interval to show all existing components.
	 * If the interval doesn't have a time component (years, months, etc)
	 * That component won't be displayed.
	 *
	 * @param DateInterval $interval The interval
	 *
	 * @return string Formatted interval string.
	 */
	protected function format_interval(\DateInterval $interval) {
		$result = "";
		if ($interval->y) { $result .= $interval->format("%y years "); }
		if ($interval->m) { $result .= $interval->format("%m months "); }
		if ($interval->d) { $result .= $interval->format("%d days "); }
		if ($interval->h) { $result .= $interval->format("%h hours "); }
		if ($interval->i) { $result .= $interval->format("%i minutes "); }
		if ($interval->s) { $result .= $interval->format("%s seconds "); }

		return $result;
	}

	/**
	 * humanTiming function.
	 * @what returns time lapsed in easy human reading form
	 * @access protected
	 * @return string
	 */
	protected function humanTiming ($time1,$time2)
	{

	    $time = $time1 - $time2; // to get the time since that moment

	    $tokens = array (
	        31536000 => 'year',
	        2592000 => 'month',
	        604800 => 'week',
	        86400 => 'day',
	        3600 => 'hour',
	        60 => 'minute',
	        1 => 'second'
	    );

	    foreach ($tokens as $unit => $text) {
	        if ($time < $unit) continue;
	        $numberOfUnits = floor($time / $unit);
	        return $numberOfUnits.' '. $text.(($numberOfUnits>1)?'s':''). ' ago';
	    }
    }

    protected function getExceptionTraceAsString($exception) {
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
