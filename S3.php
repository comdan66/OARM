<?php

final class S3Request {
  private $verb;
  private $bucket;
  private $uri;
  private $resource = '';

  private $parameters = [];
  private $amzHeaders = [];
  private $headers    = ['Host' => '', 'Date' => '', 'Content-MD5' => '', 'Content-Type' => ''];

  public $fp   = null;
  public $data = null;
  public $size = 0;

  public $response = null;

  public function __construct($verb, $bucket = '', $uri = '', $defaultHost = 's3.amazonaws.com') {
    $this->verb = strtoupper($verb);
    $this->bucket = strtolower($bucket);

    $this->uri = $uri ? '/' . str_replace('%2F', '/', rawurlencode($uri)) : '/';
    $this->resource = ($this->bucket ? '/' . $this->bucket : '') . $this->uri;
    
    $this->headers['Host'] = ($this->bucket ? $this->bucket . '.' : '') . $defaultHost;
    $this->headers['Date'] = gmdate('D, d M Y H:i:s T');

    $this->response = new STDClass;
    $this->response->error = null;
    $this->response->body = '';
    $this->response->code = null;
  }

  public function setParameter($key, $value) { $value && $this->parameters[$key] = $value; return $this; }
  public function setHeaders($arr) { foreach ($arr as $key => $value) $this->setHeader($key, $value); return $this; }
  public function setHeader($key, $value) { $value && $this->headers[$key] = $value; return $this; }
  public function setAmzHeaders($arr) { foreach ($arr as $key => $value) $this->setAmzHeader($key, $value); return $this; }
  public function setAmzHeader($key, $value) { $value && $this->amzHeaders[preg_match('/^x-amz-.*$/', $key) ? $key : 'x-amz-meta-' . $key] = $value; return $this; }
  public function setData($data) { $this->data = $data; $this->setSize(strlen($data)); return $this; }
  public function setFile($file, $mode = 'rb', $autoSetSize = true) { $this->fp = @fopen($file, $mode); $autoSetSize && $this->setSize(filesize($file)); return $this; }
  public function setSize($size) { $this->size = $size; return $this; }

  public function getSize() { return $this->size; }
  public function getFile() { return $this->fp; }

  private function makeAmz() {
    $amz = [];

    foreach ($this->amzHeaders as $header => $value)
      $value && array_push($amz, strtolower($header) . ':' . $value);

    if (!$amz)
      return '';

    sort($amz);

    return "\n" . implode("\n", $amz);
  }
  
  private function makeHeader() {
    $headers = [];

    foreach ($this->amzHeaders as $header => $value)
      $value && array_push($headers, $header . ': ' . $value);
    
    foreach ($this->headers as $header => $value)
      $value && array_push($headers, $header . ': ' . $value);
    
    array_push($headers, 'Authorization: ' . S3::getSignature($this->headers['Host'] == 'cloudfront.amazonaws.com' ? $this->headers['Date'] : $this->verb . "\n" . $this->headers['Content-MD5'] . "\n" . $this->headers['Content-Type'] . "\n" . $this->headers['Date'] . $this->makeAmz() . "\n" . $this->resource));

    return $headers;
  }

