<?php
namespace Bang;

class Exception extends \Exception {
	function __construct(string $message = '', int $code = 0, \Throwable $previous = null) {
		parent::__construct($message,$code,$previous);
		if (Core::isWeb()) {
			echo '<pre class="exception">'.
				$this->getCode().': '.$this->getMessage().
				'</pre>'.PHP_EOL;
			return;
		}
		if (Core::isAPI()) {
			echo json_encode((object)
				[
					'errno' => $this->getCode(),
					'error' => $this->getMessage(),
				]
			);
			return;
		}
		echo '\Bang\Exception: '.$this->getCode().': '.$this->getMessage().PHP_EOL;
	}
}