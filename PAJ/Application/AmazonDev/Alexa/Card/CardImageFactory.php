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
namespace PAJ\Application\AmazonDev\Alexa\Card;
use PAJ\Application\AmazonDev\Data;

class CardImageFactory implements Data
{

    public $__environment;
    protected $__config;
    protected $__log;

    public function __construct
	(


	)
    {
    }

/**
 * getData
 * @return void
 */
    public function getData($_environment)
    {
        $this->loadEnvironment($_environment);

        $_expires = new \DateTime("now + 11 months");
		$_imageFolder=$this->__config->get('amazonCardImageFolder');

        $_imageFilename=$this->get('imagefilename');
        $_size=$this->get('imagesize');

		// Allow from any origin
		if (isset($_SERVER['HTTP_ORIGIN'])) {
			header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
			header('Access-Control-Allow-Credentials: true');
			header("Expires:" . $_expires->format(\DateTime::RFC1123));
		}
		// Access-Control headers are received during OPTIONS requests
		if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

			if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
				header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

			if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
				header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

		}

		header('Content-Type: image/png');

		if (file_exists($_imageFolder. $_size. '/'. $_imageFilename. '.png'))
		{
			readfile($_imageFolder. $_size. '/'. $_imageFilename. '.png');
		} else {
			// invalid file
		}
    }




    //
    //
    // ENVIRONMENT
    //
    //
    /**
     * load the environment
     *
     * @param  array $_data envrionment data and objects
     * @return void
     */
        protected function loadEnvironment($_environment)
        {

            //
            // load class variables into $this->__environment['data']
            //
            $_data=$_environment['data'];
            if ($_data)
            {
                foreach ($_data as $key => $value)
                {
                    $this->set($key,$value);
                }
            }

            //
            // load objects
            //
            $this->loadObjects($_environment['objects']);
        }

    /**
     * Load envrionment objects i.e. $this->__config
     *
     * @param  array $objects array of objects
     * @return void
     */
        protected function loadObjects($objects)
        {
            foreach($objects as $key => $value)
            {
                $obj='__'. $key;
                $this->$obj=$value;
            }

            $this->setObjects($objects);
        }
    /**
     * Set environment objects for injection into other method_exists
     *
     * @param array $objects array of objects name $key, object $value
     */
        public function setObjects($objects)
        {
            foreach($objects as $key => $value)
            {
                $this->__environment['objects'][$key] = $value;
            }
        }

        public function set($key,$value)
        {
            $this->__environment['data'][$key] = $value;
        }

        public function get($variable)
        {
            return $this->__environment['data'][$variable];
        }
}
