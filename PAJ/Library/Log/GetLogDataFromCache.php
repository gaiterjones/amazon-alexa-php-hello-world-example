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
 *  Returns log data from memcache
 *
 *  USAGE: http://www.medazzaland.co.uk/dropbox/dev/PAJ/www/DataLogger/?ajax&class=PAJ_Library_Log_GetLogDataFromCache&variables=minutes=30|timestamp=1|logname=BLAH
 *
 *	MINUTES - x mins of data
 *	TIMESTAMP - the last time stamp (1)
 *	LOGNAME - NAMESPACE for MEMCACHE
 */

namespace PAJ\Library\Log;

class GetLogDataFromCache extends \PAJ\Library\Log\LogController {


	public function __construct($_variables) {
		
		parent::__construct();
		
		$this->loadClassVariables($_variables);

		$this->getLogFile();
	}
	
	/**
	 * getLogFile function.
	 * @what retrieve log data from cache
	 * @access private
	 * @return nix
	 */	
	private function getLogFile()
	{
		$this->set('success',false);
		$this->set('errormessage','Not defined');
		
		$_useCache=$this->__config->get('useMemcache');
		$_cacheConnected=$this->__cache->get('memcacheconnected');
		$_clientTimeStamp=$this->get('timestamp');
		$_logName=$this->get('logname');
		
		$_timeRangeCount=0;
		$_pollCount=0;
		
		$_minutes=$this->get('minutes'); // from client - time range to grab logs from
		date_default_timezone_set($this->get('timezone'));
		
		$_time = strtotime("now"); // right here, right now.
		$_logData=array(); // log data container
		
		if ($_useCache && $_cacheConnected) // get data from cache
		{		
			
			// get logs for requested time range - (now - minutes)
			for ($i = (int)$_minutes; $i >=0; $i=$i-1) {
			
				$_data=$this->__cache->cacheGet($_logName. '-'. date('dmy'). '-'. date("H", strtotime('-'. $i. ' minutes', $_time)). date("i", strtotime('-'. $i. ' minutes', $_time))); // get cache date DMY-HM eg 20082013-1120
					
					if ($_data)	{ // cached data found
						
						$_cacheData=unserialize($_data); // extract from cache
												
						foreach ($_cacheData as $_timeStamp => $_data) // return records newer than client timestamp.
						{
							$_timeRangeCount ++; // number of hits in this time range
							
							if ($_timeStamp > $_clientTimeStamp)
							{
								$_pollCount ++; // number of hits since last poll
								
								$_logData[$_timeStamp]=$_data;
							}
						}
					}			
			}

			$this->set('success',true);
			$this->set('output',array(
				'logdata' => $_logData,
				'timerangecount' => $_timeRangeCount,
				'pollcount' => $_pollCount,
				'minutes' => $_minutes,
				'timezone' => $this->get('timezone')
			));
		} else {
			$this->set('errormessage','Cache disabled or Cache Error.');
		}
	}
	
}