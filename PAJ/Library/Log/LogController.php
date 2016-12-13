<?php
/**
 *  PAJ\Library
	Log Controller Class
 '
 *  Copyright (C) 2016
 *
 *
 *  @who	   	PAJ
 *  @info   	paj@gaiterjones.com
 *  @license    blog.gaiterjones.com
 * 	
 *	22072016 - added boo to switch between browesecap and petecap
 *
 */

namespace PAJ\Library\Log;

class LogController{

	protected $__;
	protected $__config;
	protected $__cache;

	/**
	 * construct function.
	 * @what class constructor
	 * @access public
	 * @return nix
	 */			
	public function __construct() {

		$this->loadConfig();
		$this->loadMemcache();
	}
	
	/**
	 * destruct function.
	 * @what class destructor
	 * @access public
	 * @return nix
	 */	
	public function __destruct()
	{
		unset($this->__config);
		unset($this->__);
		unset($this->__t);		
	}

	/**
	 * config function.
	 * @what class configuration
	 * @access private
	 * @return nix
	 */	
	private function loadConfig()
	{
		if (!defined('ANS')) { throw new \Exception ('No configuration class specified. (SEC)'); }
		
		$_class = '\\PAJ\\Application\\'. ANS. '\\config';
		
		$this->__config= new $_class();
		
		$this->set('timezone','Europe/Berlin');
		$this->set('logFilePath',$this->__config->get('logFilePath'));
		$this->set('logCacheTTL',$this->__config->get('logCacheTTL'));
	}

	/**
	 * memcache loader function.
	 * @what loads memcache class
	 * @access private
	 * @return nix
	 */		
	private function loadMemcache()
	{
		$this->__cache=new \PAJ\Library\Cache\Memcache();
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
	
	/**
	 * getLogCounter function.
	 * @what gets a memcache counter used to numerate logs
	 * @access protected
	 * @return INTEGER COUNTER
	 */	
	protected function getLogCounter()
	{
	
		$_counter = $this->__cache->cacheGet('logcounter'); // get version from cache
        
        if ($_counter === false) { // if namespace not in cache reset to 1
            $_counter = 1;
            $this->__cache->cacheSet('logcounter', $_counter,2592000); // save to cache note ttl!
        }
        
        return $_counter;
        
	}	

	/**
	 * incLogCounter function.
	 * @what increments a counter in memcache
	 * @access public
	 * @return INTEGER COUNTER
	 */		
	protected function incLogCounter($_cacheNameSpace='logcounter')
	{
		$this->__cache->increment($_cacheNameSpace);
		return ($this->getLogCounter());
	}

	/**
	 * getKey function.
	 * @what defines a key to use to set memcache object
	 * @access protected
	 * @return STRING KEY
	 */	
	protected function getKey()
	{
		date_default_timezone_set($this->get('timezone'));
		
		$_date= date('dmy'); // date
		$_minute= date('i'); // minutes with leading zero
		$_hour= date('H'); // hours in 24 hour time
	
		$_key=$_date.'-'.$_hour.$_minute;
		return ($_key);
	}


	/**
	 * getLogData function.
	 * @what creates an array of log data using USER AGENT, Date Time, IP etc.
	 * @access protected
	 * @return ARRAY
	 */		
	protected function getLogData($_logData,$_timeStamp,$_referrer=false,$_useBrowseCap=true)
	{
			// define log data variables
			if (php_sapi_name() != 'cli')
			{
				$_userAgent="Unknown";
				$_browser="Unknown";
				$_os="Unknown";
			} else {
				$_userAgent="CLI";
				$_browser="CLI";
				$_os="CLI";				
			}
			
			$_scriptName = pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME);
			$_time = date('H:i:s');
			
			$_ip = 'NO_IP';
			$_ipText = 'NO_IP';
			
			if (isset($_SERVER["REMOTE_ADDR"]))
			{
				
				$_ip = $_SERVER["REMOTE_ADDR"];
			
				if ($this->__config->get('logShowIP')) {
					if ($this->__config->get('logGeoInfo')) {
						$_ipText = 'Loading...';
					} else {
						$_ipText = $_SERVER["REMOTE_ADDR"]; //get ip address
					}
				} else {
					$_ipText = 'IP Hidden';
				}
			}
			
			$_referrerDomain='unknown';
			$_googleSearchQuery=false;
			
			if ($_referrer && $_referrer !=='unknown' && php_sapi_name() != 'cli')
			{
				// get domain from referrer
				$_referrerDomain=parse_url($_referrer);
				$_referrerDomain=$_referrerDomain['host'];
				
				if (strpos($_referrerDomain, 'google') !== false)
				{
					// came from google
					$_googleSearchQueryArray=explode('q=',$_referrer);
					if (isset($_googleSearchQueryArray[1]))
					{
						$_googleSearchQuery=$_googleSearchQueryArray[1];
					}
				}
			}			
			
			try {
			
				if (isset($_SERVER["HTTP_USER_AGENT"])) {
				
					$_userAgent = $_SERVER["HTTP_USER_AGENT"];
					
					set_error_handler(array($this, 'handleError')); // catch all errors	
					
					if ($_useBrowseCap) // get user agent from browsecap
					{
						$_obj= new \PAJ\Library\External\phpbrowscap\Browscap('/home/www/medazzaland/cache/');
						$_currentBrowser = $_obj->getBrowser();
						unset($_obj);
						
						$_browser=$_currentBrowser->Browser. 
						($_currentBrowser->Version > 0 ? ' v'. $_currentBrowser->Version : '').
						($_currentBrowser->isMobileDevice ? ' (M)' : '');
						
						$_os=$_currentBrowser->Platform;
						
					} else {
						
						$_browser=$this->getBrowser($_userAgent);
						$_os=$this->getOS($_userAgent);	
					}
					
					restore_error_handler();
				}
			}
			catch (\Exception $_e)
			{
				restore_error_handler();
				$_userAgent="Error";
				$_browser="Error";
				$_os="Error";					
				// keep soldiering on
				
				//print_r($_e);
				//exit;
			}

			return (array('id' => $this->incLogCounter(), 'time' => $_time, 'script' => $_scriptName, 'os' => $_os, 'useragent' => $_userAgent, 'iptext' => $_ipText, 'ip' => $_ip, 'referrer' => $_referrerDomain,'referrerfull' => $_referrer, 'googlesearchquery' => $_googleSearchQuery, 'data' => $_logData, 'browser' => $_browser));
	}

