<?php

class Thumbnail {
  private $object = null;
  private static $driver = null;
  private static $logerFunc = null;

  public static function setDriver($driver) {
    is_string($driver) && self::$driver = $driver;
  }
  public static function setLogerFunc($logerFunc) {
    is_callable($logerFunc) && self::$logerFunc = $logerFunc;
  }

  public static function log($obj) {
    $args = func_get_args();
    $obj = array_shift($args);
    ($func = self::$logerFunc) && call_user_func_array($func, [implode('，', array_merge(['[' . get_class($obj) . ']'], $args))]);
    return $obj;
  }

  private static function checkDriver() {
    self::$driver !== null || Thumbnail::error('Thumbnail Driver 錯誤', '請先執行 Thumbnail::setDriver 設定 Driver', '您可以設定 Gd、Imagick');
    in_array(self::$driver, ['Gd', 'Imagick']) || self::error('Thumbnail Driver 錯誤', 'Driver 只允許 Gd 或 Imagick');
  }

  public function __construct($filePath = '', $class = '', $options = []) {
    if (!class_exists($class))
      require_once $class . '.php';

    class_exists($class) || Thumbnail::error('找不到指定的 Driver', '您的 Driver：' . $class);

    $this->object = new $class($filePath, $options);
  }

  public function getObject() {
    return $this->object;
  }

  public static function create($filePath, $options = []) {
    self::checkDriver();
    $thumbnail = new Thumbnail($filePath, 'Thumbnail' . self::$driver, $options);
    return $thumbnail->getObject();
  }

  public static function block9($files, $savePath) {
    self::checkDriver();
    return call_user_func_array(['Thumbnail' . self::$driver, 'block9'], [$files, $savePath]);
  }

  public static function photos($files, $savePath) {
    self::checkDriver();
    return call_user_func_array(['Thumbnail' . self::$driver, 'photos'], [$files, $savePath]);
  }
  
  public static function error() {
    $backtrace = debug_backtrace();
    throw new Exception(implode('，', array_merge(['[' . (isset($backtrace[2]['object']) ? get_class($backtrace[2]['object']) : get_called_class()) . ']'], func_get_args())));
  }

  public static function colorHex2Rgb($hex) {
    if (($hex = str_replace('#', '', $hex)) && ((strlen($hex) == 3) || (strlen($hex) == 6))) {
      if(strlen($hex) == 3) {
        $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
        $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
        $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
      } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
      }
      return [$r, $g, $b];
    } else {
      return [];
    }
  }

  public static function sort2DArr($key, $list) {
    if (!$list)
      return $list;

    $tmp = [];
    foreach ($list as &$ma)
      $tmp[] = &$ma[$key];
    array_multisort($tmp, SORT_DESC, $list);

    return $list;
  }
}

class ThumbnailDimension {
  private $width, $height;

  public function __construct($width, $height) {
    $this->width = intval($width);
    $this->height = intval($height);
    is_numeric($this->width) && is_numeric($this->height) && $this->width > 0 && $this->height > 0 || Thumbnail::error('參數格式錯誤', 'Width：' . $this->width, 'Height：' . $this->height);
  }
  public function width() { return $this->width; }
  public function height() { return $this->height; }
}

abstract class ThumbnailBase {
  private $class = null;
  protected $filePath = null;

  protected $mime = null;
  protected $format = null;
  protected $image = null;
  protected $dimension = null;