  public function getResponse() {
    $query = '';

    if ($this->parameters) {
      $query = substr($this->uri, -1) !== '?' ? '?' : '&';

      foreach ($this->parameters as $var => $value)
        $query .= ($value == null) || ($value == '') ? $var . '&' : $var . '=' . rawurlencode($value) . '&';

      $this->uri .= $query = substr($query, 0, -1);

      if (isset($this->parameters['acl']) || isset($this->parameters['location']) || isset($this->parameters['torrent']) || isset($this->parameters['logging']))
        $this->resource .= $query;
    }

    $url = (S3::$isUseSsl && extension_loaded('openssl') ? 'https://' : 'http://') . $this->headers['Host'] . $this->uri;

    $curlSetopts = [
      CURLOPT_URL => $url,
      CURLOPT_USERAGENT => 'S3/php',
      CURLOPT_HTTPHEADER => $this->makeHeader(),
      CURLOPT_HEADER => false,
      CURLOPT_RETURNTRANSFER => false,
      CURLOPT_WRITEFUNCTION => [&$this, 'responseWriteCallback'],
      CURLOPT_HEADERFUNCTION => [&$this, 'responseHeaderCallback'],
      CURLOPT_FOLLOWLOCATION => true,
    ];

    S3::$isUseSsl && $curlSetopts[CURLOPT_SSL_VERIFYHOST] = 1;
    S3::$isUseSsl && $curlSetopts[CURLOPT_SSL_VERIFYPEER] = S3::$isVerifyPeer ? 1 : FALSE;

    switch ($this->verb) {
      case 'PUT': case 'POST':
        if ($this->fp !== null) {
          $curlSetopts[CURLOPT_PUT] = true;
          $curlSetopts[CURLOPT_INFILE] = $this->fp;
          $this->size && $curlSetopts[CURLOPT_INFILESIZE] = $this->size;
          break;
        }

        $curlSetopts[CURLOPT_CUSTOMREQUEST] = $this->verb;

        if ($this->data !== null) {
          $curlSetopts[CURLOPT_POSTFIELDS] = $this->data;
          $this->size && $curlSetopts[CURLOPT_BUFFERSIZE] = $this->size;
        }
        break;

      case 'HEAD':
        $curlSetopts[CURLOPT_CUSTOMREQUEST] = 'HEAD';
        $curlSetopts[CURLOPT_NOBODY] = true;
        break;

      case 'DELETE':
        $curlSetopts[CURLOPT_CUSTOMREQUEST] = 'DELETE';
        break;

      case 'GET': default: break;
    }
    
    $curl = curl_init();
    curl_setopt_array($curl, $curlSetopts);
    
    if (curl_exec($curl))
      $this->response->code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    else
      $this->response->error = [
        'code' => curl_errno($curl),
        'message' => curl_error($curl),
        'resource' => $this->resource
      ];

    curl_close($curl);

    if ($this->response->error === null && isset($this->response->headers['type']) && $this->response->headers['type'] == 'application/xml' && isset($this->response->body) && ($this->response->body = simplexml_load_string($this->response->body)))
      if (!in_array($this->response->code, [200, 204]) && isset($this->response->body->Code, $this->response->body->Message))
        $this->response->error = [
          'code' => (string)$this->response->body->Code,
          'message' => (string)$this->response->body->Message,
          'resource' => isset($this->response->body->Resource) ? (string)$this->response->body->Resource : null
        ];

    $this->fp !== null && is_resource($this->fp) && fclose($this->fp);

    return $this->response;
  }

  private function responseWriteCallback(&$curl, &$data) {
    if ($this->response->code == 200 && $this->fp !== null)
      return fwrite($this->fp, $data);

    $this->response->body .= $data;
    return strlen($data);
  }

  private function responseHeaderCallback(&$curl, &$data) {
    if (($strlen = strlen($data)) <= 2)
      return $strlen;
    
    if (substr($data, 0, 4) == 'HTTP') {
      $this->response->code = (int)substr($data, 9, 3);
    } else {
      list($header, $value) = explode(': ', trim($data), 2);

      $header == 'Last-Modified'  && $this->response->headers['time'] = strtotime($value);
      $header == 'Content-Length' && $this->response->headers['size'] = (int)$value;
      $header == 'Content-Type'   && $this->response->headers['type'] = $value;
      $header == 'ETag'           && $this->response->headers['hash'] = $value{0} == '"' ? substr($value, 1, -1) : $value;
      preg_match('/^x-amz-meta-.*$/', $header) && $this->response->headers[$header] = is_numeric($value) ? (int)$value : $value;
    }

    return $strlen;
  }

  public static function create($verb, $bucket = '', $uri = '') {
    return new S3Request($verb, $bucket, $uri);
  }
}

class S3 {
  const ACL_PRIVATE            = 'private';
  const ACL_PUBLIC_READ        = 'public-read';
  const ACL_PUBLIC_READ_WRITE  = 'public-read-write';
  const ACL_AUTHENTICATED_READ = 'authenticated-read';

