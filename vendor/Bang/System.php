<?php
namespace Bang;


abstract class System {
	private
		$controller,
		$view,
		$model;

	protected
		$type;

    function __construct(string $configFile = null) {
		echo 'Bang System: '.$this->type.PHP_EOL;
		if ($configFile) {
			if (!file_exists($configFile)) throw new Error('file not exists: '.$configFile, 500);
			Config::install($configFile);
		}

	}
	function __destruct() {
		
	}
	function name(string $name):string {
		return preg_replace('/[^a-z][^a-z0-9]+/', '', strtolower($name));
	}
	function load() {
		if ($this->controller) {
			throw new Error('controller is already loaded', 500);
		}
		$controller = $this->type.'/controller';

	}
	function run() {
		
	}
}
