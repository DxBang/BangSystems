<?php
namespace Bang;

class Error extends \Error {
	function __construct($e) {
		if (\Bang\Core::isWeb()) {
			echo '<pre class="error">';
			print_r($e);
			echo '</pre>';
			return;
		}
		if (\Bang\Core::isAPI()) {
			
		}
		if (\Bang\Core::isCLI()) {
			
		}
	}
}