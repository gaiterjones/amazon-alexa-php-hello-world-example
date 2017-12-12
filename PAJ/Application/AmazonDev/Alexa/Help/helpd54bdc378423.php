<?php
/**
 *
 *  Copyright (C) 2017 paj@gaiterjones.com
 *
 *
 */

namespace PAJ\Application\AmazonDev\Alexa\Help;

/**
 * ALEXA HELP CLASS FOR CLEVER STUFF SKILL
 *
 */
class helpd54bdc378423 {


	public static function help()
	{
		// CLEVER STUFF

		$_response="To start this skill please say 'open Clever Stuff', or 'ask Clever Stuff for clever quotes.'";
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
