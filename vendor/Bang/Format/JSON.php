<?php
namespace Bang\Format;

class JSON {

	protected static
		$format = 0,
		$throw = 0;
	const
		FORMAT_DEFAULT = 0,
		FORMAT_PRETTY = 1,
		THROW_NONE = 0,
		THROW_EXCEPTION = 1;

	function __construct(int $format=self::FORMAT_DEFAULT, int $throw=self::THROW_EXCEPTION) {
		$this->format($format);
		$this->throw($throw);
	}
	function __destruct() {
		
	}
	function throw(int $throw=self::THROW_EXCEPTION):object {
		self::$throw = $throw;
		return $this;
	}
	
	private static function doThrow(int $throw):bool {
		return self::isBitwise('throw', $throw);
	}
	function format(int $format=self::FORMAT_DEFAULT):object {
		self::$format = $format;
		return $this;
	}
	private static function doFormat(int $format):bool {
		return self::isBitwise('format', $format);
	}
	private static function isBitwise(string $key, int $value) {
		if ($value)
			return ((self::$key & $value) === $value);
		return (self::$key === $value);
	}
	static function header() {
		if (!headers_sent()) {
			header('Content-Type: application/json; charset=UTF-8');
		}
	}
	static function output(mixed $mixed) {
		if (self::doFormat(self::FORMAT_DEFAULT)) {

		}
	}
	static function read(string $string) {

	}
	static function readFile(string $file) {

	}
	static function write(mixed $json) {

	}
	static function writeFile(mixed $json, string $file) {

	}
	static function parse(string $string) {
		return self::read($string);
	}
	static function stringify(mixed $json) {
		return self::write($json);
	}
}