	protected function getBrowser($_userAgent) {
	  // Create list of browsers with browser name as array key and user agent as value. 
		$browsers = array(
			'Facebook'=> '(FBIOS)',
			'Edge'=> '(Edge)',
			'Chrome'=> '(Chrome|CriOS)',
			'Safari' => 'Safari',			
			'Opera' => 'Opera',
			'Firefox'=> '(Firebird|Firefox)', // Use regular expressions as value to identify browser
			'Galeon' => 'Galeon',
			'Internet Explorer 11' => '(Trident\/7.0; rv:11)',
			'Mozilla'=>'Gecko',
			'MyIE'=>'MyIE',
			'Lynx' => 'Lynx',
			'Netscape' => '(Mozilla/4\.75|Netscape6|Mozilla/4\.08|Mozilla/4\.5|Mozilla/4\.6|Mozilla/4\.79)',
			'Konqueror'=>'Konqueror',
			'SearchBot' => '(nuhk|Googlebot|Yammybot|Openbot|Slurp\/cat|msnbot|ia_archiver)',
			'Internet Explorer 10' => '(MSIE 10\.[0-9]+)',
			'Internet Explorer 9' => '(MSIE 9\.[0-9]+)',
			'Internet Explorer 8' => '(MSIE 8\.[0-9]+)',
			'Internet Explorer 7' => '(MSIE 7\.[0-9]+)',
			'Internet Explorer 6' => '(MSIE 6\.[0-9]+)',
			'Internet Explorer 5' => '(MSIE 5\.[0-9]+)',
			'Internet Explorer 4' => '(MSIE 4\.[0-9]+)',
		);

		foreach($browsers as $browser=>$pattern) { // Loop through $browsers array
		// Use regular expressions to check browser type
			if(preg_match('/'. $pattern. '/i', $_userAgent)) { // Check if a value in $browsers array matches current user agent.
				return $browser; // Browser was matched so return $browsers key
			}
		}
		
		return 'Unknown Browser'; // Cannot find browser so return Unknown
	}	