  private static $exts = ['jpg' => ['image/jpeg', 'image/pjpeg'], 'gif' => 'image/gif', 'png' => ['image/png', 'image/x-png'], 'tif' => 'image/tiff', 'tiff' => 'image/tiff', 'ico' => 'image/x-icon', 'swf' => 'application/x-shockwave-flash', 'pdf' => ['application/pdf', 'application/x-download'], 'zip' => ['application/x-zip', 'application/zip', 'application/x-zip-compressed'], 'gz' => 'application/x-gzip', 'tar' => 'application/x-tar', 'bz' => 'application/x-bzip', 'bz2' => 'application/x-bzip2', 'txt' => 'text/plain', 'asc' => 'text/plain', 'htm' => 'text/html', 'html' => 'text/html', 'css' => 'text/css', 'js' => 'application/x-javascript', 'xml' => 'text/xml', 'xsl' => 'text/xml', 'ogg' => 'application/ogg', 'mp3' => ['audio/mpeg', 'audio/mpg', 'audio/mpeg3', 'audio/mp3'], 'wav' => ['audio/x-wav', 'audio/wave', 'audio/wav'], 'avi' => 'video/x-msvideo', 'mpg' => 'video/mpeg', 'mpeg' => 'video/mpeg', 'mov' => 'video/quicktime', 'flv' => 'video/x-flv', 'php' => 'application/x-httpd-php', 'hqx' => 'application/mac-binhex40', 'cpt' => 'application/mac-compactpro', 'csv' => ['text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel'], 'bin' => 'application/macbinary', 'dms' => 'application/octet-stream', 'lha' => 'application/octet-stream', 'lzh' => 'application/octet-stream', 'exe' => ['application/octet-stream', 'application/x-msdownload'], 'class' => 'application/octet-stream', 'psd' => 'application/x-photoshop', 'so' => 'application/octet-stream', 'sea' => 'application/octet-stream', 'dll' => 'application/octet-stream', 'oda' => 'application/oda', 'ai' => 'application/postscript', 'eps' => 'application/postscript', 'ps' => 'application/postscript', 'smi' => 'application/smil', 'smil' => 'application/smil', 'mif' => 'application/vnd.mif', 'xls' => ['application/excel', 'application/vnd.ms-excel', 'application/msexcel'], 'ppt' => ['application/powerpoint', 'application/vnd.ms-powerpoint'], 'wbxml' => 'application/wbxml', 'wmlc' => 'application/wmlc', 'dcr' => 'application/x-director', 'dir' => 'application/x-director', 'dxr' => 'application/x-director', 'dvi' => 'application/x-dvi', 'gtar' => 'application/x-gtar', 'php4' => 'application/x-httpd-php', 'php3' => 'application/x-httpd-php', 'phtml' => 'application/x-httpd-php', 'phps' => 'application/x-httpd-php-source', 'sit' => 'application/x-stuffit', 'tgz' => ['application/x-tar', 'application/x-gzip-compressed'], 'xhtml' => 'application/xhtml+xml', 'xht' => 'application/xhtml+xml', 'mid' => 'audio/midi', 'midi' => 'audio/midi', 'mpga' => 'audio/mpeg', 'mp2' => 'audio/mpeg', 'aif' => 'audio/x-aiff', 'aiff' => 'audio/x-aiff', 'aifc' => 'audio/x-aiff', 'ram' => 'audio/x-pn-realaudio', 'rm' => 'audio/x-pn-realaudio', 'rpm' => 'audio/x-pn-realaudio-plugin', 'ra' => 'audio/x-realaudio', 'rv' => 'video/vnd.rn-realvideo', 'bmp' => ['image/bmp', 'image/x-windows-bmp'], 'jpeg' => ['image/jpeg', 'image/pjpeg'], 'jpe' => ['image/jpeg', 'image/pjpeg'], 'shtml' => 'text/html', 'text' => 'text/plain', 'log' => ['text/plain', 'text/x-log'], 'rtx' => 'text/richtext', 'rtf' => 'text/rtf', 'mpe' => 'video/mpeg', 'qt' => 'video/quicktime', 'movie' => 'video/x-sgi-movie', 'doc' => 'application/msword', 'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'], 'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'], 'word' => ['application/msword', 'application/octet-stream'], 'xl' => 'application/excel', 'eml' => 'message/rfc822', 'json' => ['application/json', 'text/json'], 'svg' => 'image/svg+xml'];

  public function __construct($filePath) {
    is_file($filePath) && is_readable($filePath) || Thumbnail::error('檔案不可讀取，或者不存在', '路徑：' . $filePath);
    
    $this->class = get_called_class();
    $this->filePath = $filePath;
   
    $this->init();
  }

  private static function getExtensionByMime($m) {
    static $extensions;

    if (isset($extensions[$m]))
      return $extensions[$m];

    foreach (self::$exts as $ext => $mime)
      if ((is_string($mime) && ($mime == $m)) || ((is_array($mime) && in_array($m, $mime))))
        return $extensions[$m] = $ext;

    return $extensions[$m] = false;
  }

  protected function init() {
    function_exists('mime_content_type') || Thumbnail::error('mime_content_type 函式不存在');
    ($this->mime = strtolower(mime_content_type($this->filePath))) || Thumbnail::error('取不到檔案的 mime 格式', 'Mime：' . $this->mime);
    ($this->format = self::getExtensionByMime($this->mime)) !== false || Thumbnail::error('取不到符合的格式', 'Mime：' . $this->mime);
    isset(static::$allows) && static::$allows && is_array(static::$allows) ? in_array($this->format, static::$allows) || Thumbnail::error('不支援此檔案格式', 'Format：' . $this->format, '只允許：' . json_encode(static::$allows)) : true;
    ($this->image = $this->class == 'ThumbnailImagick' ? new Imagick($this->filePath) : $this->getOldImage($this->format)) || Thumbnail::error('Create image 失敗');
    $this->dimension = $this->getDimension($this->image);
  }

  public function getFormat() {
    return $this->format;
  }

  public function getImage() {
    return $this->image;
  }

  protected function calcImageSizePercent($percent, $dimension) {
    return new ThumbnailDimension(ceil($dimension->width() * $percent / 100), ceil($dimension->height() * $percent / 100));
  }

  protected function calcWidth($oldDimension, $newDimension) {
    $newWidthPercentage = 100 * $newDimension->width() / $oldDimension->width();
    $height = ceil($oldDimension->height() * $newWidthPercentage / 100);
    return new ThumbnailDimension($newDimension->width(), $height);
  }

  protected function calcHeight($oldDimension, $newDimension) {
    $newHeightPercentage  = 100 * $newDimension->height() / $oldDimension->height();
    $width = ceil($oldDimension->width() * $newHeightPercentage / 100);
    return new ThumbnailDimension($width, $newDimension->height());
  }

  protected function calcImageSize($oldDimension, $newDimension) {
    $newSize = new ThumbnailDimension($oldDimension->width(), $oldDimension->height());

    if ($newDimension->width() > 0) {
      $newSize = $this->calcWidth ($oldDimension, $newDimension);
      ($newDimension->height() > 0) && ($newSize->height() > $newDimension->height()) && $newSize = $this->calcHeight($oldDimension, $newDimension);
    }
    if ($newDimension->height() > 0) {
      $newSize = $this->calcHeight($oldDimension, $newDimension);
      ($newDimension->width() > 0) && ($newSize->width() > $newDimension->width()) && $newSize = $this->calcWidth($oldDimension, $newDimension);
    }
    return $newSize;
  }

  protected function calcImageSizeStrict($oldDimension, $newDimension) {
    $newSize = new ThumbnailDimension($newDimension->width(), $newDimension->height());

    if ($newDimension->width() >= $newDimension->height()) {
      if ($oldDimension->width() > $oldDimension->height())  {
        $newSize = $this->calcHeight($oldDimension, $newDimension);
        $newSize->width() < $newDimension->width() && $newSize = $this->calcWidth($oldDimension, $newDimension);
      } else if ($oldDimension->height() >= $oldDimension->width()) {
        $newSize = $this->calcWidth($oldDimension, $newDimension);
        $newSize->height() < $newDimension->height() && $newSize = $this->calcHeight($oldDimension, $newDimension);
      }
    } else if ($newDimension->height() > $newDimension->width()) {
      if ($oldDimension->width() >= $oldDimension->height()) {
        $newSize = $this->calcWidth($oldDimension, $newDimension);
        $newSize->height() < $newDimension->height() && $newSize = $this->calcHeight($oldDimension, $newDimension);
      } else if ($oldDimension->height() > $oldDimension->width()) {
        $newSize = $this->calcHeight($oldDimension, $newDimension);
        $newSize->width() < $newDimension->width() && $newSize = $this->calcWidth($oldDimension, $newDimension);
      }
    }
    return $newSize;
  }
}
