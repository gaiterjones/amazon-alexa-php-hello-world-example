<?php
/*

Configuration Interface

*/

namespace PAJ\Application\AmazonDev;

interface Configuration {
  public function get($constant);
}
