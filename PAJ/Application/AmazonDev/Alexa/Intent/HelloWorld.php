<?php
/**
 *
 *  Copyright (C) 2017 paj@gaiterjones.com
 *
 *
 */

namespace PAJ\Application\AmazonDev\Alexa\Intent;
use PAJ\Application\AmazonDev\Alexa\GetIntent;
/**
 * ALEXA INTENT CLASS
 *
 * @extends GetIntent
 */
class HelloWorld extends GetIntent {

	public function __construct
	(
		$_environment
	)
	{

		$this->loadEnvironment($_environment);

		// intent request
		$this->intent();


	}

	protected function intent()
	{
		// init vars
		//
		$this->set('success',false);
		$this->set('errormessage','intent -> Default error message.');

		// alexa request data
		//
		$_alexaRequest=$this->get('alexarequest');

		$_locale=$_alexaRequest['request']['locale'];

		// parse request and perform actions
		//
		if (isset($_alexaRequest['request']['intent']['name'])) {

			$_now = new \DateTime(null, new \DateTimeZone('Europe/London'));

			$_response=$this->intentAction($_alexaRequest);

			$_endSession=true;
			if (isset($_response['intent']['endsession'])) {$_endSession=$_response['intent']['endsession'];}

			$_sessionAttributes=false;
			if (isset($_response['intent']['sessionattributes'])) {$_sessionAttributes=$_response['intent']['sessionattributes'];}

			$_outputSSML=false;
			if (isset($_response['intent']['outputssml'])) {$_outputSSML=$_response['intent']['outputssml'];}

			$this->set('success',true);

			$this->set('output',array(
					'intent' => array(
						$_alexaRequest['request']['intent']['name'] => array(
							'response' => $_response['intent']['response'],
							'card' => $_response['intent']['card'],
							'status' => $_response['intent']['status'],
							'target' => $_response['intent']['target'],
							'endsession' => $_endSession,
							'sessionattributes' => $_sessionAttributes,
							'outputssml' => $_outputSSML,
							'locale' => $_locale,
							'timestamp' => $_now->format('Y-m-d\TH:i:sP')
						)
					)
			));

		} else {

			$this->set('errormessage','Invalid alexa request data.');
		}


	}

