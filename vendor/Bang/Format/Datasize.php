<?php
namespace Bang\Format;

class Datasize {
    static function human(int $bytes):string {
		if ($bytes <= 0) return '0 b';
		$units = ['b', 'kb', 'mb', 'gb', 'tb', 'pb', 'eb', 'zb', 'yb'];
		return @round($bytes / pow(1024, ($i = floor(log($bytes, 1024)))), 2).' '.$units[$i];
	}
}