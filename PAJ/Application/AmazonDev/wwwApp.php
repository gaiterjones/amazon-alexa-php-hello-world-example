<?php
/**
 *
 *  Copyright (C) 2017
 *
 *
 *  @who	   	PAJ
 *  @info   	paj@gaiterjones.com
 *  @license    blog.gaiterjones.com
 *
 *
 *
 */
namespace PAJ\Application\AmazonDev;
use \PAJ\Library\Log\LogToFile as Log;

/**
 * wwwApp
 */
class wwwApp implements Application
{
    public $__environment;
    protected $__config;
    protected $__security;
    protected $__log;
    protected $__request;
    protected $__data;

/**
 * Constructor
 * @param Configuration $config   Application config
 * @param Security      $security Security Factory
 * @param Log           $log      Logging Library
 * @param Request       $request  Request Factory
 * @param Data          $data     Data Factory
 */
    public function __construct
	(
		Configuration $config,
		Security $security,
        Log $log,
		Request $request,
        Data $data

	)
    {
        $this->__config=$config;
        $this->__security=$security;
        $this->__log=$log;
        $this->__request=$request;
        $this->__data=$data;

        try
		{
            $this->loadConfig();
            $this->getRequest();
            $this->loadSecurity();
            $this->getData();
        }
		catch (\Exception $e)
	    {
            if ($this->get('debug'))
            {
                // log errors
                //
                $this->__log->writeLogFile($e->getMessage(),$this->get('amazonLogFile'));

            }
            exit;
	    }

    }


//
//
//    Interfaces
//
//


/**
 * Configuration
 *
 * @return void
 */
    public function loadConfig()
    {
        $_version='v0.2.1';
		$_versionNumber=explode('-',$_version);
		$_versionNumber=$_versionNumber[0];

		$_debug=true; // enable for debug logging

        // environment variables
        //
		$this->set('debug',$_debug);
		$this->set('version',$_version);
		$this->set('versionNumber',$_versionNumber);
		$this->set('amazonLogFile',$this->__config->get('amazonCacheFolder'). 'alexarequest');
		$this->set('amazonLogFolder',$this->__config->get('amazonCacheFolder'));
		$this->set('applicationURL',$this->__config->get('applicationURL'));
		$this->set('applicationName',$this->__config->get('applicationName'));

        // environment objects
        //
        $this->setObjects(array('config' => $this->__config));
        $this->setObjects(array('log' => $this->__log));
    }

/**
 * REQUEST FACTORY
 * gets Alexa Request POST data (input) and loads into $__environment
 *
 * @return void
 */
    public function getRequest()
    {
        $this->setData
        (
            $this->__request->getData()
        );

    }

/**
 * SECURITY
 * validates Alexa requests according to API documentation required for skill certification
 *
 * @return void
 */
    public function loadSecurity()
    {
        $this->__security->loadSecurity
        (
            $this->__environment
        );
    }



/**
 *
 * DATA FACTORY
 * injects $__environment into FACTORY
 * RendersResponses to Alexa Intent Requests
 *
 * @return void
 */
    public function getData()
    {
        $this->__data->getData
        (
            $this->__environment
        );
    }

//
//
//
//

/**
 * setData
 * @param Array $variables Load data array into $__environment[data]
 */
    public function setData($variables)
    {
        foreach($variables as $key => $value)
        {
            $this->set($key,$value);
        }
    }
/**
 * setObjects
 * @param object $objects Load object in $__environment[objectname]
 */
    public function setObjects($objects)
    {
        foreach($objects as $key => $value)
        {
            $this->__environment['objects'][$key] = $value;
        }
    }

/**
 * set
 * @param string $key   data key name
 * @param string $value data value
 */
    public function set($key,$value)
    {
        $this->__environment['data'][$key] = $value;
    }
/**
 * get
 * @param  string $variable data name
 * @return multi - return the data
 */
    public function get($variable)
    {
        return $this->__environment['data'][$variable];
    }

}
