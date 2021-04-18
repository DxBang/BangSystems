<?php
namespace Bang\Tool;

class Generate {
	static function key(int $length = 16):string {
		$c = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
		$l = strlen($c) - 1;
		$r = '';
		for ($i = 0; $i < $length; $i++) {
			$r .= $c[rand(0, $l)];
		}
		return $r;
	}
}

