<?php
namespace Bang;

abstract class System {
	static
		$type = 'system';

    function __construct(string $configFile = null) {
		echo ':start of \Bang\System'.PHP_EOL;
		echo ':with configFile: '.$configFile.PHP_EOL;
		return \Bang\Config::install($configFile);
	}
	function __destruct() {
		echo ':end of system'.PHP_EOL;
	}
}
