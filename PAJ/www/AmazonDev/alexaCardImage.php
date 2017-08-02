<?php
/**
 *
 *  Copyright (C) 2017
 *
 *
 *  @who	   	PAJ
 *	@what		Amazon Alexa Hello World Example App
 *  @info   	paj@gaiterjones.com
 *  @license    blog.gaiterjones.com
 *
 *
 */
namespace PAJ\Application;
include '../../autoload.php';
define ('ANS','AmazonDev'); // Application Name Space

/**
 *
 * ERROR HANDLING
 *
 */
//ini_set('display_errors', 1);
//error_reporting(E_ERROR);
//error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);


// boot
$_boot = new AmazonDev\wwwCardImage();
	unset($_boot);
		exit;


?>
