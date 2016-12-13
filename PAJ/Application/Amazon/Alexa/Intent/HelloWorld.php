<?php
/**
 *  
 *  Copyright (C) 2016 paj@gaiterjones.com
 *
 *
 */

namespace PAJ\Application\Amazon\Alexa\Intent;

/**
 * ALEXA INTENT CLASS
 * 
 * @extends Controller
 */
class HelloWorld extends \PAJ\Application\Amazon\Controller {


	public function __construct($_variables) {
	
		// load parent
		parent::__construct($_variables);
		
		// load intent 
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
		
		// parse request and perfrom actions
		//
		if (isset($_alexaRequest['request']['intent']['name'])) {
			
			$_now = new \DateTime(null, new \DateTimeZone('Europe/London'));

			$_response=$this->intentAction($_alexaRequest);
			
			
			$this->set('success',true);
			
			$this->set('output',array(
					'intent' => array(
						$_alexaRequest['request']['intent']['name'] => array(
							'response' => $_response['intent']['response'],
							'status' => $_response['intent']['status'],
							'target' => $_response['intent']['target'],
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
		
		// init commmand vars
		//
		$_commandFound=false;
		$_commandTargetFound=false;
		
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

		if (is_array($_slots))
		{
			
			foreach ($_slots as $_slot)
			{
				
				//
				// - PARSE SLOTS
				//
				if ($_slot['name']=='command')
				{
					$_slotValue=$_slot['value'];
					
					$_spokenWords = explode(' ', $_slotValue);
					
					$this->set('spokenwords',$_spokenWords);
					
					
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
									// execute custom commands for this target
									//
									//
									// e.g. switch on light
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
										'target' => $_target,
										'status' => false
									)
								);
								
							} else {
								
								// command failed
								//
								return array(
									'intent' => array(
										'response' => 'Sorry I could not complete your request for '. $_target,
										'target' => $_target,
										'status' => false
									)
								);
							}
							
						} // target not found
							
					} // parse words
					
				} // slots
				
			} // loop
		} 	
		
		
		// failed
		return array(
			'intent' => array(
				'response' => $_response,
				'target' => false,
				'status' => false				
			)
		);
	}
	

}
?>