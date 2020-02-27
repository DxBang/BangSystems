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
define('BANG_VERSION',			'4.0.0');
define('BANG_CODENAME',			'OpenWorld v1.0');

if (!defined('SITE_CONTROLLERS'))
	define('SITE_CONTROLLERS',		SITE_PRIVATE.'/controllers');
if (!defined('SITE_MODELS'))
	define('SITE_MODELS',			SITE_PRIVATE.'/models');
if (!defined('SITE_VIEWS'))
	define('SITE_VIEWS',			SITE_PRIVATE.'/views');

define('JSON_ENCODE_SETTINGS',	JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
define('DATE_SQL', 				'Y-m-d H:i:s');


set_error_handler(function (int $code, string $error, string $file, int $line, array $context) {
	throw new Error($error, $code);
	/*
	echo '<dl class="form">'
		.'<dt>code</dt><dd>'.$code.'</dd>'
		.'<dt>error</dt><dd>'.$error.'</dd>'
		.'<dt>file</dt><dd>'.$file.'</dd>'
		.'<dt>line</dt><dd>'.$line.'</dd>'
		.'<dt>context</dt><dd><pre>';
	print_r($context);
	echo '</pre></dd>'
		.'</dl>';
	*/
	return true;
}, E_ALL);

spl_autoload_register(function ($class) {
	if (defined('SITE_VENDOR')) {
		$file = constant('SITE_VENDOR').
			DIRECTORY_SEPARATOR.
			str_replace('\\', DIRECTORY_SEPARATOR, $class).
			'.php';
		if (file_exists($file)) {
			require_once $file;
			\Bang\Bang::mark($file);
			return;
		}
	}
	$file = constant('BANG_VENDOR').
		DIRECTORY_SEPARATOR.
		str_replace('\\', DIRECTORY_SEPARATOR, $class).
		'.php';
	if (file_exists($file)) {
		require_once $file;
		\Bang\Bang::mark($file);
		return;
	}
});

set_error_handler(function($errno, $errstr, $errfile, $errline, array $errcontext) {
    $_ENV['errors'][] = [
		$errno,
		$errstr,
		$errfile,
		$errline,
		$errcontext,
    ];
});

if (\Bang\Bang::isCLI()) {
	echo 'Bang! v'.BANG_VERSION.PHP_EOL;
	$_SERVER['HTTP_HOST']
		= $_SERVER['HTTP_SERVER']
		= 'bang.commandline';
	$_SERVER['REQUEST_URI'] = !empty($argv[1]) ? $argv[1] : '/';
}

try {
	return new \Bang\Bang(SITE_PRIVATE.'/configX.php');
} catch (\Exception $e) {
	new \Bang\Error($e);
}