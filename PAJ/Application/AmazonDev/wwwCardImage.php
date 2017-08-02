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

use \PAJ\Application\AmazonDev\config as Config;
use \PAJ\Library\Log\LogToFile as Log;
use \PAJ\Application\AmazonDev\Alexa\Card\CardImageRequest as Request;
use \PAJ\Application\AmazonDev\Alexa\Card\CardImageFactory as Data;
use \PAJ\Application\AmazonDev\Alexa\Card\ValidateCardRequest as Security;


/**
 * www bootstrap
 */
class wwwCardImage implements ApplicationLoader
{
    public function __construct
	(

	)
    {
        $this->boot();
    }

/**
 * bootstrap
 * @return void
 */
    public function boot()
    {
        $_application = new wwwApp
    	(
    		new Config(),
            new Security(),
            new Log(),
    		new Request(),
            new Data()
    	);
    }
}
