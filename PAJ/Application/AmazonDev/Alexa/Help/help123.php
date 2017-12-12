<?php
/**
 *
 *  Copyright (C) 2017 paj@gaiterjones.com
 *
 *
 */

namespace PAJ\Application\AmazonDev\Alexa\Help;

/**
 * ALEXA HELP CLASS
 * RENAME this class to the last section of the skill id
 */
class help123 {


	public static function help($_alexaRequest=false)
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
