<?php
namespace Bang;

class Error extends \Error {
	function __construct($e) {
		if (\Bang\Bang::isWeb()) {
			echo '<pre class="error">';
			print_r($e);
			echo '</pre>';
			return;
		}
		if (\Bang\Bang::isAPI()) {
			
		}
		if (\Bang\Bang::isCLI()) {
			
		}
	}
}