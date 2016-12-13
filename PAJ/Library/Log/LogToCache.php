<?php
/**
 *  
 *  Copyright (C) 2014
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

class LogToCache extends \PAJ\Library\Log\LogController {


	public function __construct($_logData,$_logName='',$_referrer=false,$_useBrowseCap=true) {
		
		parent::__construct();

		$this->logToCache($_logData,$_logName,$_referrer,$_useBrowseCap);
	}
	
	/**
	 * writeLogFile function.
	 * @what write log data to cache/file
	 * @access private
	 * @return nix
	 */	
	private function logToCache($_logData,$_logName,$_referrer,$_useBrowseCap){
	
		$this->set('success',false);
		$this->set('errormessage','Not defined');
		

		// init
		$_useCache=$this->__config->get('useMemcache');
		$_cacheConnected=$this->__cache->get('memcacheconnected');
		$_timeStamp=strtotime("now");
		
		date_default_timezone_set($this->get('timezone'));

		// create log data array
		//
		$_log=$this->getLogData($_logData,$_timeStamp,$_referrer,$_useBrowseCap);
		
		
		// check if cache enabled
		//
		if ($_useCache && $_cacheConnected) // get data from cache
		{
			// memcache key
			$_key=$_logName. '-'. $this->getKey();
			
			// build log data array
			$_newLog[$_timeStamp]=$_log;
			
			// get existing data logged in this minute
			$_cachedLog=$this->__cache->cacheGet($_key);
			
			if ($_cachedLog) // if cached data exists add it to new data
			{
				$_cachedLog=unserialize($_cachedLog);
				
				if (is_array($_cachedLog)) {
					$_newLog=$_cachedLog + $_newLog;
				}
			}
			
			// write a new cache log - ttl set in config
			$this->__cache->cacheSet($_key,serialize($_newLog),$this->__config->get('logCacheTTL'));
			
			$this->set('success',true);
			
		} else {
			$this->set('errormessage','Cache disabled or Cache Error.');
		}
	}
}