<?php
namespace Bang\Format;

class Color {
	static function hex2hsl(string $hex) {}
	static function hex2raw(string $hex) {}
	static function hex2rgb(string $hex) {}

	static function hsl2hex(int $h, int $s, int $l, int $a = null) {}
	static function hsl2raw(int $h, int $s, int $l, int $a = null) {}
	static function hsl2rgb(int $h, int $s, int $l, int $a = null) {}

	static function rgb2hex(int $r, int $g, int $b, int $a = null) {}
	static function rgb2hsl(int $r, int $g, int $b, int $a = null) {}
	static function rgb2raw(int $r, int $g, int $b, int $a = null) {}
}