	protected function intentAction($_alexaRequest)
	{
		// intent action for HELLOWORLD COMMAND
		//

		// parse slots
		//
		$_slots=false;
		$_target=false;

		// init commmand vars
		//
		$_commandFound=false;
		$_commandTargetFound=false;
		$_now = new \DateTime(null, new \DateTimeZone($this->__config->get('timezone')));
		$_debug=false;

		// init object names
		$_objectNames=array();

		// default response
		//
		$_response='Sorry I could not complete your request.';

		if (isset($_alexaRequest['request']['intent']['slots']))
		{
			$_slots=$_alexaRequest['request']['intent']['slots'];
		}

		// clever quotes data
		//
		$_cleverQuotesData=HelloWorldData::cleverQuotes();
		$_cleverQuotesArrayKeys = range(0, (count($_cleverQuotesData)-1));
		shuffle($_cleverQuotesArrayKeys);

		if (is_array($_slots))
		{

			foreach ($_slots as $_slot)
			{

				//
				// - PARSE PROMPT SLOTS
				//
				if ($_slot['name']=='prompt' && isset($_slot['value']))
				{

					// get session data
					//
					$_sessionData=$_alexaRequest['session']['attributes'];

					// check session prompt
					//
					if (!isset($_sessionData['prompt']))
					{
							// invalid prompt
							//
							return array(
								'intent' => array(
									'response' => 'Sorry, I did not expect that answer!',
									'card' => false,
									'target' => false,
									'status' => false,
									'endsession' => true
								)
							);
					}

					$_promptCount=(int)$_sessionData['count'];

					$_slotValue=$_slot['value'];

					$_spokenWords = explode(' ', $_slotValue);

					$this->set('spokenwords',$_spokenWords);


					// PARSE SPOKEN WORDS IF REQUIRED
					//
					// yes / no response to prompt :
					//
					if (in_array('yes', $_spokenWords))
					{


						// prompts for object->clever quotes
						//
						if ($_sessionData['object']==='clever quotes')
						{

							// found target
							//
							// here you can parse the spoken words further to define more custom actions for your target object
							//

							if (!isset($_cleverQuotesArrayKeys[$_promptCount]))
							{
								// no more prompt data
								//
								return array(
									'intent' => array(
										'response' => 'Thats all for now, goodbye!',
										'card' => false,
										'target' => false,
										'status' => false,
										'endsession' => true
									)
								);
							}

							$_data=$_sessionData['data'];

							$_target=$_cleverQuotesData[$_data[$_promptCount]];
							$_promptCount++;
							$_targetFound=true;

							if ($_targetFound)
							{

								// command success
								//
								return array(
									'intent' => array(
										'response' => $_target. ' Would you like some more clever quotes?',
										'card' => array(
											'title' => 'Clever Quotes',
											'text' => $_now->format('H:i:s'). ' '. $_target,
											'image' => false
										),
										'target' => $_target,
										'status' => false,
										'sessionattributes' => array('object' => 'clever quotes', 'target' => $_target,'prompt' => true, 'count' => $_promptCount, 'data' => $_data),
										'endsession' => false
									)
								);

							} // target not found
						}

					} // parse words


					// prompt response NO
					//
					if (in_array('no', $_spokenWords))
					{
						$_target='no';
						$_targetFound=true;

						if ($_targetFound)
						{

							// command success
							//
							return array(
								'intent' => array(
									'response' => 'Ok, goodbye!',
									'card' => false,
									'target' => $_target,
									'status' => false,
									'endsession' => true
								)
							);


						} // target not found

					} // parse words

					// prompt response not understood
					//
					return array(
						'intent' => array(
							'response' => 'Sorry I didn\'t understand that. Please repeat it.',
							'card' => false,
							'target' => $_target,
							'status' => false,
							'sessionattributes' => array('object' => 'clever quotes', 'target' => $_target,'prompt' => true, 'count' => $_promptCount, 'data' => $_sessionData['data']),
							'endsession' => false
						)
					);

				} // prompt slots




				//
				// - PARSE COMMAND SLOTS
				//
				if ($_slot['name']=='command' && isset($_slot['value']))
				{
					$_slotValue=$_slot['value'];

					$_spokenWords = explode(' ', $_slotValue);

					$this->set('spokenwords',$_spokenWords);

					// PARSE SPOKEN WORDS IF REQUIRED
					//
					// clever quotes
					//
					if (in_array('clever', $_spokenWords) && in_array('quotes', $_spokenWords))
					{
						// found target
						//
						// here you can parse the spoken words further to define more custom actions for your target object
						//
						$_target='clever quotes';

						// response
						//
						return array(
							'intent' => array(
								'response' => 'Ok, here comes your first clever quote : '. $_cleverQuotesData[$_cleverQuotesArrayKeys[0]]. ' Would you like some more clever quotes?',
								'card' => array(
									'title' => $_target,
									'text' => $_now->format('H:i:s'). ' '. $_cleverQuotesData[$_cleverQuotesArrayKeys[0]],
									'image' => false
								),
								'target' => $_target,
								'status' => false,
								'sessionattributes' => array('object' => 'clever quotes','target' => $_target,'prompt' => true, 'count' => 1,'data' => $_cleverQuotesArrayKeys),
								'endsession' => false
							)
						);

					} // parse words


					// PARSE SPOKEN WORDS IF REQUIRED
					//
					// hello world
					//
					if (in_array('hello', $_spokenWords) && in_array('world', $_spokenWords))
					{
						// found target
						//
						// here you can parse the spoken words further to define more custom actions for your target object
						//
						$_target='hello world';
						$_targetFound=true;

						if ($_targetFound)
						{

							$_commandStatus='fail';

							try
							{

								if (!$_debug) // do not execute if debugging
								{
									// execute custom external commands for this target if required
									//
									//
									// e.g. switch lights on/off
									//
									//

									$_commandStatus='success';

								} else {

									$_commandStatus='success';
								}

							// catch exception
							//
							} catch(Exception $e) {

								// do nothing with $e


							}


							if ($_commandStatus==='success')
							{
								// command success
								//
								return array(
									'intent' => array(
										'response' => 'OK, I will now say '. $_target,
										'card' => array(
											'title' => $_target,
											'text' => $_now->format('H:i:s'). ' '. $_target,
											'image' => false
										),
										'target' => $_target,
										'status' => false,
										'endsession' => true
									)
								);

							} else {

								// command failed (if using some kind of external command i.e. lights on/off)
								//
								return array(
									'intent' => array(
										'response' => 'Sorry I could not complete your request for '. $_target,
										'card' => false,
										'target' => $_target,
										'status' => false,
										'endsession' => true
									)
								);
							}

						} // target not found

					} // parse words

				} // command slots

			} // loop
		}


		// failed
		return array(
			'intent' => array(
				'response' => $_response,
				'card' => false,
				'target' => false,
				'status' => false,
				'endsession' => true
			)
		);
	}

}
?>
