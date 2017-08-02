<?php
/**
 *  
 *  Copyright (C) 2017 paj@gaiterjones.com
 *
 *
 */

namespace PAJ\Application\AmazonDev\Alexa\Launch;

/**
 * ALEXA LAUNCH CLASS
 * 
 */
class example {


	public static function launch()
	{
		$_response='This ia a hello world alexa php example skill!';
		$_card=array(
			'title' => 'Hello World',
			'text' => $_response,
			'image' => false
		);
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