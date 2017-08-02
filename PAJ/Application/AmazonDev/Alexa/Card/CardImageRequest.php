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
use PAJ\Application\AmazonDev\Request;

class CardImageRequest implements Request
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
        if(isset($_GET['image'])){ $_imageFilename = $_GET['image'];} else { $_imageFilename = 'default';}
		if(isset($_GET['size'])){ $_size = $_GET['size'];} else { $_size = 'small';}

        return array(
            'imagefilename' => $_imageFilename,
            'imagesize' => $_size
        );
    }
}
