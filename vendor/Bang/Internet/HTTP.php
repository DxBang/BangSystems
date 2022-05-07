<?php
namespace Bang\Internet;

class HTTP {
	protected static
		$url,
		$error,
		$data,
		$response,
		$info,
		$cookie,
		$options = [],
		$download,
		$upload,
		$curl,
		$userAgent,
		$encoding,
		$postAs = 0,
		$throw = 0;
	const
		THROW_NONE = 0,
		THROW_ERROR = 1,
		THROW_EXCEPTION = 2,
		THROW_ALL = 3,
		POST_AS_FORM = 0,
		POST_AS_JSON = 1,
		POST_AS_STRING = 2;

	function __construct() {
		$this->init();
		$this->defaults();
	}
	/* curl handles */
	private function init() {
		if (is_null(self::$curl))
			self::$curl = curl_init();
	}
	function close():object {
		if (!is_null(self::$curl)) {
			curl_close(self::$curl);
			self::$curl = null;
		}
		return $this;
	}
	function reset():object {
		return $this
			->close()
			->init()
			->defaults();
	}
	function clean():object {
		self::$error = null;
		self::$data = '';
		self::$cookie = (object) [];
		self::$response = [];
		self::$download = null;
		self::$upload = [];
		foreach ([
			CURLOPT_POST,
			CURLOPT_POSTFIELDS
		] as $v) {
			if (isset(self::$options[$v])) {
				unset(self::$options[$v]);
			}
		}
		foreach ([
			CURLOPT_HTTPHEADER,
		] as $v) {
			if (isset(self::$options[$v])) {
				self::$options[$v] = [];
			}
		}
		return $this;
	}
	function defaults():object {
		$this->clean();
		foreach ([
			CURLOPT_URL => null,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_AUTOREFERER => true,
			CURLOPT_COOKIESESSION => true,
			CURLOPT_DNS_CACHE_TIMEOUT => 30,
			CURLOPT_CONNECTTIMEOUT => 30,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_USERAGENT => self::getUserAgent(),
			CURLOPT_ENCODING => self::getEncoding(),
			CURLOPT_HTTPHEADER => [],
			CURLOPT_HEADER => false,
			CURLOPT_SSL_VERIFYPEER => 1,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_COOKIESESSION => true,
			CURLOPT_FORBID_REUSE => false,
			CURLOPT_CAINFO => '/etc/ssl/certs/ca-certificates.crt',
			CURLOPT_CAPATH => '/etc/ssl/certs',
			CURLOPT_FAILONERROR => false,
			CURLOPT_HEADERFUNCTION => [$this, '_headerFunction'],
			CURLINFO_HEADER_OUT => true,
		] as $k => $v) {
			self::$options[$k] = $v;
		}
		return $this;
	}
	private static function _headerFunction($ch, $res) {
		$head = trim($res);
		if (strlen($head)) {
			self::$response[] = $head;
		}
		return strlen($res);
	}
	function option(int $curl_constant, mixed $value = null):object {
		self::$options[$curl_constant] = $value;
		return $this;
	}
	private function _execute():object {
		if (self::hasDownload()) {
			self::$options[CURLOPT_FILE] = fopen(self::$download, 'w');
		}
		if (self::hasPostfields()) {
			switch (self::$postAs) {
				case self::POST_AS_FORM:
					$this->header('Content-Type', 'multipart/form-data');
				break;
				case self::POST_AS_JSON:
					$this->header('Accept', 'application/json');
					$this->header('Content-Type', 'application/json', true);
					#$this->header('Content-Type', 'multipart/mixed');
					self::$options[CURLOPT_POSTFIELDS] = json_encode(self::$options[CURLOPT_POSTFIELDS], JSON_UNESCAPED_SLASHES);
				break;
				case self::POST_AS_STRING:
					$this->header('Content-Type', 'application/x-www-form-urlencoded');
					self::$options[CURLOPT_POSTFIELDS] = self::queryString(self::$options[CURLOPT_POSTFIELDS], prefix: '');
				break;
			}
			/*
			curl -i -X POST
				-H "Content-Type: multipart/mixed"
				-F "blob=@/Users/username/Documents/bio.jpg"
				-F "metadata={\"edipi\":123456789,\"firstName\":\"John\",\"lastName\":\"Smith\",\"email\":\"john.smith@gmail.com\"};type=application/json"
				http://localhost:8080/api/v1/user/
			*/
		}
		if (self::hasUpload()) {
			if (self::hasPostfields()) {
				foreach (self::$upload as $name => $upload) {
					self::$options[CURLOPT_POSTFIELDS][$name] = $upload;
				}
			}
			else {
				self::$options[CURLOPT_POSTFIELDS] = self::$upload;
			}
		}
		if (self::hasCookie()) {
			#self::$options[CURLOPT_COOKIE] = implode(';', self::$options[CURLOPT_COOKIE]);
		}
		curl_setopt_array(
			self::$curl,
			self::$options
		);
		self::$data = curl_exec(self::$curl);
		self::$info = (object) curl_getinfo(self::$curl);
		if ($errno = curl_errno(self::$curl)) {
			self::$error = (object) [
				'code' => $errno,
				'message' => curl_error(self::$curl),
			];
			if (self::doThrowOn(self::THROW_ERROR)) {
				throw new \Exception(self::$error->message, self::$error->code);
			}
		}
		if (self::hasDownload()) {
			if (fclose(self::$options[CURLOPT_FILE])) {
				self::$options[CURLOPT_FILE] = &self::$download;
			}
		}
		return $this;
	}
	/* feature handles */
	
