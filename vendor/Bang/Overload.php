<?php
namespace Bang;

abstract class Overload {
	private $data = [];
	function __set(string $k, $v) {
		return $this->set($k, $v);
	}
	function __get(string $k) {
		return $this->get($k);
	}
	function __isset(string $k) {
		return $this->isset($k);
	}
	function __unset(string $k) {
		return $this->unset($k);
	}
	function set(string $k, $v) {
		return $this->data[$k] = $v;
	}
	function get(string $k) {
		return $this->isset($k) ? $this->data[$k] : null;
	}
	function isset(string $k) {
		return isset($this->data[$k]);
	}
	function unset(string $k) {
		unset($this->data[$k]);
	}
}