  private static $accessKey    = null;
  private static $secretKey    = null;
  private static $exts         = ['jpg' => ['image/jpeg', 'image/pjpeg'], 'gif' => 'image/gif', 'png' => ['image/png', 'image/x-png'], 'tif' => 'image/tiff', 'tiff' => 'image/tiff', 'ico' => 'image/x-icon', 'swf' => 'application/x-shockwave-flash', 'pdf' => ['application/pdf', 'application/x-download'], 'zip' => ['application/x-zip', 'application/zip', 'application/x-zip-compressed'], 'gz' => 'application/x-gzip', 'tar' => 'application/x-tar', 'bz' => 'application/x-bzip', 'bz2' => 'application/x-bzip2', 'txt' => 'text/plain', 'asc' => 'text/plain', 'htm' => 'text/html', 'html' => 'text/html', 'css' => 'text/css', 'js' => 'application/x-javascript', 'xml' => 'text/xml', 'xsl' => 'text/xml', 'ogg' => 'application/ogg', 'mp3' => ['audio/mpeg', 'audio/mpg', 'audio/mpeg3', 'audio/mp3'], 'wav' => ['audio/x-wav', 'audio/wave', 'audio/wav'], 'avi' => 'video/x-msvideo', 'mpg' => 'video/mpeg', 'mpeg' => 'video/mpeg', 'mov' => 'video/quicktime', 'flv' => 'video/x-flv', 'php' => 'application/x-httpd-php', 'hqx' => 'application/mac-binhex40', 'cpt' => 'application/mac-compactpro', 'csv' => ['text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel'], 'bin' => 'application/macbinary', 'dms' => 'application/octet-stream', 'lha' => 'application/octet-stream', 'lzh' => 'application/octet-stream', 'exe' => ['application/octet-stream', 'application/x-msdownload'], 'class' => 'application/octet-stream', 'psd' => 'application/x-photoshop', 'so' => 'application/octet-stream', 'sea' => 'application/octet-stream', 'dll' => 'application/octet-stream', 'oda' => 'application/oda', 'ai' => 'application/postscript', 'eps' => 'application/postscript', 'ps' => 'application/postscript', 'smi' => 'application/smil', 'smil' => 'application/smil', 'mif' => 'application/vnd.mif', 'xls' => ['application/excel', 'application/vnd.ms-excel', 'application/msexcel'], 'ppt' => ['application/powerpoint', 'application/vnd.ms-powerpoint'], 'wbxml' => 'application/wbxml', 'wmlc' => 'application/wmlc', 'dcr' => 'application/x-director', 'dir' => 'application/x-director', 'dxr' => 'application/x-director', 'dvi' => 'application/x-dvi', 'gtar' => 'application/x-gtar', 'php4' => 'application/x-httpd-php', 'php3' => 'application/x-httpd-php', 'phtml' => 'application/x-httpd-php', 'phps' => 'application/x-httpd-php-source', 'sit' => 'application/x-stuffit', 'tgz' => ['application/x-tar', 'application/x-gzip-compressed'], 'xhtml' => 'application/xhtml+xml', 'xht' => 'application/xhtml+xml', 'mid' => 'audio/midi', 'midi' => 'audio/midi', 'mpga' => 'audio/mpeg', 'mp2' => 'audio/mpeg', 'aif' => 'audio/x-aiff', 'aiff' => 'audio/x-aiff', 'aifc' => 'audio/x-aiff', 'ram' => 'audio/x-pn-realaudio', 'rm' => 'audio/x-pn-realaudio', 'rpm' => 'audio/x-pn-realaudio-plugin', 'ra' => 'audio/x-realaudio', 'rv' => 'video/vnd.rn-realvideo', 'bmp' => ['image/bmp', 'image/x-windows-bmp'], 'jpeg' => ['image/jpeg', 'image/pjpeg'], 'jpe' => ['image/jpeg', 'image/pjpeg'], 'shtml' => 'text/html', 'text' => 'text/plain', 'log' => ['text/plain', 'text/x-log'], 'rtx' => 'text/richtext', 'rtf' => 'text/rtf', 'mpe' => 'video/mpeg', 'qt' => 'video/quicktime', 'movie' => 'video/x-sgi-movie', 'doc' => 'application/msword', 'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'], 'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'], 'word' => ['application/msword', 'application/octet-stream'], 'xl' => 'application/excel', 'eml' => 'message/rfc822', 'json' => ['application/json', 'text/json'], 'svg' => 'image/svg+xml'];

  public static $isUseSsl      = false;
  public static $isVerifyPeer  = true;

  private static $logerFunc = null;

  public static function init($accessKey, $secretKey) {
    self::setAccessKey($accessKey);
    self::setSecretKey($secretKey);
  }

  public static function setLogerFunc($logerFunc) {
    is_callable($logerFunc) && self::$logerFunc = $logerFunc;
  }

  private static function log() {
    ($func = self::$logerFunc) && call_user_func_array($func, [implode('，', array_merge(['[S3]'], func_get_args()))]);
    return false;
  }

  public static function setAccessKey($accessKey) {
    is_string($accessKey) && self::$accessKey = trim($accessKey);
  }

  public static function setSecretKey($secretKey) {
    is_string($secretKey) && self::$secretKey = trim($secretKey);
  }

  public static function test() {
    return self::isSuccess(S3Request::create('GET')->getResponse());
  }

  private static function isSuccess($rest, $codes = [200]) {
    return $rest->error === null && in_array($rest->code, $codes);
  }

  public static function getSignature($string) {
    return 'AWS ' . self::$accessKey . ':' . self::getHash($string);
  }

  private static function getHash($string) {
    return base64_encode(extension_loaded('hash') ? hash_hmac('sha1', $string, self::$secretKey, true) : pack('H*', sha1((str_pad(self::$secretKey, 64, chr(0x00)) ^ (str_repeat(chr(0x5c), 64))) . pack('H*', sha1((str_pad(self::$secretKey, 64, chr(0x00)) ^ (str_repeat(chr(0x36), 64))) . $string)))));
  }

  public static function buckets() {
    if (!self::isSuccess($rest = S3Request::create('GET')->getResponse()))
      return self::log('buckets 異常的 HTTP 狀態', 'Code：' . $rest->code);

    $buckets = [];

    if (!isset($rest->body->Buckets))
      return $buckets;

    foreach ($rest->body->Buckets->Bucket as $bucket)
      array_push($buckets, (string)$bucket->Name);

    return $buckets;
  }

  public static function bucketsWithDetail() {
    if (!self::isSuccess($rest = S3Request::create('GET')->getResponse()))
      return self::log('bucketsWithDetail 異常的 HTTP 狀態', 'Code：' . $rest->code);

    $results = [];

    if (!isset($rest->body->Buckets))
      return $results;

    if (isset($rest->body->Owner, $rest->body->Owner->ID, $rest->body->Owner->DisplayName))
      $results['owner'] = ['id' => (string)$rest->body->Owner->ID, 'name' => (string)$rest->body->Owner->ID];

    $results['buckets'] = [];
    foreach ($rest->body->Buckets->Bucket as $bucket)
      array_push($results['buckets'], ['name' => (string)$bucket->Name, 'time' => date('Y-m-d H:i:s', strtotime((string)$bucket->CreationDate))]);

    return $results;
  }

  public static function bucket($bucket, $prefix = null, $marker = null, $maxKeys = null, $delimiter = null, $returnCommonPrefixes = false) {
    if (!self::isSuccess($rest = S3Request::create('GET', $bucket)->setParameter('prefix', $prefix)->setParameter('marker', $marker)->setParameter('max-keys', $maxKeys)->setParameter('delimiter', $delimiter)->getResponse()))
      return self::log('bucket 異常的 HTTP 狀態', 'Code：' . $rest->code, '參數：' . json_encode(['bucket' => $bucket, 'prefix' => $prefix, 'marker' => $marker, 'maxKeys' => $maxKeys, 'delimiter' => $delimiter, 'returnCommonPrefixes' => $returnCommonPrefixes]));

    $nextMarker = null;
    $results = [];

    if (isset($rest->body, $rest->body->Contents))
      foreach ($rest->body->Contents as $content)
        $results[$nextMarker = (string)$content->Key] = ['name' => (string)$content->Key, 'time' => date('Y-m-d H:i:s', strtotime((string)$content->LastModified)), 'size' => (int)$content->Size, 'hash' => substr((string)$content->ETag, 1, -1)];

    if ($returnCommonPrefixes && isset($rest->body, $rest->body->CommonPrefixes))
      foreach ($rest->body->CommonPrefixes as $content)
        $results[(string)$content->Prefix] = ['prefix' => (string)$content->Prefix];

    if (isset($rest->body, $rest->body->IsTruncated) && (((string)$rest->body->IsTruncated) == 'false'))
      return $results;

    if (isset($rest->body, $rest->body->NextMarker))
      $nextMarker = (string)$rest->body->NextMarker;

    if ($maxKeys || !$nextMarker || (((string)$rest->body->IsTruncated) != 'true'))
      return $results;

    do {
      if (!self::isSuccess($rest = S3Request::create('GET', $bucket)->setParameter('marker', $nextMarker)->setParameter('prefix', $prefix)->setParameter('delimiter', $delimiter)->getResponse()))
        break;

      if (isset($rest->body, $rest->body->Contents))
        foreach ($rest->body->Contents as $content)
          $results[$nextMarker = (string)$content->Key] = ['name' => (string)$content->Key, 'time' => date('Y-m-d H:i:s', strtotime((string)$content->LastModified)), 'size' => (int)$content->Size, 'hash' => substr((string)$content->ETag, 1, -1)];

      if ($returnCommonPrefixes && isset($rest->body, $rest->body->CommonPrefixes))
        foreach ($rest->body->CommonPrefixes as $content)
          $results[(string)$content->Prefix] = ['prefix' => (string)$content->Prefix];

      if (isset($rest->body, $rest->body->NextMarker))
        $nextMarker = (string)$rest->body->NextMarker;

    } while (($rest !== false) && (((string)$rest->body->IsTruncated) == 'true'));

    return $results;
  }

  public static function createBucket($bucket, $acl = self::ACL_PRIVATE, $location = false) {
    $rest = S3Request::create('PUT', $bucket)->setAmzHeader('x-amz-acl', $acl);

    if ($location) {
      $dom = new \DOMDocument();
      $configuration = $dom->createElement('CreateBucketConfiguration');
      $configuration->appendChild($dom->createElement('LocationConstraint', strtoupper($location)));
      $dom->appendChild($configuration);

      $rest->setHeader('Content-Type', 'application/xml')
           ->setData($dom->saveXML());
    }

    return self::isSuccess($rest = $rest->getResponse()) ? true : self::log('createBucket 異常的 HTTP 狀態', 'Code：' . $rest->code, '參數：' . json_encode(['bucket' => $bucket, 'acl' => $acl, 'location' => $location]));
  }

  public static function deleteBucket($bucket) {
    return self::isSuccess($rest = S3Request::create('DELETE', $bucket)->getResponse(), [200, 204]) ? true : self::log('deleteBucket 異常的 HTTP 狀態', 'Code：' . $rest->code, '參數：' . json_encode(['bucket' => $bucket]));
  }

  private static function getMimeType(&$file) {
    return ($extension = self::getMimeByExtension($file)) ? $extension : 'text/plain';//'application/octet-stream';
  }

  private static function getMimeByExtension($file) {
    $extension = strtolower(substr(strrchr($file, '.'), 1));
    return isset(self::$exts[$extension]) ? is_array(self::$exts[$extension]) ? current(self::$exts[$extension]) : self::$exts[$extension] : false;
  }

  public static function fileMD5($filePath) {
    return base64_encode(md5_file($filePath, true));
  }

  // $headers => "Cache-Control" => "max-age=5", setcache 5 sec
  public static function putObject($filePath, $bucket, $s3Path, $acl = self::ACL_PUBLIC_READ, $amzHeaders = [], $headers = []) {
    if (!(is_file($filePath) && is_readable($filePath)))
      return self::log('putObject 無法開啟檔案', '路徑：' . $filePath, '參數：' . json_encode(['filePath' => $filePath, 'bucket' => $bucket, 's3Path' => $s3Path, 'acl' => $acl, 'amzHeaders' => $amzHeaders, 'headers' => $headers]));

    $rest = S3Request::create('PUT', $bucket, $s3Path)
                     ->setHeaders(array_merge(['Content-Type' => self::getMimeType($filePath), 'Content-MD5' => self::fileMD5($filePath)], $headers))->setAmzHeaders(array_merge(['x-amz-acl' => $acl], $amzHeaders))->setFile($filePath);

    if ($rest->getSize() < 0 || $rest->getFile() === null)
      return self::log('putObject 參數錯誤', 'Code：' . $rest->code, '參數：' . json_encode(['filePath' => $filePath, 'bucket' => $bucket, 's3Path' => $s3Path, 'acl' => $acl, 'amzHeaders' => $amzHeaders, 'headers' => $headers]));

    return self::isSuccess($rest = $rest->getResponse()) ? true : self::log('putObject 異常的 HTTP 狀態', 'Code：' . $rest->code, '參數：' . json_encode(['filePath' => $filePath, 'bucket' => $bucket, 's3Path' => $s3Path, 'acl' => $acl, 'amzHeaders' => $amzHeaders, 'headers' => $headers]));
  }

  public static function getObject($bucket, $uri, $saveTo = null) {
    $rest = S3Request::create('GET', $bucket, $uri);
    if ($saveTo && ($rest->setFile($saveTo, 'wb', false)->getFile() === null || !($rest->file = realpath($saveTo))))
      return self::log('getObject 無法寫入', '位置：' . $saveTo, '參數：' . json_encode(['bucket' => $bucket, 'uri' => $uri, 'saveTo' => $saveTo]));

    return self::isSuccess($rest = $rest->getResponse()) ? true : self::log('getObject 異常的 HTTP 狀態', 'Code：' . $rest->code, '參數：' . json_encode(['bucket' => $bucket, 'uri' => $uri, 'saveTo' => $saveTo]));
  }

  public static function getObjectInfo($bucket, $uri) {
    return self::isSuccess($rest = S3Request::create('HEAD', $bucket, $uri)->getResponse(), [200, 404]) ? $rest->code == 200 ? $rest->headers : false : self::log('getObjectInfo 異常的 HTTP 狀態', 'Code：' . $rest->code, '參數：' . json_encode(['bucket' => $bucket, 'uri' => $uri]));
  }

  public static function copyObject($srcBucket, $srcUri, $bucket, $uri, $acl = self::ACL_PUBLIC_READ, $amzHeaders = [], $headers = []) {
    return self::isSuccess($rest = S3Request::create('PUT', $bucket, $uri)->setHeaders($headers = array_merge(['Content-Length' => 0], $headers))->setAmzHeaders($amzHeaders = array_merge(['x-amz-acl' => $acl, 'x-amz-copy-source' => sprintf('/%s/%s', $srcBucket, $srcUri)], $amzHeaders))->setAmzHeader('x-amz-metadata-directive', $headers || $amzHeaders ? 'REPLACE' : null)->getResponse()) && isset($rest->body->LastModified, $rest->body->ETag) ? ['time' => date('Y-m-d H:i:s', strtotime((string)$rest->body->LastModified)), 'hash' => substr((string)$rest->body->ETag, 1, -1)] : self::log('copyObject 異常的 HTTP 狀態', 'Code：' . $rest->code, '參數：' . json_encode(['srcBucket' => $srcBucket, 'srcUri' => $srcUri, 'bucket' => $bucket, 'uri' => $uri, 'acl' => $acl, 'amzHeaders' => $amzHeaders, 'headers' => $headers]));
  }

  public static function deleteObject($bucket, $uri) {
    return self::isSuccess($rest = S3Request::create('DELETE', $bucket, $uri)->getResponse(), [200, 204]) ? true : self::log('deleteObject 異常的 HTTP 狀態', 'Code：' . $rest->code, '參數：' . json_encode(['bucket' => $bucket, 'uri' => $uri]));
  }
}