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
 * RENAME this class to the last section of the skill id
 */
class launch123 {


	public static function launch($_alexaRequest=false)
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