	function verify():object {
		return $this;
	}
	function follow():object {
		self::$options[CURLOPT_FOLLOWLOCATION] = true;
		return $this;
	}
	function noFollow():object {
		self::$options[CURLOPT_FOLLOWLOCATION] = false;
		return $this;
	}
	function download(string $download, bool $binary = true):object {
		if (self::doThrowOn(self::THROW_ERROR)) {
			$path = pathinfo($download, PATHINFO_DIRNAME);
			if (!file_exists($path)) throw new \Error('missing download directory: '.$path, 404);
			if (!is_dir($path)) throw new \Error('download destination is not a directory: '.$path, 406);
			if (!is_writable($path)) throw new \Error('download directory is not writable: '.$path, 403);
		}
		self::$download = $download;
		return $this;
	}
	function upload(string $upload, string $name = 'image', string $filename = null, string $contentType = null):object {
		if (self::doThrowOn(self::THROW_ERROR)) {
			if (!file_exists($upload)) throw new \Error('missing upload file: '.$upload, 404);
			if (!is_readable($upload)) throw new \Error('cannot read file: '.$upload, 403);
		}
		if (empty($filename)) {
			$filename = pathinfo($upload, PATHINFO_BASENAME);
		}
		if (empty($contentType)) {
			$contentType = mime_content_type($upload);
		}
		self::$upload[$name] = new \CURLFile(
			$upload,
			$contentType,
			$filename,
		);
		return $this;
	}
	function cert():object {
		self::$options[CURLOPT_CERTINFO] = true;
		self::$options[CURLOPT_SSL_VERIFYPEER] = true;
		self::$options[CURLOPT_SSL_VERIFYHOST] = 2;
		self::$options[CURLOPT_SSL_VERIFYSTATUS] = true;
		return $this;
	}
	function noCert():object {
		self::$options[CURLOPT_CERTINFO] = false;
		self::$options[CURLOPT_SSL_VERIFYPEER] = false;
		self::$options[CURLOPT_SSL_VERIFYHOST] = 0;
		self::$options[CURLOPT_SSL_VERIFYSTATUS] = false;
		return $this;
	}
	function userAgent(string $userAgent):object {
		self::$userAgent = $userAgent;
		return $this;
	}
	function encoding(string $encoding):object {
		self::$encoding = $encoding;
		return $this;
	}
	static function getUserAgent():string {
		if (!empty(self::$userAgent)) return self::$userAgent;
		self::$userAgent = !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Mozilla/5.0 Bang/4.0';
		return self::$userAgent;
	}
	static function getEncoding():string {
		if (self::$encoding) return self::$encoding;
		self::$encoding = !empty($_SERVER['HTTP_ENCODING']) ? $_SERVER['HTTP_ENCODING'] : 'deflate, gzip';
		return self::$encoding;
	}

