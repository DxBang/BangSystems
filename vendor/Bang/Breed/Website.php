<?php
namespace Bang\Breed;

class Website extends \Bang\Breed {
    function __construct(string $configFile = null) {
		if (empty($configFile))
			$configFile = SITE_PRIVATE.'/website/config.php';
		parent::__construct($configFile);
	}
}