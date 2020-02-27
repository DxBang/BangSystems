<?php
namespace Bang;

final class Bang {
	static
		$db,
		$visitor;
	protected static
		$url,
		$route,
		$filter,
		$marks = [],
		$instance,
		$instances = [];
	private static
		$_get,
		$_post,
		$_files,
		$_filters;

	function __construct($configFile) {
		try {
			new Config($configFile);
			header('Server: Bang!', true);
			if (Config::isset('session')) {
				
			}
			return $this;
		}
		catch (\Exception $e) {

		}
	}
	static function mark(string $mark):void {
		if (!defined('BANG_DEBUG_MARKS')) return;
		if (constant('BANG_DEBUG_MARKS') == false) return;
		try {
			if (empty(self::$marks) && !empty($_SERVER['REQUEST_TIME_FLOAT'])) {
				self::$marks['pre'] = (object) [
					'microtime' => (float) $_SERVER['REQUEST_TIME_FLOAT'],
					'runtime' => (float) 0,
					'spent' => (float) 0,
					'usage_int' => (int) !empty($_SERVER['MEMORY_GET_USAGE']) ? $_SERVER['MEMORY_GET_USAGE'] : 0,
					'peak_usage_int' => (int) !empty($_SERVER['MEMORY_GET_PEAK_USAGE']) ? $_SERVER['MEMORY_GET_PEAK_USAGE'] : 0,
					'usage' => (string) '',
					'peak_usage' => (string) '',
					'usage_increased' => (string) '',
				];
			}
			self::$marks[$mark] = (object) [
				'microtime' => (float) microtime(1),
				'runtime' => (float) 0,
				'spent' => (float) 0,
				'usage_int' => (int) memory_get_usage(),
				'peak_usage_int' => (int) memory_get_peak_usage(),
				'usage' => (string) '',
				'peak_usage' => (string) '',
				'usage_increased' => (string) '',
			];
		} catch (Exception $e) {
			throw new Exception($e);
		}
	}
	static function marks():array {
		if (!defined('BANG_DEBUG_MARKS')) return [];
		if (constant('BANG_DEBUG_MARKS') == false) return [];
		$prev = (object) [
			'microtime' => (float) $_SERVER['REQUEST_TIME_FLOAT'],
			'usage_int' => (int) 0,
			'peak_usage_int' => (int) 0,
		];
		foreach (self::$marks as &$mark) {
			$mark->runtime = (float) $mark->microtime - self::$marks['pre']->microtime;
			$mark->spent = (float) $mark->microtime - $prev->microtime;
			$mark->usage = Format\Datasize::human($mark->usage_int);
			$mark->peak_usage = Format\Datasize::human($mark->peak_usage_int);
			$mark->usage_increased = Format\Datasize::human($mark->usage_int - $prev->usage_int);
			$prev = $mark;
		}
		return self::$marks;
	}
	static function isCLI():bool {
		return false;
	}
	static function isWeb():bool {
		return true;
	}
	static function isAPI():bool {
		return false;
	}
	static function secured():bool {

	}
	static function path(int $depth = -1) {

	}
}