	/* checks */
	static function error() {
		return self::$error;
	}
	static function isOK():bool {
		if (self::doThrowOn(self::THROW_ERROR)) {
			if (!is_null(self::$error)) throw new \Error(self::$error->message, self::$error->code);
		}
		if (self::doThrowOn(self::THROW_EXCEPTION)) {
			if (!is_null(self::$error)) throw new \Exception(self::$error->message, self::$error->code);
		}
		return true;
	}
	static function isClientError():bool {
		return false;
	}
	static function isServerError():bool {
		return false;
	}
	static function isSecured():bool {
		return strtolower(self::$info->scheme) == 'https';
	}
	static function hasCookie():bool {
		return !empty(self::$cookie);
	}
	static function isDownloaded():bool {
		return false;
	}
	static function hasDownload():bool {
		return !empty(self::$download);
	}
	static function hasUpload():bool {
		return !empty(self::$upload);
	}
	static function hasPostfields():bool {
		return !empty(self::$options[CURLOPT_POSTFIELDS]);
	}
	static function hasData():bool {
		return false;
	}

	/* extract info */
	static function contentType(bool $asObject = true):mixed {
		$r = $asObject ? (object) [
			'mime' => null
		] : null;
		if (empty(self::$info->content_type)) {
			return $r;
		}
		$e = array_filter(preg_split('/\s?;\s?/', self::$info->content_type, 2));
		if (!$asObject) {
			return trim($e[0]);
		}
		$r->mime = trim($e[0]);
		list($r->type, $r->format) = explode('/', $e[0], 2);
		if (count($e) == 2) {
			$a = preg_split('/\s?;\s?/', trim($e[1]));
			foreach ($a as $v) {
				$v = preg_split('/\s?=\s?/', $v);
				if (!empty($v))
					$r->{strtolower($v[0])} = trim(strtolower($v[1])) ?? null;
			}
		}
		return $r;
	}

	/* error/exception */
	function throwOn(int $throw):object {
		#self::$throw |= $throw;
		self::$throw = $throw;
		return $this;
	}
	private static function doThrowOn(int $throw):bool {
		if ($throw)
			return ((self::$throw & $throw) === $throw);
		return (self::$throw === $throw);
	}

	/* response handles */
	static function response():array {
		return self::$response;
	}
	static function data():string {
		if (self::hasDownload()) {
			return file_get_contents(self::$download);
		}
		return self::$data ?? '';
	}
	static function json(bool $throw = true) {
		if ($throw)
			return json_decode(
				self::data(),
				null,
				512,
				JSON_THROW_ON_ERROR,
			);
		return json_decode(
			self::data(),
		);
	}
	static function dom():object {
		return new DOM(self::data());
	}

