# BangSystems
Bang.Systems PHP8 Framework  
**built with security in mind**  
_Version 4.y.z_  


## getting started
public/index.php
```php
<?php
$_SERVER['REQUEST_TIME_FLOAT'] = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(1); # benchmark
define('SITE_ROOT', dirname(__DIR__));
define('SITE_PUBLIC', SITE_ROOT.'/public');
define('SITE_PRIVATE', SITE_ROOT.'/private');
define('SITE_VENDOR', SITE_PRIVATE.'/vendor');
define('SITE_UI', SITE_PUBLIC.'/ui');
define('WEB_UI', '/ui');
define('BANG_DEBUG_MARKS', true);

require_once '/srv/src/bang/v4/bang.php';
switch (\Bang\Bang::path(0)) {
	case 'api':
		new \Bang\System\API();
	break;
	case 'image':
		new \Bang\System\Image();
	break;
	case 'video':
		new \Bang\System\Video();
	break;
	default:
		new \Bang\System\Website();
	break;
}
```
