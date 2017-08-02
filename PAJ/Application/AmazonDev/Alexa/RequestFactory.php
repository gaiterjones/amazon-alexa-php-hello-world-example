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
namespace PAJ\Application\AmazonDev\Alexa;
use PAJ\Application\AmazonDev\Request;

class RequestFactory implements Request
{
    public function __construct
	(


	)
    {
    }
/**
 * Get Request (input) data for application
 * @return array Input Data
 */
    public function getData()
    {
        // Get Amazon POST JSON data
		//
		$_jsonRequest    = file_get_contents('php://input');
		$_data           = json_decode($_jsonRequest, true);

        return array(
            'alexarequest' => $_data,
            'alexajsonrequest' => $_jsonRequest
        );
    }
}
