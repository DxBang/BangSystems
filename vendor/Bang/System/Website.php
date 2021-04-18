<?php
namespace Bang\System;

class Website extends \Bang\System {
	protected
		$type = 'website';

    function __construct(string $configFile = null) {
		parent::__construct($configFile);
	}
}
