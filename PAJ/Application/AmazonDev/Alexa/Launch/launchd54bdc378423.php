<?php
/**
 *
 *  Copyright (C) 2017 paj@gaiterjones.com
 *
 *
 */

namespace PAJ\Application\AmazonDev\Alexa\Launch;

/**
 * ALEXA LAUNCH CLASS FOR CLEVER STUFF SKILL
 *
 */
class launchd54bdc378423 {


	public static function launch($_alexaRequest=false)
	{
		$_response="Say 'Clever Quotes', to hear some clever quotes!";
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
