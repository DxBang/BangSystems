# BangSystems
Bang.Systems PHP Framework 
**built with security in mind** 
_Version 4.y.z_ 


## getting started
public/index.php
```php
<?php
$_ENV['REQUEST_TIME_FLOAT'] = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(1); # benchmark
define('ROOT', dirname(__DIR__));
define('PUBLIC', ROOT.'/public');
define('PRIVATE', ROOT.'/private');
define('VENDOR', PRIVATE.'/vendor');
define('UI', PUBLIC.'/ui');
define('WEB_UI', '/ui');

require_once '/srv/src/bang/v4/bang.php';
switch (\Bang\Core::URI(0)) {
	case 'api':
		$bang = new Bang\API();
	break;
	case 'image':
		$bang = new Bang\ImageAPI();
	break;
	case 'video':
		$bang = new Bang\VideoAPI();
	break;
	default
		$bang = new Bang\Website();
	break;
}
```
