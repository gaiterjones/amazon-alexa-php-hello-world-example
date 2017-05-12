<?php
/**
 *  
 *  Copyright (C) 2017 paj@gaiterjones.com
 *
 *
 */

namespace PAJ\Application\Amazon\Alexa\Help;

/**
 * ALEXA HELP CLASS
 * 
 */
class example {


	public static function help()
	{
		$_response="Hello, I am happy to Help! To start this skill please say 'Alexa, open Hello World'";
		$_card=false;
		$_endSession=false;
		$_sessionAttributes=false;
		$_outputSSML=false;

		return array(
			'response' => $_response,
			'card' => $_card,
			'endsession' => $_endSession,
			'sessionattributes' => $_sessionAttributes,
			'outputssml' => $_outputSSML
		);
		
	}
	
}
?>