<?php
namespace Bang;

class Breed {
    function __construct(string $configFile = null) {
		echo 'Breed!'.
			$configFile;
		return \Bang\Config::install($configFile);
	}
	function __destruct() {
		echo ':eof';
	}
}