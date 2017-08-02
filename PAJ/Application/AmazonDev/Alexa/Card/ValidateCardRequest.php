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
use PAJ\Application\AmazonDev\Security;

class ValidateCardRequest implements Security
{

    public function __construct
	(


	)
    {
    }

    public function loadSecurity($_environment)
    {
        return true;
    }

}
