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

	
 */

namespace PAJ\Application\Amazon\Alexa;

/**
 * CardImage
 * @what return image with correct headers for alexa card response
 */
class CardImage extends \PAJ\Application\Amazon\Controller {
	
	public function __construct() {
		
		$this->loadConfig();
		$this->sendImage();
		exit;
	}	
	
	
	protected function sendImage()
	{
		$_expires = new \DateTime("now + 11 months");
		
		if(isset($_GET['image'])){ $_imageFilename = $_GET['image'];} else { $_imageFilename = 'default';}	
		if(isset($_GET['size'])){ $_size = $_GET['size'];} else { $_size = 'small';}

		$_imageFolder=$this->__config->get('amazonCardImageFolder');
		
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
	
}