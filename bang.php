<?php
if (!function_exists('mb_internal_encoding')) {
	die('this source needs mbstring');
}
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
date_default_timezone_set('UTC');
error_reporting(E_ALL); #  & ~E_NOTICE

define('BANG_ROOT',				__DIR__);
define('BANG_UI',				BANG_ROOT.'/ui');
define('BANG_DATA',				BANG_ROOT.'/data');
define('BANG_VENDOR',			BANG_ROOT.'/vendor');
define('BANG_VERSION',			'4.0.0 Alpha-0');
define('BANG_CODENAME',			'OpenWorld v1.0');

if (!defined('SITE_PRIVATE')) {
	throw new Error('Missing SITE_PRIVATE defined', 10000);
}
if (!defined('SITE_CONTROLLERS'))
	define('SITE_CONTROLLERS',		constant('SITE_PRIVATE').'/controllers');
if (!defined('SITE_MODELS'))
	define('SITE_MODELS',			constant('SITE_PRIVATE').'/models');
if (!defined('SITE_VIEWS'))
	define('SITE_VIEWS',			constant('SITE_PRIVATE').'/views');


define('JSON_ENCODE_SETTINGS',	JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
define('DATE_SQL', 				'Y-m-d H:i:s');


set_error_handler(function (int $code, string $error, string $file, int $line, array $context = null) {
    $_ENV['errors'][] = [
		$code,
		$error,
		$file,
		$line,
		$context,
    ];
	print_r((object) ['error' => [$error, $code, $file, $line, $context]]);
	throw new Error($error, $code);
}, E_ALL);

spl_autoload_register(function ($class) {
	foreach (['SITE_VENDOR', 'SITE_SHARED_VENDOR', 'BANG_VENDOR'] as $vendor) {
		if (defined($vendor)) {
			$file = constant($vendor).
				DIRECTORY_SEPARATOR.
				str_replace('\\', DIRECTORY_SEPARATOR, $class).
				'.php';
			if (file_exists($file)) {
				require_once $file;
				\Bang\Core::mark($file);
				return;
			}
		}
	}
});


if (\Bang\Core::isCLI()) {
	echo 'Bang! v'.BANG_VERSION.PHP_EOL;
	$_SERVER['HTTP_HOST']
		= $_SERVER['HTTP_SERVER']
		= 'bang.commandline';
	$_SERVER['REQUEST_URI'] = !empty($argv[1]) ? $argv[1] : '/';
}

try {
	if (!defined('SITE_PRIVATE')) throw new Error('missing SITE_PRIVATE for config.php', 10001);
	$bang = new \Bang\Core(constant('SITE_PRIVATE').'/config.php');
} catch (\Exception $e) {
	echo 'EXP: '.$e->getCode().' '.$e->getMessage().' in '.$e->getFile().' on '.$e->getLine();
	exit;
} catch (\Error $e) {
	echo 'ERR: '.$e->getCode().' '.$e->getMessage().' in '.$e->getFile().' on '.$e->getLine();
	exit;
}