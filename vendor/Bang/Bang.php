<?php
namespace Bang;

final class Bang {
	static
		$db,
		$visitor;
	protected static
		$host,
		$domain,
		$sub,
		$urn,
		$uri,
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
		$_filters,
		$system;

	function __construct($configFile) {
		try {
			self::mark('bang->__construct()');
			header('Server: Bang!', true);
			header('Content-Type: text/plain');
			new Config($configFile);
			if (!self::isHost($_SERVER['HTTP_HOST']))
				throw new Error('Incorrect host', 100001);
			#if (!self::isDomain($_SERVER['HTTP_HOST']))
			#	throw new Error('Incorrect domain', 100002);

			self::$urn = isset($_SERVER['REQUEST_URI'])
				? '/'.trim(explode('?', rawurldecode($_SERVER['REQUEST_URI']), 2)[0], "\x00..\x20\/")
				: '/';
			self::$uri = preg_split('/\//', trim(self::$urn, '/'));
			
			if (Config::get('verifyURL')) {
				self::$url = new Internet\URL(
					$_SERVER['REQUEST_URI']
				);
			}
			if (Config::isset('session')) {
				Config::set('session', 'cookie_domain', self::$domain);
				Config::set('session', 'cookie_path', '/; SameSite=Strict');
				Config::set('session', 'cookie_domain', self::domain());
				Config::set('session', 'cookie_secure', self::secured());
				Config::set('session', 'cookie_httponly', true);
			}

			Config::debug();
			return $this;
		}
		catch (\Error $e) {
			echo $e->getCode().':'.$e->getMessage();
			die('---');
		}
		catch (\Exception $e) {

		}
	}
	function __destruct() {
		print_r(
			(object) ['self::debug' => self::debug()]
		);
		print_r(
			(object) ['Config::debug' => Config::debug()]
		);
		print_r(
			self::marks()
		);
		echo ':end of bang'.PHP_EOL;
	}
	static function debug() {
		return (object) [
			'system' => self::$system ?? null,
			'isAPI' => self::isAPI(),
			'isCLI' => self::isCLI(),
			'isWeb' => self::isWeb(),
			'secured' => self::secured(),
			'host' => self::$host,
			'domain' => self::$domain,
			'sub' => self::$sub,
			'urn' => self::$urn,
			'uri' => self::$uri,
		];
	}
	static function system(System $system) {
		self::$system = $system;
	}
	static function mark(string $mark):void {
		if (!defined('BANG_DEBUG_MARKS')) return;
		if (constant('BANG_DEBUG_MARKS') == false) return;
		try {
			if (empty(self::$marks) && !empty($_SERVER['REQUEST_TIME_FLOAT'])) {
				self::$marks['pre'] = (object) [
					'microtime' => (double) $_SERVER['REQUEST_TIME_FLOAT'],
					'runtime' => (double) 0,
					'spent' => (double) 0,
					'usage_int' => (int) !empty($_SERVER['MEMORY_GET_USAGE']) ? $_SERVER['MEMORY_GET_USAGE'] : 0,
					'peak_usage_int' => (int) !empty($_SERVER['MEMORY_GET_PEAK_USAGE']) ? $_SERVER['MEMORY_GET_PEAK_USAGE'] : 0,
					'usage' => (string) '',
					'peak_usage' => (string) '',
					'usage_increased' => (string) '',
				];
			}
			self::$marks[$mark] = (object) [
				'microtime' => (double) microtime(1),
				'runtime' => (double) 0,
				'spent' => (double) 0,
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
		$prev = self::$marks['pre'];
		foreach (self::$marks as $k => &$mark) {
			if ($k == 'pre') continue;
			$mark->runtime = (double) $mark->microtime - self::$marks['pre']->microtime;
			$mark->spent = (double) $mark->microtime - $prev->nanotime;
			$mark->usage = Format\Datasize::human($mark->usage_int);
			$mark->peak_usage = Format\Datasize::human($mark->peak_usage_int);
			$mark->usage_increased = Format\Datasize::human($mark->usage_int - $prev->usage_int);
			$prev = $mark;
		}
		return self::$marks;
	}
	static function isSystem():bool {
		return (is_object(self::$system)
			&& self::$system::$type == 'system');
	}
	static function isAPI():bool {
		return (is_object(self::$system)
			&& self::$system::$type == 'api');
	}
	static function isCLI():bool {
		return (php_sapi_name() == 'cli');
	}
	static function isMedia():bool {
		return (is_object(self::$system)
			&& self::$system::$type == 'media');
	}
	static function isWeb():bool {
		echo 'is_object(self::$system): '.is_object(self::$system).'/'.gettype(self::$system).PHP_EOL;
		return (is_object(self::$system)
			&& self::$system::$type == 'web');
	}
	static function protocol():string {
		return isset($_SERVER['HTTPS']) ? 'https' : 'http';
	}
	static function SSL():bool {
		return self::secured();
	}
	static function secured():bool {
		return (self::protocol() == 'https');
	}
	static function host():string {
		return self::$host ?? '';
	}
	static function domain():string {
		return self::$domain ?? '';
	}
	static function sub():string {
		return self::$sub ?? '';
	}
	static function isHost(string $host):bool {
		echo 'self::$host::: '.self::$host.PHP_EOL;
		return ($host == self::$host);
		$c = Config::get('host');
		if (is_array($c)) {
			return in_array($host, $c);
		}
		return ($host == $c);
	}
	static function isDomain(string $domain):bool {
		return ($domain == self::$domain);
		$c = Config::get('domain');
		if (self::findDomain($domain, $c))
			return true;
		return false;
	}
	static function URN():string {
		return self::$urn;
	}
	static function URI(int $depth = null) {
		if (!is_null($depth)) {
			if ($depth == -1) {
				return self::$uri;
			}
			return isset(self::$uri[$depth]) ? self::$uri[$depth] : null;
		}
		return self::$urn;
	}
	static function path(int $depth = -1) {
		return self::URI($depth);
	}
	static function nanotime() {
		return (double) implode('.', hrtime());
	}

	static function setupDomain($domain) {
		print_r($domain);
		foreach((array) $domain as $v) {
			echo 'setupDomain: '.$domain.' vs '.$v.PHP_EOL;
			if ($m = self::findDomain($v, $_SERVER['HTTP_HOST'])) {
				print_r($m);
				self::$domain = $m[2];
				self::$host = $m[0];
				self::$sub = trim($m[1], '.');
				break;
			}
		}
	}
	static function setupHost($host) {
		$done = false;
		foreach((array) $host as $v) {
			if (self::$host == $v)
				$done = true;
		}
		return $done;
	}
	static private function findDomain(string $domain, $area) {
		if (is_string($area)) {
			if (preg_match('/(.*)('.preg_quote($domain, '.-_').')$/', $area, $m)) {
				return $m;
			}
		}
		if (is_array($area)) {
			foreach($area as $v) {
				$m = self::findDomain($domain, $v);
				if ($m) {
					return $m;
				}
			}
		}
	}
}
