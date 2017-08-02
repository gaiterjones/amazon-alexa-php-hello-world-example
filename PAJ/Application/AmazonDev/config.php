<?php
/*

	Edit configuration settings here

*/

//
//
//

namespace PAJ\Application\AmazonDev;

class config implements Configuration
{
	// AMAZON ALEXA

	// CAUTION!!! comma seperared skill full id list i.e. amzn1.ask.skill.0b94bf43-9aee-4c53-955a-026f889aa23c
	const amazonSkillId = 'amzn1.ask.skill1.XXXXXXX,amzn1.ask.skill2.XXXXXXX';

	const amazonUserId = 'amzn1.ask.account.XXXXXXX';
	const amazonEchoServiceDomain = 'echo-api.amazon.com';
	const amazonCacheFolder='/var/www/logs/';
	const amazonCardImageFolder='/PATHTO/AmazonDev/Alexa/images/card/';
	const amazonAudioMP3Folder='/PATHTO/AmazonDev/Alexa/audio/mp3/';

	// timezone
	const timezone='Europe/Berlin';

	// configure memcache
	const useMemcache=false;
	const memcacheServer='cache_memcached_1';
	const memcacheServerPort='11211';
	const memcacheTTL='604800';
	const cacheKey='AMAZON';
	const cacheHTML=false;

	// configure session variables
	//
	const sessionEnabled = true;
	const sessionLifetime = 86400;

	// configure logging module
	//
	const loggingEnabled = true; // logs to logging module
	// path to folder for file logging
	const logFilePath = '/var/www/logs/';
	// ttl for cached log data
	const logCacheTTL = '86400'; // 24 hours
	// show ip address in logs - see log.php
	const logShowIP = false;
	// show geo info in logs - see log.php
	const logGeoInfo = false;

	// my constants here
	const applicationName = 'ALEXA';
	const applicationURL = 'https://www.YOURSITE.com/dropbox/dev/PAJ/www/AmazonDev/';
	const applicationDomain = 'YOURSITE.com';
	const siteTitle='Hello World';

	public $_serverURL;
	public $_serverPath;

	public function __construct()
	{
		if (php_sapi_name() != 'cli') {
			$this->_serverURL=$this->serverURL();
			$this->_serverPath=$this->serverPath();
		}
	}


    public function get($constant) {

	    $constant = 'self::'. $constant;

	    if(defined($constant)) {
	        return constant($constant);
	    }
	    else {
	        return false;
	    }

	}

	/**
	 * serverURL function.
	 *
	 * @access public
	 * @return string
	 */
	public function serverURL() {
	 $_serverURL = 'http';
	 if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {$_serverURL .= "s";}
	 $_serverURL .= "://";
	 if ($_SERVER["SERVER_PORT"] != "80") {
	  $_serverURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"];
	 } else {
	  $_serverURL .= $_SERVER["SERVER_NAME"];
	 }
	 return $_serverURL;
	}

	private function serverPath() {
	 $_serverPath=$_SERVER["REQUEST_URI"];
	 //$_serverPath=explode('?',$_serverPath);
	 //$_serverPath=$_serverPath[0];

	 return $_serverPath;
	}

}




?>
