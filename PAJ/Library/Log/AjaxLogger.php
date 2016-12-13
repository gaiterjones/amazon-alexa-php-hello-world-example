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
 *
 *
 */

namespace PAJ\Library\Log;

//
// simple logger to logging system for use with ajax calls to log events from a webpage
//
class AjaxLogger {


	public function __construct($_variables) {
		
		$this->loadClassVariables($_variables);
		$this->ajaxLogger();
	}
	
	/**
	 * writeLogFile function.
	 * @what write log data to cache/file
	 * @access private
	 * @return nix
	 */	
	private function ajaxLogger(){
	
		// init
		$this->set('success',false);
		$this->set('errormessage','Not defined');
		$_result=false;
		
		// vars - 
		// - logname for cache key
		// - logdata
		$_logData=$this->get('logdata');
		$_logName=$this->get('logname');
		$_referrer=$this->get('referrer');
		$_useBrowseCap=true; // use browsecap? true / false
		
		try
		{
			// log to logging module (cache)
			//
			$_obj=new LogToCache($_logData,$_logName,$_referrer,$_useBrowseCap);
				$_result=$_obj->get('success');
					unset($_obj);
			
			if ($_result)
			{
				$this->set('success',true);
				$this->set('output',array('ajaxlogger' => true,'logkey' => $_logName,'referrer' => $_referrer));
			}
		
		}
		catch (\Exception $e)
	    {
		    $this->set('errormessage','Error trying to log data.');
	    }
	}

	public function set($key,$value)
	{
		$this->__[$key] = $value;
	}

	public function get($variable)
	{
		return $this->__[$variable];
	}
	
	protected function loadClassVariables($_variables)
	{
		foreach ($_variables as $_variableName=>$_variableData)
		{
			// check for optional data
			if (substr($_variableName, -8) === 'optional') { continue; }
			
			$_variableData=trim($_variableData);
			
			if (empty($_variableData) && $_variableData !='0') {
				//$_variableData=false;
				throw new \Exception('Class variable '.$_variableName. '('. $_variableData. ') cannot be empty.');
			}
			
			$this->set($_variableName,$_variableData);
						
		}
	}	
}