	/* request handles */
	function postAs(int $postAs):object {
		self::$postAs = $postAs;
		return $this;
	}
	private static function doPostAs(int $postAs):bool {
		if ($postAs)
			return ((self::$postAs & $postAs) === $postAs);
		return (self::$postAs === $postAs);
	}
	function header($header, string $value = null, bool $overwrite = false):object {
		if (is_array($header)) {
			foreach ($header as $k => $v) {
				if (is_int($k)) {
					$this->header(
						$v,
						null,
						$overwrite
					);
					continue;
				}
				$this->header(
					$k,
					$v,
					$overwrite
				);
			}
			return $this;
		}
		if (empty($value) && preg_match('/^([\w\-]+)[:|=][\s]{0,}(.*)$/', $header, $m)) {
			return $this->header($m[1], $m[2], $overwrite);
		}
		if ($overwrite) {
			foreach (self::$options[CURLOPT_HTTPHEADER] as $k => $v) {
				if (preg_match('/^'.preg_quote(strtolower($header)).':/', $v)) {
					self::$options[CURLOPT_HTTPHEADER][$k] = "{$header}: {$value}";
					$overwrite = false;
				}
			}
			if ($overwrite) {
				self::$options[CURLOPT_HTTPHEADER][] = "{$header}: {$value}";
			}
			return $this;
		}
		self::$options[CURLOPT_HTTPHEADER][] = "{$header}: {$value}";
		return $this;
	}
	function cookie(string $cookie, string $value = null):object {
		if (is_array($cookie)) {
			foreach ($cookie as $k => $v) {
				$this->cookie(
					$v
				);
			}
			return $this;
		}
		if (empty($value) && preg_match('/^([\w\-]+)[:|=][\s]{0,}(.*)$/', $cookie, $m)) {
			return $this->cookie($m[1], $m[2]);
		}
		self::$cookie->{$cookie} = $value;
		return $this;
	}
	function cookieFile(string $file, string $jar = null):object {
		if (empty($jar)) {
			self::$options[CURLOPT_COOKIEJAR]
				= self::$options[CURLOPT_COOKIEFILE]
				= $file;
			return $this;
		}
		self::$options[CURLOPT_COOKIEFILE] = $file;
		self::$options[CURLOPT_COOKIEJAR] = $jar;
		return $this;
	}
	function url(string $url):object {
		self::$url = new URL($url);
		return $this;
	}
	function referer(string $url):object {
		return $this->refer($url);
	}
	function refer(string $url):object {
		self::$options[CURLOPT_REFERER] = $url;
		return $this;
	}
	function head(string $url):object {
		self::$options[CURLOPT_NOBODY] = 1;
		return $this;
	}
	function touch(string $url):object {
		self::$options[CURLOPT_CUSTOMREQUEST] = 'GET';
		self::$options[CURLOPT_NOBODY] = 1;
		return $this;
	}
	function get(string $url, array|object $get = null):object {
		self::$options[CURLOPT_URL] = self::_makeUrl($url, $get);
		self::$options[CURLOPT_POST] = false;
		return $this->_execute();
	}
	function post(string $url, array|object $post = null, int $postAs = null):object {
		self::$options[CURLOPT_POST] = true;
		self::$options[CURLOPT_POSTFIELDS] = (array) $post;
		self::$options[CURLOPT_URL] = $url;
		if (!is_null($postAs)) {
			$this->postAs($postAs);
		}
		return $this->_execute();
	}
	function go(string $url, array|object $get = null, array|object $post = null, int $postAs = null):object {
		self::$options[CURLOPT_URL] = self::_makeUrl($url, $get);
		if ($post) {
			self::$options[CURLOPT_POST] = true;
			self::$options[CURLOPT_POSTFIELDS] = (array) $post;
			if (!is_null($postAs)) {
				$this->postAs($postAs);
			}
		}
		return $this->_execute();
	}
	private static function _makeUrl(string $url, array|object $get = null):string {
		$u = explode('?', explode('#', $url)[0]);
		$r = $u[0];
		if (!empty($u[1])) {
			parse_str($u[1], $p);
			$get = array_merge(
				$p,
				$get ?? []
			);
		}
		if ($get) {
			$r .= self::queryString($get);
		}
		return $r;
	}

	static function queryString(array|object $query, string $prefix = '?'):string {
		if (empty($query)) return '';
		$r = $prefix;
		$i = 0;
		foreach ($query as $k => $v) {
			if ($i) $r .= '&';
			switch (strtolower(gettype($v))) {
				case 'null':
					$r .= urlencode($k);
				break;
				case 'array':
				case 'object':
					$ai = 0;
					foreach ($v as $ak => $av) {
						if ($ai) $r .= '&';
						$r .= urlencode($k.'['.$ak.']').'='.urlencode($av);
						$ai++;
					}
				break;
				default:
					$r .= urlencode($k).'='.urlencode($v);
			}
			$i++;
		}
		return $r;
	}

