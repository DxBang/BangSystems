<?php
namespace Bang\Math;

class Ratio {
	

	static function calculate(int $width, int $height) {
		if (empty($width)) throw new \Exception('width cannot be 0 or less');
		if (empty($height)) throw new \Exception('height cannot be 0 or less');
	}
}