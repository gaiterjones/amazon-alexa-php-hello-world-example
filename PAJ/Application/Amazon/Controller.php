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
 */

namespace PAJ\Application\Amazon;

class Controller {

	protected $__;
	protected $__config;

	public function __construct($_variables=false) {
		
		if ($_variables)
		{
			$this->loadClassVariables($_variables);
		}
		
		$this->loadConfig();
		
	}
	
	protected function loadConfig()
	{
		$this->__config= new config();
	}	

	protected function loadClassVariables($_variables)
	{
		foreach ($_variables as $_variableName=>$_variableData)
		{
			// check for array
			if (is_array($_variableData))
			{
				$this->set($_variableName,$_variableData);
				continue;
			}
			
			// check for optional data
			if (substr($_variableName, -8) === 'optional') { continue; }
			
			$_variableData=trim($_variableData);
			
			if ($_variableData == 0) // allow boolean/zero
			{
				$this->set($_variableName,$_variableData);
				continue;
			}
			
			if (empty($_variableData)) {
				throw new \Exception('Class variable '.$_variableName. ' cannot be empty.');
			}
			
			$this->set($_variableName,$_variableData);
						
		}
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
	
	/**
	 * get function.
	 * @what class variable retriever
	 * @access public
	 * @return VARIABLE FROM ARRAY
	 */	
  	public function get($variable)
	{
		if (!isset($this->__[$variable]) && substr($variable, -8) != 'optional') { throw new \Exception(get_class($this). ' - The requested class variable "'. $variable. '" does not exist.');}
		
		return $this->__[$variable];
	}


	/**
	 * set function.
	 * @what class variable setter
	 * @access public
	 * @return VARIABLE TO ARRAY
	 */		
	public function set($key,$value)
	{
		$this->__[$key] = $value;
	}		
	
	
}