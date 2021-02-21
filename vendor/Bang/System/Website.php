<?php
namespace Bang\System;

class Website extends \Bang\System {
	static
		$type = 'web';

    function __construct(string $configFile = null) {
		echo ':start of \Bang\System\Website'.PHP_EOL;
		if (empty($configFile))
			$configFile = SITE_PRIVATE.'/website/config.php';
			echo ':with configFile: '.$configFile.PHP_EOL;
		return parent::__construct($configFile);
	}
}
