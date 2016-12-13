<?php
/**
 *  
 *  Copyright (C) 2014
 *
 *
 *  @who	   	PAJ
 *  @info   	paj@gaiterjones.com
 *  @license    -
 * 	
 *
 */

namespace PAJ\Library\Log;

class Helper {
	
	function logThis($_logdata,$_logKey,$_toFile=true,$_logFile=false,$_toCache=true)
	{
		try
		{
			if ($_toCache)
			{
				// log to logging module (cache)
				$_obj=new LogToCache($_logdata,$_logKey);
					unset($_obj);
			}

			if ($_toFile && $_logFile) // optionally log to file
			{
				$_obj=new LogToFile(array(
					'logfile' => $_logFile,
					'data' => $_SERVER['REMOTE_ADDR']. ' '. $_logdata
				));
				
				unset($_obj);
			}
			
			return true;
			
		}
		catch (\Exception $e)
	    {
		    return false;
	    }
	}	
}
?>
