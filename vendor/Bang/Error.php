<?php
namespace Bang;

class Error extends \Error {
	function __construct(string $message = '', int $code = 0, \Throwable $previous = null) {
		parent::__construct($message,$code,$previous);
		if (\Bang\Bang::isWeb()) {
			echo '<pre class="error">'.
				$this->getCode().': '.$this->getMessage().
				'</pre>'.PHP_EOL;
			exit;
		}
		if (\Bang\Bang::isAPI()) {
			echo json_encode((object)
				[
					'errno' => $this->getCode(),
					'error' => $this->getMessage(),
				]
			);
			exit;
		}
		echo '\Bang\Error: '.$this->getCode().': '.$this->getMessage().PHP_EOL;
		exit;
	}
}