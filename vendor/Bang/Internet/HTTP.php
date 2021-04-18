<?php
namespace Bang\Internet;

class HTTP {
	protected static
		$method,
		$send,
		$data,
		$response,
		$info,
		$cookie,
		$options,
		$download,
		$upload,
		$curl;

	function __construct() {
		$this->init();
		$this->detaults();
	}
	/* curl handles */
	private function init() {
		self::$curl = curl_init();
	}
	function detaults():object {
		self::$send = (object) [
			'primary' => null,
			'secondary' => null,
		];
		self::$data = '';
		self::$cookie = (object) [];
		self::$response = [];
		self::$download = null;
		self::$upload = [];
		self::$options = [
			CURLOPT_URL => null,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_AUTOREFERER => true,
			CURLOPT_COOKIESESSION => true,
			CURLOPT_DNS_CACHE_TIMEOUT => 30,
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_TIMEOUT => 10,
			CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'] ?? 'Mozilla/5.0 Bang/4.0',
			CURLOPT_ENCODING => $_SERVER['HTTP_ENCODING'] ?? 'deflate, gzip',
			CURLOPT_HTTPHEADER => [],
			CURLOPT_HEADER => false,
			CURLOPT_SSL_VERIFYPEER => 1,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_COOKIESESSION => true,
			CURLOPT_FORBID_REUSE => false,
			CURLINFO_HEADER_OUT => true,
			CURLOPT_CAINFO => '/etc/ssl/certs/ca-certificates.crt',
			CURLOPT_CAPATH => '/etc/ssl/certs',
			CURLOPT_FAILONERROR => false,
			CURLOPT_HEADERFUNCTION => function ($ch, $res) {
				$head = trim($res);
				if (strlen($head)) {
					self::$response[] = $head;
				}
				return strlen($res);
			},
			/*
			CURLOPT_WRITEFUNCTION => function ($ch, string $data) {
				echo 'CURLOPT_WRITEFUNCTION: '.strlen($data).PHP_EOL;
				print_r($ch);
				
				if (self::hasDownload()) {
					file_put_contents(self::$download, $data, FILE_APPEND);
				}
				self::$data .= $data;
				return strlen($data);
			},
			*/
			/*
			CURLOPT_READFUNCTION => function ($ch, $fp, int $len) {
				return fgets($fp, $len);
			},*/
		];
		return $this;
	}
	function option(int $curl_constant, mixed $value = null):object {
		self::$options[$curl_constant] = $value;
		return $this;
	}
	function close():object {
		curl_close(self::$curl);
		return $this;
	}
	function reset():object {
		curl_reset(self::$curl);
		#self::$options[CURLOPT_URL] = null;
		#self::$options[CURLOPT_REFERER] = null;
		#self::$options[CURLOPT_URL] = null;
		return $this;
	}
	function hardReset():object {
		curl_close(self::$curl);
		$this->init();
		$this->detaults();
		return $this;
	}
	private function _execute():object {
		if (self::$curl) {
			$this->close();
		}
		$this->init();
		if (self::hasDownload()) {
			self::$options[CURLOPT_FILE] = fopen(self::$download, 'w');
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
		curl_exec(self::$curl);
		self::$info = (object) curl_getinfo(self::$curl);

		curl_close(self::$curl);
		if (self::hasDownload()) {
			if (fclose(self::$options[CURLOPT_FILE])) {
				self::$options[CURLOPT_FILE] = &self::$download;
			}
		}
		return $this;
	}
	private function clean():object {
		
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
		self::$download = $download;
		return $this;
	}
	function upload(string $upload, string $name = 'image', string $filename = null, string $contentType = null):object {
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

	/* checks */
	static function isOK():bool {
		return false;
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
	function throwOnError():object {
		if (empty(self::$options[CURLOPT_URL])) throw new \Error('missing url');
		if (self::hasDownload()) {
			if (!file_exists(self::$download)) throw new \Error('cannot find downloaded file...', 400);
			if (!is_readable(self::$download)) throw new \Error('cannot read downloaded file...', 400);
		}
		return $this;
	}
	function throwOnException():object {
		
		return $this;
	}

	/* response handles */
	static function data():string {
		if (self::hasDownload()) {
			return file_get_contents(self::$download);
		}
		return self::$data ?? '';
	}
	static function json(bool $throw = true) {
		if ($throw)
			return json_decode(
				json: self::data(),
				flags: JSON_THROW_ON_ERROR,
			);
		return json_decode(
			json: self::data(),
		);
	}
	static function dom():object {
		return new DOM(self::data());
	}

	/* request handles */
	function header(string|array $header, string $value = null, bool $append = true):object {
		if (is_array($header)) {
			foreach ($header as $k => $v) {
				$this->header(
					$v,
					append: $k ? (bool) $k : $append
				);
			}
			return $this;
		}
		if (empty($value) && preg_match('/^([\w\-]+)[:|=][\s]{0,}(.*)$/', $header, $m)) {
			return $this->header($m[1], $m[2], $append);
		}
		if ($append) {
			self::$options[CURLOPT_HTTPHEADER][] = "{$header}: {$value}";
			return $this;
		}
		self::$options[CURLOPT_HTTPHEADER] = ["{$header}: {$value}"];
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
		self::$method = 'GET';
		self::$options[CURLOPT_URL] = $url;
		self::$send->primary = $get;
		return $this->_execute();
	}
	function post(string $url, array|object $post = null):object {
		self::$method = 'POST';
		self::$options[CURLOPT_POST] = 1;
		self::$options[CURLOPT_POSTFIELDS] = (array) $post;
		self::$options[CURLOPT_URL] = $url;
		self::$send->secondary = $post;
		return $this->_execute();
	}
	function go(string $url, array|object $get = null, array|object $post = null):object {
		self::$method = 'GET';
		self::$options[CURLOPT_URL] = $url;
		if ($get) {
			self::$send->primary = $get;
		}
		if ($post) {
			self::$method = 'POST';
			self::$options[CURLOPT_POST] = 1;
			self::$send->secondary = $post;
		}
		return $this->_execute();
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
			CURLOPT_WRITEFUNCTION => 'Receive Data Function',
			CURLOPT_READFUNCTION => 'Send Data Function',
			CURLOPT_FORBID_REUSE => 'Forbid Reuse',
			CURLOPT_AUTOREFERER => 'Auto Referer',
			CURLOPT_FAILONERROR => 'Fail On Error',
		];
		$r = [];
		foreach (self::$options as $k => $v) {
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
			'method' => self::$method,
			'url' => self::$info->url,
			'get' => self::$send->primary,
			'post' => self::$send->secondary,
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