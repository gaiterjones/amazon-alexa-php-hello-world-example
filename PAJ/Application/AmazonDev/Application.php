<?php
/*

Application Interface

*/

namespace PAJ\Application\AmazonDev;

interface Application {
    public function loadConfig();
    public function loadSecurity();
    public function getRequest();
    public function getData();
}
