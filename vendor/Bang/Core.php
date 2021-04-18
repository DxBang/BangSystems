<?php
namespace Bang;

final class Core {
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

		self::mark('bang->__construct()');
		header('Server: Bang!', true);
		header('Content-Type: text/plain');
		$config = new Config($configFile);
		if (!self::isHost($_SERVER['HTTP_HOST']))
			throw new \Error('Incorrect host', 100001);
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
			echo 'session isset'.PHP_EOL;
			print_r(
				Config::get('session')
			);
			Config::set('session', 'cookie_domain', '.'.self::domain());
			Config::set('session', 'cookie_secure', self::isSecured());
			Config::set('session', 'cookie_httponly', true);
			Config::set('session', 'cookie_samesite', 'Lax');
			Config::set('session', 'use_strict_mode', true);
			Config::set('session', 'use_cookies', true);
			Config::set('session', 'use_only_cookies', true);
			self::session();
		}
		Config::debug();
	}
	function __destruct() {
		print_r(
			(object) ['Core::debug' => self::debug()]
		);
		/*
		print_r(
			(object) ['Config::debug' => Config::debug()]
		);
		*/
		print_r(
			self::marks()
		);
		echo ':end of bang'.PHP_EOL;
	}
	static function debug() {
		return (object) [
			'system' => self::$system ?? null,
			'isCLI' => self::isCLI(),
			'secured' => self::isSecured(),
			'host' => self::host(),
			'domain' => self::domain(),
			'sub' => self::sub(),
			'urn' => self::urn(),
			'uri' => self::uri(),
		];
	}

	static private function session() {
		self::mark('Bang\Core->session()');
		$config = Config::get('session');
		if (is_null($config)) return;
		session_start((array) $config);
		#self::$visitor = new Visitor();
		if (isset($_COOKIE[session_name()])) {
			if (!isset($_SESSION['expire'])) {
				$_SESSION['expire'] = time() + ($config->cookie_lifetime / 2);
				return;
			}
			else if ($_SESSION['expire'] <= time()) {
				session_regenerate_id(true);
				$_SESSION['expire'] = time() + ($config->cookie_lifetime / 2);
			}
		}
	}
	static function system(System $system) {
		self::$system = $system;
	}
	static function mark(string $mark):void {
		if (!defined('BANG_DEBUG_MARKS')) return;
		if (constant('BANG_DEBUG_MARKS') == false) return;
		try {
			if (empty(self::$marks)) {
				self::$marks['init'] = (object) [
					'microtime' => (double) !empty($_SERVER['REQUEST_TIME_FLOAT']) ? $_SERVER['REQUEST_TIME_FLOAT'] : microtime(1),
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

		$prev = self::$marks['init'];
		foreach (self::$marks as $k => &$mark) {
			if ($k == 'init') continue;
			$mark->runtime = (double) $mark->microtime - self::$marks['init']->microtime;
			$mark->spent = (double) $mark->microtime - $prev->microtime;
			$mark->usage = Format\Datasize::human($mark->usage_int);
			$mark->peak_usage = Format\Datasize::human($mark->peak_usage_int);
			$mark->usage_increased = Format\Datasize::human($mark->usage_int - $prev->usage_int);
			$prev = $mark;
		}
		return self::$marks;
	}
	static function isCLI():bool {
		return (php_sapi_name() == 'cli');
	}
	static function isSSL():bool {
		return self::isSecured();
	}
	static function isSecured():bool {
		return (self::protocol() == 'https');
	}
	static function protocol():string {
		return isset($_SERVER['HTTPS']) ? 'https' : 'http';
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
		#return ($host == self::$host);
		$c = Config::get('host');
		if (is_array($c)) {
			return in_array($host, $c);
		}
		return ($host == $c);
	}
	static function isDomain(string $domain):bool {
		return ($domain == self::$domain);
		$c = Config::get('domain');
		if (self::findDomainInHost($domain, $c))
			return true;
		return false;
	}
	static function URN():string {
		return self::$urn;
	}
	static function URI(int $depth = null):string|array {
		if (!is_null($depth)) {
			if ($depth == -1) {
				return self::$uri;
			}
			return isset(self::$uri[$depth]) ? self::$uri[$depth] : '';
		}
		return self::$urn;
	}
	static function path(int $depth = -1):string|array {
		return self::URI($depth);
	}
	static function nanotime() {
		return (double) implode('.', hrtime());
	}

	static function setupDomain(array $domain) {
		foreach($domain as $v) {
			echo 'setupDomain: '.$v.PHP_EOL;
			if ($m = self::findDomainInHost($v, $_SERVER['HTTP_HOST'])) {
				print_r($m);
				self::$domain = $m->domain;
				self::$host = $m->host;
				self::$sub = $m->sub;
				return true;
			}
		}
		return false;
	}
	static function setupHost(array $host) {
		foreach($host as $v) {
			if (self::$host == $v)
				return true;
		}
		return false;
	}
	static private function findDomainInHost(string $domain, string|array $area) {
		if (is_string($area)) {
			if (preg_match('/(.*)('.preg_quote($domain, '.-_').')$/', $area, $m)) {
				return (object) [
					'domain' => $m[2],
					'host' => $m[0],
					'sub' => trim($m[1], '.'),
				];
			}
		}
		if (is_array($area)) {
			foreach($area as $v) {
				$m = self::findDomainInHost($domain, $v);
				if ($m) {
					return $m;
				}
			}
		}
	}
}