	protected function getOS($_userAgent) {
	  // Create list of operating systems with operating system name as array key 
		$oses = array (
			'iPhone' => '(iPhone1,1;)',
			'iPhone 3G' => '(iPhone1,2;)',
			'iPhone 3GS' => '(iPhone1,3;)',
			'iPhone 4' => '(iPhone3,1;)',
			'iPhone 4S' => '(iPhone4,1;)',
			'iPhone 5' => '(iPhone5,)',
			'iPhone 5S' => '(iPhone6,)',
			'iPad 1' => '(iPad1,1;)',
			'iPad 2' => '(iPad2,1;)',
			'iPad 3' => '(iPad3,1;)',
			'iPad' => '(iPad4,1;)',
			'iPad' => '(iPad)',
			'iPod' => '(iPod)',
			'iPhone' => '(iPhone)',
			'Android' => 'Android',
			'Blackberry' => 'Blackberry',
			'Windows Mobile' => 'IEMobile',
			'Windows 3.11' => 'Win16',
			'Windows 95' => '(Windows 95|Win95|Windows_95)', // Use regular expressions as value to identify operating system
			'Windows 98' => '(Windows 98|Win98)',
			'Windows 2000' => '(Windows NT 5.0|Windows 2000)',
			'Windows XP' => '(Windows NT 5.1|Windows XP)',
			'Windows 2003' => '(Windows NT 5.2)',
			'Windows Vista' => '(Windows NT 6.0|Windows Vista)',
			'Windows 7' => '(Windows NT 6.1|Windows 7)',
			'Windows 8' => '(Windows NT 6.2|Windows 8)',
			'Windows 8.1' => '(Windows NT 6.3|Windows 8.1)',
			'Windows 10' => '(Windows NT 10.0)',
			'Windows NT 4.0' => '(Windows NT 4.0|WinNT4.0|WinNT|Windows NT)',
			'Windows ME' => 'Windows ME',
			'Open BSD'=>'OpenBSD',
			'Sun OS'=>'SunOS',
			'Linux'=>'(Linux|X11)',
			'Macintosh'=>'(Mac_PowerPC|Macintosh)',
			'Safari' => '(Safari)',			
			'QNX'=>'QNX',
			'BeOS'=>'BeOS',
			'OS/2'=>'OS/2',
			'Search Bot'=>'(nuhk|Googlebot|Yammybot|Openbot|Slurp\/cat|msnbot|ia_archiver|EasouSpider)'
		);

		foreach($oses as $os=>$pattern){ // Loop through $oses array
		// Use regular expressions to check operating system type
			if(preg_match('/'. $pattern. '/i', $_userAgent)) { // Check if a value in $oses array matches current user agent.
				return $os; // Operating system was matched so return $oses key
			}
		}
		return 'Unknown OS'; // Cannot find operating system so return Unknown
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
	        return $numberOfUnits.' '. $this->__t->__($text.(($numberOfUnits>1)?'s':''). ' ago.');
	    }
	
	}

	/**
	 * loadClassVariables function.
	 * @what loads in variables passed to class
	 * @access protected
	 * @return nix
	 */	
	protected function loadClassVariables($_variables)
	{
		foreach ($_variables as $_variableName=>$_variableData)
		{
			// check for optional data
			if (substr($_variableName, -8) === 'optional') { continue; }
			
			$_variableData=trim($_variableData);
			if (empty($_variableData)) {
				throw new \Exception('Class variable '.$_variableName. ' cannot be empty.');
			}
			
			$this->set($_variableName,$_variableData);
						
		}
	}
	
	public function handleError($errno, $errstr, $errfile, $errline, array $errcontext)
	{
		// error was suppressed with the @-operator
		if (0 === error_reporting()) {
			return false;
		}
		

		throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
	}	
}