	/* debug & info */
	static function info() {
		return self::$info;
	}
	static function options() {
		$a = [
			CURLINFO_HEADER_OUT => 'Output Headers',
			CURLOPT_CAINFO => 'CA Info File',
			CURLOPT_CAPATH => 'CA Path',
			CURLOPT_CERTINFO => 'Cert Information',
			CURLOPT_CONNECTTIMEOUT => 'Connection Timeout',
			CURLOPT_COOKIE => 'Cookie',
			CURLOPT_COOKIEFILE => 'Cookie File',
			CURLOPT_COOKIEJAR => 'Cookie Jar',
			CURLOPT_COOKIESESSION => 'Cookie Session',
			CURLOPT_CUSTOMREQUEST => 'Custom Request',
			CURLOPT_DNS_CACHE_TIMEOUT => 'DNS Cache Timeout',
			CURLOPT_ENCODING => 'Encoding',
			CURLOPT_FILE => 'Download File',
			CURLOPT_FOLLOWLOCATION => 'Follow Location',
			CURLOPT_HEADER => 'Send Headers',
			CURLOPT_HTTPAUTH => 'HTTP Auth',
			CURLOPT_HTTPHEADER => 'HTTP Headers',
			CURLOPT_INFILE => 'Upload File',
			CURLOPT_INFILESIZE => 'Upload Filesize',
			CURLOPT_NOBODY => 'No Body',
			CURLOPT_POST => 'Request as Post',
			CURLOPT_POSTFIELDS => 'Post Fields',
			CURLOPT_PROXY => 'Proxy',
			CURLOPT_PROXYAUTH => 'Proxy Auth',
			CURLOPT_PROXYTYPE => 'Proxy Type',
			CURLOPT_PUT => 'Request as Put',
			CURLOPT_REFERER => 'Referer URL',
			CURLOPT_RETURNTRANSFER => 'Return Transfer',
			CURLOPT_SSL_VERIFYHOST => 'SSL Verify Host',
			CURLOPT_SSL_VERIFYPEER => 'SSL Verify Peer',
			CURLOPT_SSL_VERIFYSTATUS => 'SSL Verify Status',
			CURLOPT_TIMEOUT => 'Timeout',
			CURLOPT_UPLOAD => 'Upload',
			CURLOPT_URL => 'Request URL',
			CURLOPT_USERAGENT => 'User-Agent',
			CURLOPT_USERPWD => 'User & Password',
			CURLOPT_VERBOSE => 'Verbose',
			CURLOPT_MAXREDIRS => 'Max Redirects',
			CURLOPT_HEADERFUNCTION => 'Receive Headers',
			CURLOPT_WRITEFUNCTION => 'Receive Data Function',
			CURLOPT_READFUNCTION => 'Send Data Function',
			CURLOPT_FORBID_REUSE => 'Forbid Reuse',
			CURLOPT_AUTOREFERER => 'Auto Referer',
			CURLOPT_FAILONERROR => 'Fail On Error',
		];
		$f = [
			CURLOPT_HEADERFUNCTION,
			CURLOPT_WRITEFUNCTION,
			CURLOPT_READFUNCTION,
		];
		$r = [];
		foreach (self::$options as $k => $v) {
			if (in_array($k, $f)) {
				$r[$a[$k] ?? $k] = 'function()';
				continue;
			}
			$r[$a[$k] ?? $k] = $v;
		}
		return $r;
	}
	function debug():object {
		/*
		echo json_encode(
			$this->__debugInfo()
		);
		*/
		return $this;
	}
	function __debugInfo() {
		return (array) [
			'url' => self::$info->url ?? '',
			'postAs' => (object) [
				'form' => self::doPostAs(self::POST_AS_FORM),
				'json' => self::doPostAs(self::POST_AS_JSON),
				'string' => self::doPostAs(self::POST_AS_STRING),
			],
			'info' => self::$info,
			'response' => self::$response,
			'data' => self::data(),
			'json' => self::json(false),
			'download' => self::$download,
			'upload' => self::$upload,
			'options' => self::options(),
		];
	}
}