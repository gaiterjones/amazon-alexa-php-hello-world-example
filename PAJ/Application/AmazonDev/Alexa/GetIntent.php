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
 */

namespace PAJ\Application\AmazonDev\Alexa;

class GetIntent {

    public $__environment;
    protected $__config;

    public function __construct
	(

	)
    {

    }


	/**
	 * Format an interval to show all existing components.
	 * If the interval doesn't have a time component (years, months, etc)
	 * That component won't be displayed.
	 *
	 * @param DateInterval $interval The interval
	 *
	 * @return string Formatted interval string.
	 */
	protected function format_interval(\DateInterval $interval) {
		$result = "";
		if ($interval->y) { $result .= $interval->format("%y years "); }
		if ($interval->m) { $result .= $interval->format("%m months "); }
		if ($interval->d) { $result .= $interval->format("%d days "); }
		if ($interval->h) { $result .= $interval->format("%h hours "); }
		if ($interval->i) { $result .= $interval->format("%i minutes "); }
		if ($interval->s) { $result .= $interval->format("%s seconds "); }

		return $result;
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
	        return $numberOfUnits.' '. $text.(($numberOfUnits>1)?'s':''). ' ago';
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
