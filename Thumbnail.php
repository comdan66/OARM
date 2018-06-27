<?php

class ThumbnailException extends Exception {}

class Thumbnail {
  private $object = null;

  private static $logerFunc = null;

  public static function setLogerFunc($logerFunc) {
    is_callable($logerFunc) && self::$logerFunc = $logerFunc;
  }

  public static function log() {
    $backtrace = debug_backtrace();
    ($func = self::$logerFunc) && call_user_func_array($func, [implode('，', array_merge(['[' . (isset($backtrace[2]['object']) ? get_class($backtrace[2]['object']) : get_called_class()) . ']'], func_get_args()))]);
    return false;
  }

  public function __construct($filePath = '', $class = '', $options = []) {
    $this->object = new $class($filePath, $options);
  }

  public function getObject () {
    return $this->object;
  }

  public static function createGd($filePath, $options = []) {
    $uti = new Thumbnail($filePath, 'ThumbnailGd', $options);
    return $uti->getObject ();
  }

  public static function createImagick($filePath, $options = []) {
    $uti = new Thumbnail ($filePath, 'ThumbnailImagick', $options);
    return $uti->getObject ();
  }

  public static function error() {
    $backtrace = debug_backtrace();
    throw new ThumbnailException(implode('，', array_merge(['[' . (isset($backtrace[2]['object']) ? get_class($backtrace[2]['object']) : get_called_class()) . ']'], func_get_args())));
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

  // public static function createGdBlock9 ($files, $save) {
  //   Load::sysLib ('ThumbnailDrivers' . DIRECTORY_SEPARATOR . 'Gd' . EXT, '[Thumbnail] 載入物件失敗。');
  //   return call_user_func_array (array ('ThumbnailGd', 'block9'), array ($files, $save));
  // }

  // public static function createImagickBlock9 ($files, $save) {
  //   Load::sysLib ('ThumbnailDrivers' . DIRECTORY_SEPARATOR . 'Imagick' . EXT, '[Thumbnail] 載入物件失敗。');
  //   return call_user_func_array (array ('ThumbnailImagick', 'block9'), array ($files, $save));
  // }

  // public static function createGdPhotos ($files, $save) {
  //   Load::sysLib ('ThumbnailDrivers' . DIRECTORY_SEPARATOR . 'Gd' . EXT, '[Thumbnail] 載入物件失敗。');
  //   return call_user_func_array (array ('ThumbnailGd', 'photos'), array ($files, $save));
  // }
  
  // public static function createImagickPhotos ($files, $save) {
  //   Load::sysLib ('ThumbnailDrivers' . DIRECTORY_SEPARATOR . 'Imagick' . EXT, '[Thumbnail] 載入物件失敗。');
  //   return call_user_func_array (array ('ThumbnailImagick', 'photos'), array ($files, $save));
  // }
}

// class ThumbnailException extends Exception {}

class ThumbnailDimension {
  private $width, $height;

  public function __construct($width, $height) {
    $this->width = intval($width);
    $this->height = intval($height);
    is_numeric($this->width) && is_numeric($this->height) && $this->width > 0 && $this->height > 0 || Thumbnail::error ('參數格式錯誤', 'Width：' . $this->width, 'Height：' . $this->height);
  }
  public function width() { return $this->width; }
  public function height() { return $this->height; }
}

class ThumbnailBase {
  private $class = null;
  protected $filePath = null;

  protected $mime = null;
  protected $format = null;
  protected $image = null;
  protected $dimension = null;

  private static $exts = ['jpg' => ['image/jpeg', 'image/pjpeg'], 'gif' => 'image/gif', 'png' => ['image/png', 'image/x-png'], 'tif' => 'image/tiff', 'tiff' => 'image/tiff', 'ico' => 'image/x-icon', 'swf' => 'application/x-shockwave-flash', 'pdf' => ['application/pdf', 'application/x-download'], 'zip' => ['application/x-zip', 'application/zip', 'application/x-zip-compressed'], 'gz' => 'application/x-gzip', 'tar' => 'application/x-tar', 'bz' => 'application/x-bzip', 'bz2' => 'application/x-bzip2', 'txt' => 'text/plain', 'asc' => 'text/plain', 'htm' => 'text/html', 'html' => 'text/html', 'css' => 'text/css', 'js' => 'application/x-javascript', 'xml' => 'text/xml', 'xsl' => 'text/xml', 'ogg' => 'application/ogg', 'mp3' => ['audio/mpeg', 'audio/mpg', 'audio/mpeg3', 'audio/mp3'], 'wav' => ['audio/x-wav', 'audio/wave', 'audio/wav'], 'avi' => 'video/x-msvideo', 'mpg' => 'video/mpeg', 'mpeg' => 'video/mpeg', 'mov' => 'video/quicktime', 'flv' => 'video/x-flv', 'php' => 'application/x-httpd-php', 'hqx' => 'application/mac-binhex40', 'cpt' => 'application/mac-compactpro', 'csv' => ['text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel'], 'bin' => 'application/macbinary', 'dms' => 'application/octet-stream', 'lha' => 'application/octet-stream', 'lzh' => 'application/octet-stream', 'exe' => ['application/octet-stream', 'application/x-msdownload'], 'class' => 'application/octet-stream', 'psd' => 'application/x-photoshop', 'so' => 'application/octet-stream', 'sea' => 'application/octet-stream', 'dll' => 'application/octet-stream', 'oda' => 'application/oda', 'ai' => 'application/postscript', 'eps' => 'application/postscript', 'ps' => 'application/postscript', 'smi' => 'application/smil', 'smil' => 'application/smil', 'mif' => 'application/vnd.mif', 'xls' => ['application/excel', 'application/vnd.ms-excel', 'application/msexcel'], 'ppt' => ['application/powerpoint', 'application/vnd.ms-powerpoint'], 'wbxml' => 'application/wbxml', 'wmlc' => 'application/wmlc', 'dcr' => 'application/x-director', 'dir' => 'application/x-director', 'dxr' => 'application/x-director', 'dvi' => 'application/x-dvi', 'gtar' => 'application/x-gtar', 'php4' => 'application/x-httpd-php', 'php3' => 'application/x-httpd-php', 'phtml' => 'application/x-httpd-php', 'phps' => 'application/x-httpd-php-source', 'sit' => 'application/x-stuffit', 'tgz' => ['application/x-tar', 'application/x-gzip-compressed'], 'xhtml' => 'application/xhtml+xml', 'xht' => 'application/xhtml+xml', 'mid' => 'audio/midi', 'midi' => 'audio/midi', 'mpga' => 'audio/mpeg', 'mp2' => 'audio/mpeg', 'aif' => 'audio/x-aiff', 'aiff' => 'audio/x-aiff', 'aifc' => 'audio/x-aiff', 'ram' => 'audio/x-pn-realaudio', 'rm' => 'audio/x-pn-realaudio', 'rpm' => 'audio/x-pn-realaudio-plugin', 'ra' => 'audio/x-realaudio', 'rv' => 'video/vnd.rn-realvideo', 'bmp' => ['image/bmp', 'image/x-windows-bmp'], 'jpeg' => ['image/jpeg', 'image/pjpeg'], 'jpe' => ['image/jpeg', 'image/pjpeg'], 'shtml' => 'text/html', 'text' => 'text/plain', 'log' => ['text/plain', 'text/x-log'], 'rtx' => 'text/richtext', 'rtf' => 'text/rtf', 'mpe' => 'video/mpeg', 'qt' => 'video/quicktime', 'movie' => 'video/x-sgi-movie', 'doc' => 'application/msword', 'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'], 'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'], 'word' => ['application/msword', 'application/octet-stream'], 'xl' => 'application/excel', 'eml' => 'message/rfc822', 'json' => ['application/json', 'text/json'], 'svg' => 'image/svg+xml'];

  public function __construct($filePath) {
    is_file($filePath) && is_readable($filePath) || Thumbnail::error ('檔案不可讀取，或者不存在', '路徑：' . $filePath);
    
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
    function_exists('mime_content_type') || Thumbnail::error ('mime_content_type 函式不存在');
    ($this->mime = strtolower(mime_content_type($this->filePath))) || Thumbnail::error ('取不到檔案的 mime 格式', 'Mime：' . $this->mime);
    ($this->format = self::getExtensionByMime($this->mime)) !== false || Thumbnail::error ('取不到符合的格式', 'Mime：' . $this->mime);
    isset(static::$allows) && static::$allows && is_array(static::$allows) ? in_array($this->format, static::$allows) || Thumbnail::error ('不支援此檔案格式', 'Format：' . $this->format, '只允許：' . json_encode(static::$allows)) : true;
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
    return new ThumbnailDimension($newDimension->width (), $height);
  }

  protected function calcHeight($oldDimension, $newDimension) {
    $newHeightPercentage  = 100 * $newDimension->height() / $oldDimension->height();
    $width = ceil ($oldDimension->width() * $newHeightPercentage / 100);
    return new ThumbnailDimension ($width, $newDimension->height());
  }
  protected function calcImageSize($oldDimension, $newDimension) {
    $newSize = new ThumbnailDimension($oldDimension->width(), $oldDimension->height());

    if ($newDimension->width() > 0) {
      $newSize = $this->calcWidth ($oldDimension, $newDimension);
      ($newDimension->height() > 0) && ($newSize->height() > $newDimension->height()) && $newSize = $this->calcHeight ($oldDimension, $newDimension);
    }
    if ($newDimension->height() > 0) {
      $newSize = $this->calcHeight ($oldDimension, $newDimension);
      ($newDimension->width() > 0) && ($newSize->width() > $newDimension->width()) && $newSize = $this->calcWidth ($oldDimension, $newDimension);
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








class ThumbnailGd extends ThumbnailBase {
  protected static $allows = ['gif', 'jpg', 'png'];
  private $options = [
    'resize_up' => true,
    'interlace' => null,
    'jpeg_quality' => 90,
    'preserve_alpha' => true,
    'preserve_transparency' => true,
    'alpha_maskColor' => [255, 255, 255],
    'transparency_mask_color' => [0, 0, 0]
  ];

  public function __construct ($filepame, $options = []) {
    parent::__construct ($filepame);
    $this->options = array_merge($this->options, array_intersect_key($options, $this->options));
  }

  protected function getOldImage($format) {
    switch ($format) {
      case 'gif':  return imagecreatefromgif($this->filePath);
      case 'jpg': return imagecreatefromjpeg($this->filePath);
      case 'png': return imagecreatefrompng($this->filePath);
      default: Thumbnail::error('找尋不到符合的格式，或者不支援此檔案格式', 'Format：' . $format);
    }
  }

  public function getDimension($image = null) {
    $image = $image ? $image : $this->getOldImage($this->format);
    return new ThumbnailDimension(imagesx($image), imagesy($image));
  }

  private function _preserveAlpha($image) {
    if ($this->format == 'png' && $this->options['preserve_alpha'] === true) {
      imagealphablending($image, false);
      imagefill($image, 0, 0, imagecolorallocatealpha($image, $this->options['alpha_maskColor'][0], $this->options['alpha_maskColor'][1], $this->options['alpha_maskColor'][2], 0));
      imagesavealpha($image, true);
    }

    if ($this->format == 'gif' && $this->options['preserve_transparency'] === true) {
      imagecolortransparent($image, imagecolorallocate($image, $this->options['transparency_mask_color'][0], $this->options['transparency_mask_color'][1], $this->options['transparency_mask_color'][2]));
      imagetruecolortopalette($image, true, 256);
    }

    return $image;
  }

  private function _copyReSampled($newImage, $oldImage, $newX, $newY, $oldX, $oldY, $newWidth, $newHeight, $oldWidth, $oldHeight) {
    imagecopyresampled($newImage, $oldImage, $newX, $newY, $oldX, $oldY, $newWidth, $newHeight, $oldWidth, $oldHeight);
    return $this->_updateImage($newImage);
  }

  private function _updateImage($image) {
    $this->image = $image;
    $this->dimension = $this->getDimension($this->image);
    return $this;
  }

  public function save($save) {
    imageinterlace($this->image, $this->options['interlace'] ? 1 : 0);

    switch ($this->format) {
      case 'jpg': return @imagejpeg($this->image, $save, $this->options['jpeg_quality']);
      case 'gif': return @imagegif($this->image, $save);
      case 'png': return @imagepng($this->image, $save);
      default: return false;
    }
  }

  static function verifyColor(&$color) {
    $color = is_string($color) ? Thumbnail::colorHex2Rgb($color) : $color;
    return is_array($color) && (count(array_filter($color, function ($color) { return $color >= 0 && $color <= 255; })) == 3);
  }

  public function pad($width, $height, $color = [255, 255, 255]) {
    $width = intval($width);
    $height = intval($height);

    if ($width <= 0 || $height <= 0) {
      Thumbnail::log('新尺寸錯誤', '尺寸寬高一定要大於 0', '寬：' . $width, '高：' . $height);
      return $this;
    }

    if ($width == $this->dimension->width() && $height == $this->dimension->height())
      return $this;

    if (!ThumbnailGd::verifyColor($color)) {
      Thumbnail::log('色碼格式錯誤，目前只支援字串 HEX、RGB 陣列格式', '色碼：' . (is_string($color) ? $color : json_encode($color)));
      return $this;
    }

    if ($width < $this->dimension->width() || $height < $this->dimension->height())
      $this->resize($width, $height);

    $newImage = function_exists('imagecreatetruecolor') ? imagecreatetruecolor($width, $height) : imagecreate($width, $height);
    imagefill($newImage, 0, 0, imagecolorallocate($newImage, $color[0], $color[1], $color[2]));

    return $this->_copyReSampled($newImage, $this->image, intval(($width - $this->dimension->width()) / 2), intval(($height - $this->dimension->height()) / 2), 0, 0, $this->dimension->width(), $this->dimension->height(), $this->dimension->width(), $this->dimension->height());
  }

  private function createNewDimension($width, $height) {
    return new ThumbnailDimension(!$this->options['resize_up'] && ($width > $this->dimension->width()) ? $this->dimension->width() : $width, !$this->options['resize_up'] && ($height > $this->dimension->height()) ? $this->dimension->height() : $height);
  }

  public function resizeByWidth($width) {
    return $this->resize($width, $width, 'w');
  }
  public function resizeByHeight($height) {
    return $this->resize($height, $height, 'h');
  }
  public function resize($width, $height, $method = 'both') {
    $width = intval($width);
    $height = intval($height);

    if ($width <= 0 || $height <= 0) {
      Thumbnail::log('新尺寸錯誤', '尺寸寬高一定要大於 0', '寬：' . $width, '高：' . $height);
      return $this;
    }

    if ($width == $this->dimension->width() && $height == $this->dimension->height())
      return $this;

    $newDimension = $this->createNewDimension($width, $height);

    switch ($method) {
      case 'b': case 'both': default:
        $newDimension = $this->calcImageSize($this->dimension, $newDimension);
        break;

      case 'w': case 'width':
        $newDimension = $this->calcWidth($this->dimension, $newDimension);
        break;

      case 'h': case 'height':
        $newDimension = $this->calcHeight($this->dimension, $newDimension);
        break;
    }

    $newImage = function_exists('imagecreatetruecolor') ? imagecreatetruecolor($newDimension->width(), $newDimension->height()) : imagecreate($newDimension->width(), $newDimension->height());
    $newImage = $this->_preserveAlpha($newImage);

    return $this->_copyReSampled($newImage, $this->image, 0, 0, 0, 0, $newDimension->width(), $newDimension->height(), $this->dimension->width(), $this->dimension->height());
  }


  public function adaptiveResizePercent($width, $height, $percent) {
    $width = intval($width);
    $height = intval($height);

    if ($width <= 0 || $height <= 0) {
      Thumbnail::log('新尺寸錯誤', '尺寸寬高一定要大於 0', '寬：' . $width, '高：' . $height);
      return $this;
    }

    if ($percent < 0 || $percent > 100) {
      Thumbnail::log('百分比例錯誤', '百分比要在 0 ~ 100 之間', 'Percent：' . $percent);
      return $this;
    }


    if ($width == $this->dimension->width() && $height == $this->dimension->height())
      return $this;

    $newDimension = $this->createNewDimension($width, $height);
    $newDimension = $this->calcImageSizeStrict($this->dimension, $newDimension);
    $this->resize($newDimension->width(), $newDimension->height());
    $newDimension = $this->createNewDimension($width, $height);

    $newImage = function_exists('imagecreatetruecolor') ? imagecreatetruecolor($newDimension->width(), $newDimension->height()) : imagecreate($newDimension->width(), $newDimension->height());
    $newImage = $this->_preserveAlpha($newImage);

    $cropX = $cropY = 0;

    if ($this->dimension->width() > $newDimension->width())
      $cropX = intval(($percent / 100) * ($this->dimension->width() - $newDimension->width()));
    else if ($this->dimension->height() > $newDimension->height())
      $cropY = intval(($percent / 100) * ($this->dimension->height() - $newDimension->height()));

    return $this->_copyReSampled($newImage, $this->image, 0, 0, $cropX, $cropY, $newDimension->width(), $newDimension->height(), $newDimension->width(), $newDimension->height());
  }

  public function adaptiveResize($width, $height) {
    return $this->adaptiveResizePercent($width, $height, 50);
  }

  public function resizePercent($percent = 0) {
    if ($percent < 1) {
      Thumbnail::log('縮圖比例錯誤', '百分比要大於 1', 'Percent：' . $percent);
      return $this;
    }

    if ($percent == 100)
      return $this;

    $newDimension = $this->calcImageSizePercent($percent, $this->dimension);

    return $this->resize($newDimension->width(), $newDimension->height());
  }






  public function crop ($startX, $startY, $width, $height) {
    if (!((($width = intval ($width)) > 0) && (($height = intval ($height)) > 0)) && !Thumbnail::error ('[ThumbnailGd] 新尺寸錯誤，Width：' . $width . '，Height：' . $height . '，尺寸寬高一定要大於 0。'))
      return $this;

    if (!(($startX >= 0) && ($startY >= 0)) && !Thumbnail::error ('[ThumbnailGd] 起始點錯誤，X：' . $startX . '，Y：' . $startY . '，水平、垂直的起始點一定要大於 0。'))
      return $this;

    if (($startX == 0) && ($startY == 0) && ($width == $this->dimension->width ()) && ($height == $this->dimension->height ()))
      return $this;

    $width  = $this->dimension->width () < $width ? $this->dimension->width () : $width;
    $height = $this->dimension->height () < $height ? $this->dimension->height () : $height;
    $startX = ($startX + $width) > $this->dimension->width () ? $this->dimension->width () - $width : $startX;
    $startY = ($startY + $height) > $this->dimension->height () ? $this->dimension->height () - $height : $startY;
    $newImage = function_exists ('imagecreatetruecolor') ? imagecreatetruecolor ($width, $height) : imagecreate ($width, $height);
    $newImage = $this->_preserveAlpha ($newImage);

    return $this->_copyReSampled ($newImage, $this->image, 0, 0, $startX, $startY, $width, $height, $width, $height);
  }

  public function cropFromCenter ($width, $height) {
    if (!((($width = intval ($width)) > 0) && (($height = intval ($height)) > 0)) && !Thumbnail::error ('[ThumbnailGd] 新尺寸錯誤，Width：' . $width . '，Height：' . $height . '，尺寸寬高一定要大於 0。'))
      return $this;

    if (($width == $this->dimension->width ()) && ($height == $this->dimension->height ()))
      return $this;

    if (($width > $this->dimension->width ()) && ($height > $this->dimension->height ()))
      return $this->pad ($width, $height);

    $startX = intval (($this->dimension->width () - $width) / 2);
    $startY = intval (($this->dimension->height () - $height) / 2);
    $width  = $this->dimension->width () < $width ? $this->dimension->width () : $width;
    $height = $this->dimension->height () < $height ? $this->dimension->height () : $height;

    return $this->crop ($startX, $startY, $width, $height);
  }

  public function rotate ($degree, $color = array (255, 255, 255)) {
    if (!function_exists ('imagerotate') && !Thumbnail::error ('[[ThumbnailGd] 沒有載入 imagerotate 函式。'))
      return $this;

    if (!is_numeric ($degree) && !Thumbnail::error ('[ThumbnailGd] 角度一定要是數字，Degree：' . $degree))
      return $this;

    if (!ThumbnailGd::verifyColor ($color) && !Thumbnail::error ('[ThumbnailGd] 色碼格式錯誤，目前只支援字串 HEX、RGB 陣列格式。'))
      return $this;

    if (!($degree % 360))
      return $this;

    $temp = function_exists ('imagecreatetruecolor') ? imagecreatetruecolor (1, 1) : imagecreate (1, 1);
    $newImage = imagerotate ($this->image, 0 - $degree, imagecolorallocate ($temp, $color[0], $color[1], $color[2]));

    return $this->_updateImage ($newImage);
  }

  public function adaptiveResizeQuadrant ($width, $height, $item = 'c') {
    if (!((($width = intval ($width)) > 0) && (($height = intval ($height)) > 0)) && !Thumbnail::error ('[ThumbnailGd] 新尺寸錯誤，Width：' . $width . '，Height：' . $height . '，尺寸寬高一定要大於 0。'))
      return $this;

    if (($width == $this->dimension->width ()) && ($height == $this->dimension->height ()))
      return $this;

    $newDimension = $this->createNewDimension ($width, $height);
    $newDimension = $this->calcImageSizeStrict ($this->dimension, $newDimension);
    $this->resize ($newDimension->width (), $newDimension->height ());
    $newDimension = $this->createNewDimension ($width, $height);
    $newImage = function_exists ('imagecreatetruecolor') ? imagecreatetruecolor ($newDimension->width (), $newDimension->height ()) : imagecreate ($newDimension->width (), $newDimension->height ());
    $newImage = $this->_preserveAlpha ($newImage);

    $cropX = $cropY = 0;

    if ($this->dimension->width () > $newDimension->width ()) {
      switch ($item) {
        case 'l': case 'L': $cropX = 0; break;
        case 'r': case 'R': $cropX = intval ($this->dimension->width () - $newDimension->width ()); break;
        case 'c': case 'C': default: $cropX = intval (($this->dimension->width () - $newDimension->width ()) / 2); break;
      }
    } else if ($this->dimension->height () > $newDimension->height ()) {
      switch ($item) {
        case 't': case 'T': $cropY = 0; break;
        case 'b': case 'B': $cropY = intval ($this->dimension->height () - $newDimension->height ()); break;
        case 'c': case 'C': default: $cropY = intval(($this->dimension->height () - $newDimension->height ()) / 2); break;
      }
    }

    return $this->_copyReSampled ($newImage, $this->image, 0, 0, $cropX, $cropY, $newDimension->width (), $newDimension->height (), $newDimension->width (), $newDimension->height ());
  }

  public static function block9 ($files, $save, $interlace = null, $jpegQuality = 100) {
    if (!(count ($files) >= 9) && !Thumbnail::error ('[ThumbnailGd] 參數錯誤，Files Count：' . count ($files) . '，參數 Files 數量一定要大於等於 9。'))
      return $this;

    if (!$save && !Thumbnail::error ('[ThumbnailGd] 錯誤的儲存路徑，Path：' . $save))
      return $this;

    $positions = array (
      array ('left' =>   2, 'top' =>   2, 'width' => 130, 'height' => 130), array ('left' => 134, 'top' =>   2, 'width' =>  64, 'height' =>  64), array ('left' => 200, 'top' =>   2, 'width' =>  64, 'height' =>  64),
      array ('left' => 134, 'top' =>  68, 'width' =>  64, 'height' =>  64), array ('left' => 200, 'top' =>  68, 'width' =>  64, 'height' =>  64), array ('left' =>   2, 'top' => 134, 'width' =>  64, 'height' =>  64),
      array ('left' =>  68, 'top' => 134, 'width' =>  64, 'height' =>  64), array ('left' => 134, 'top' => 134, 'width' =>  64, 'height' =>  64), array ('left' => 200, 'top' => 134, 'width' =>  64, 'height' =>  64),
    );

    $image = imagecreatetruecolor (266, 200);
    imagefill ($image, 0, 0, imagecolorallocate ($image, 255, 255, 255));
    for ($i = 0; $i < 9; $i++)
      imagecopymerge ($image, Thumbnail::createGd ($files[$i])->adaptiveResizeQuadrant ($positions[$i]['width'], $positions[$i]['height'])->getImage (), $positions[$i]['left'], $positions[$i]['top'], 0, 0, $positions[$i]['width'], $positions[$i]['height'], 100);

    isset ($interlace) && imageinterlace ($image, $interlace ? 1 : 0);

    switch (pathinfo ($save, PATHINFO_EXTENSION)) {
      case 'jpg': return @imagejpeg ($image, $save, $jpegQuality);
      case 'gif': return @imagegif ($image, $save);
      default: case 'png': return @imagepng ($image, $save);
    }
  }

  public static function photos ($files, $save, $interlace = null, $jpegQuality = 100) {
    if (!(count ($files) >= 1) && !Thumbnail::error ('[ThumbnailGd] 參數錯誤，Files Count：' . count ($files), '參數 Files 數量一定要大於 1。'))
      return $this;

    if (!$save && !Thumbnail::error ('[ThumbnailGd] 錯誤的儲存路徑，Path：' . $save))
      return $this;

    $w = 1200;
    $h = 630;

    $image = imagecreatetruecolor ($w, $h);
    imagefill ($image, 0, 0, imagecolorallocate ($image, 255, 255, 255));

    $spacing = 5;
    $positions = array ();
    switch (count ($files)) {
      case 1: $positions = array (array ('left' => 0, 'top' => 0, 'width' => $w, 'height' => $h),); break;
      case 2: $positions = array (array ('left' => 0, 'top' => 0, 'width' => $w / 2 - $spacing, 'height' => $h), array ('left' => $w / 2 + $spacing, 'top' => 0, 'width' => $w / 2 - $spacing, 'height' => $h),); break;
      case 3: $positions = array (array ('left' => 0, 'top' => 0, 'width' => $w / 2 - $spacing, 'height' => $h), array ('left' => $w / 2 + $spacing, 'top' => 0, 'width' => $w / 2 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => $w / 2 + $spacing, 'top' => $h / 2 + $spacing, 'width' => $w / 2 - $spacing, 'height' => $h / 2 - $spacing),); break;
      case 4: $positions = array (array ('left' => 0, 'top' => 0, 'width' => $w, 'height' => $h / 2 - $spacing), array ('left' => 0, 'top' => $h / 2 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => $w / 3 + $spacing, 'top' => $h / 2 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => ($w / 3 + $spacing) * 2, 'top' => $h / 2 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing),); break;
      case 5: $positions = array (array ('left' => 0, 'top' => 0, 'width' => $w / 2 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => $w / 2 + $spacing, 'top' => 0, 'width' => $w / 2 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => 0, 'top' => $h / 2 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => $w / 3 + $spacing, 'top' => $h / 2 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => ($w / 3 + $spacing) * 2, 'top' => $h / 2 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing),); break;
      case 6: $positions = array (array ('left' => 0, 'top' => 0, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => $w / 3 + $spacing, 'top' => 0, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => ($w / 3 + $spacing) * 2, 'top' => 0, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => 0, 'top' => $h / 2 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => $w / 3 + $spacing, 'top' => $h / 2 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => ($w / 3 + $spacing) * 2, 'top' => $h / 2 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing),); break;
      case 7: $positions = array (array ('left' => 0, 'top' => 0, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => 0, 'top' => $h / 2 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => $w / 3 + $spacing, 'top' => 0, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => $w / 3 + $spacing, 'top' => $h / 3 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => $w / 3 + $spacing, 'top' => ($h / 3 + $spacing) * 2, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => ($w / 3 + $spacing) * 2, 'top' => 0, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => ($w / 3 + $spacing) * 2, 'top' => $h / 2 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing),); break;
      case 8: $positions = array (array ('left' => 0, 'top' => 0, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => 0, 'top' => $h / 3 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => 0, 'top' => ($h / 3 + $spacing) * 2, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => $w / 3 + $spacing, 'top' => 0, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => $w / 3 + $spacing, 'top' => $h / 2 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => ($w / 3 + $spacing) * 2, 'top' => 0, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => ($w / 3 + $spacing) * 2, 'top' => $h / 3 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => ($w / 3 + $spacing) * 2, 'top' => ($h / 3 + $spacing) * 2, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing),); break;
      default: case 9: $positions = array (array ('left' => 0, 'top' => 0, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => 0, 'top' => $h / 3 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => 0, 'top' => ($h / 3 + $spacing) * 2, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => $w / 3 + $spacing, 'top' => 0, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => $w / 3 + $spacing, 'top' => $h / 3 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => $w / 3 + $spacing, 'top' => ($h / 3 + $spacing) * 2, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => ($w / 3 + $spacing) * 2, 'top' => 0, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => ($w / 3 + $spacing) * 2, 'top' => $h / 3 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => ($w / 3 + $spacing) * 2, 'top' => ($h / 3 + $spacing) * 2, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing),); break;
    }

    for ($i = 0; $i < count ($positions); $i++)
      imagecopymerge ($image, Thumbnail::createGd ($files[$i])->adaptiveResizeQuadrant ($positions[$i]['width'], $positions[$i]['height'])->getImage (), $positions[$i]['left'], $positions[$i]['top'], 0, 0, $positions[$i]['width'], $positions[$i]['height'], 100);

    isset ($interlace) && imageinterlace ($image, $interlace ? 1 : 0);

    switch (pathinfo ($save, PATHINFO_EXTENSION)) {
      case 'jpg': return @imagejpeg ($image, $save, $jpegQuality);
      case 'gif': return @imagegif ($image, $save);
      default: case 'png': return @imagepng ($image, $save);
    }
  }
}


// class ThumbnailImagick extends ThumbnailBase {
//   public function __construct ($filepame, $options = []) {
//     parent::__construct ($filepame);
//   }

//   public function getDimension ($image = null) {
//     $image || $image = clone $this->image;

//     if (!((($imagePage = $image->getImagePage ()) && isset ($imagePage['width'], $imagePage['height']) && $imagePage['width'] > 0 && $imagePage['height'] > 0) || (($imagePage = $image->getImageGeometry ()) && isset ($imagePage['width'], $imagePage['height']) && $imagePage['width'] > 0 && $imagePage['height'] > 0)))
//       return Thumbnail::mustError ('[ThumbnailImagick] 無法取得尺寸。');

//     return new ThumbnailDimension ($imagePage['width'], $imagePage['height']);
//   }

//   private function _machiningImageResize ($newDimension) {
//     $newImage = clone $this->image;
//     $newImage = $newImage->coalesceImages ();

//     if ($this->format == 'gif')
//       do {
//         $newImage->thumbnailImage ($newDimension->width (), $newDimension->height (), false);
//       } while ($newImage->nextImage () || !$newImage = $newImage->deconstructImages ());
//     else
//       $newImage->thumbnailImage ($newDimension->width (), $newDimension->height (), false);

//     return $newImage;
//   }

//   private function _machiningImageCrop ($cropX, $cropY, $width, $height, $color = 'transparent') {
//     $newImage = new Imagick ();
//     $newImage->setFormat ($this->format);

//     if ($this->format == 'gif') {
//       $imagick = clone $this->image;
//       $imagick = $imagick->coalesceImages ();
      
//       do {
//         $temp = new Imagick ();
//         $temp->newImage ($width, $height, new ImagickPixel ($color));
//         $imagick->chopImage ($cropX, $cropY, 0, 0);
//         $temp->compositeImage ($imagick, imagick::COMPOSITE_DEFAULT, 0, 0);

//         $newImage->addImage ($temp);
//         $newImage->setImageDelay ($imagick->getImageDelay ());
//       } while ($imagick->nextImage ());
//     } else {
//       $imagick = clone $this->image;
//       $imagick->chopImage ($cropX, $cropY, 0, 0);
//       $newImage->newImage ($width, $height, new ImagickPixel ($color));
//       $newImage->compositeImage ($imagick, imagick::COMPOSITE_DEFAULT, 0, 0 );
//     }
//     return $newImage;
//   }

//   private function _machiningImageRotate ($degree, $color = 'transparent') {
//     $newImage = new Imagick ();
//     $newImage->setFormat ($this->format);
//     $imagick = clone $this->image;

//     if ($this->format == 'gif') {
//       $imagick->coalesceImages();
      
//       do {
//         $temp = new Imagick ();
//         $imagick->rotateImage (new ImagickPixel ($color), $degree);
//         $newDimension = $this->getDimension ($imagick);
//         $temp->newImage ($newDimension->width (), $newDimension->height (), new ImagickPixel ($color));
//         $temp->compositeImage ($imagick, imagick::COMPOSITE_DEFAULT, 0, 0);
//         $newImage->addImage ($temp);
//         $newImage->setImageDelay ($imagick->getImageDelay ());
//       } while ($imagick->nextImage ());
//     } else {
//       $imagick->rotateImage (new ImagickPixel ($color), $degree);
//       $newDimension = $this->getDimension ($imagick);
//       $newImage->newImage ($newDimension->width (), $newDimension->height (), new ImagickPixel ($color));
//       $newImage->compositeImage ($imagick, imagick::COMPOSITE_DEFAULT, 0, 0);
//     }
//     return $newImage;
//   }

//   private function _updateImage ($image) {
//     $this->image = $image;
//     $this->dimension = $this->getDimension ($image);
//     return $this;
//   }

//   private function _machiningImageFilter ($radius, $sigma, $channel) {
//     if ($this->format == 'gif') {
//       $newImage = clone $this->image;
//       $newImage = $newImage->coalesceImages ();
      
//       do {
//         $newImage->adaptiveBlurImage ($radius, $sigma, $channel);
//       } while ($newImage->nextImage () || !$newImage = $newImage->deconstructImages ());
//     } else {
//       $newImage = clone $this->image;
//       $newImage->adaptiveBlurImage ($radius, $sigma, $channel);
//     }
//     return $newImage;
//   }

//   private function _createFont ($font, $fontSize, $color, $alpha) {
//     $draw = new ImagickDraw ();
//     $draw->setFont ($font);
//     $draw->setFontSize ($fontSize);
//     $draw->setFillColor ($color);
//     // $draw->setFillAlpha ($alpha);
//     return $draw;
//   }

//   public function save ($save, $rawData = true) {
//     return $save ? $this->image->writeImages ($save, $rawData) : Thumbnail::error ('[ThumbnailImagick] 錯誤的儲存路徑，Path：' . $save);
//   }

//   public function pad ($width, $height, $color = 'transparent') {
//     if (!((($width = intval ($width)) > 0) && (($height = intval ($height)) > 0)) && !Thumbnail::error ('[ThumbnailImagick] 新尺寸錯誤，Width：' . $width . '，Height：' . $height . '，尺寸寬高一定要大於 0。'))
//       return $this;

//     if (($width == $this->dimension->width ()) && ($height == $this->dimension->height ()))
//       return $this;

//     if (!is_string ($color) && !Thumbnail::error ('[ThumbnailImagick] 色碼格式錯誤，目前只支援字串 HEX 格式。'))
//       return $this;

//     if (($width < $this->dimension->width ()) || ($height < $this->dimension->height ()))
//       $this->resize ($width, $height);

//     $newImage = new Imagick ();
//     $newImage->setFormat ($this->format);

//     if ($this->format == 'gif') {
//       $imagick = clone $this->image;
//       $imagick = $imagick->coalesceImages ();
//       do {
//         $temp = new Imagick ();
//         $temp->newImage ($width, $height, new ImagickPixel ($color));
//         $temp->compositeImage ($imagick, imagick::COMPOSITE_DEFAULT, intval (($width - $this->dimension->width ()) / 2), intval (($height - $this->dimension->height ()) / 2) );

//         $newImage->addImage ($temp);
//         $newImage->setImageDelay ($imagick->getImageDelay ());
//       } while ($imagick->nextImage ());
//     } else {
//       $newImage->newImage ($width, $height, new ImagickPixel ($color));
//       $newImage->compositeImage (clone $this->image, imagick::COMPOSITE_DEFAULT, intval (($width - $this->dimension->width ()) / 2), intval (($height - $this->dimension->height ()) / 2));
//     }

//     return $this->_updateImage ($newImage);
//   }

//   private function createNewDimension ($width, $height) {
//     return new ThumbnailDimension (!$this->options['resize_up'] && ($width > $this->dimension->width ()) ? $this->dimension->width () : $width, !$this->options['resize_up'] && ($height > $this->dimension->height ()) ? $this->dimension->height () : $height);
//   }

//   public function resize ($width, $height, $method = 'b') {
//     if (!((($width = intval ($width)) > 0) && (($height = intval ($height)) > 0)) && !Thumbnail::error ('[ThumbnailImagick] 新尺寸錯誤，Width：' . $width . '，Height：' . $height . '，尺寸寬高一定要大於 0。'))
//       return $this;

//     if (($width == $this->dimension->width ()) && ($height == $this->dimension->height ()))
//       return $this;

//     $newDimension = $this->createNewDimension ($width, $height);

//     switch ($method) {
//       case 'b': case 'both': default: $newDimension = $this->calcImageSize ($this->dimension, $newDimension); break;
//       case 'w': case 'width': $newDimension = $this->calcWidth ($this->dimension, $newDimension); break;
//       case 'h': case 'height': $newDimension = $this->calcHeight ($this->dimension, $newDimension); break;
//     }

//     $workingImage = $this->_machiningImageResize ($newDimension);

//     return $this->_updateImage ($workingImage);
//   }

//   public function adaptiveResizePercent ($width, $height, $percent) {
//     if (!((($width = intval ($width)) > 0) && (($height = intval ($height)) > 0)) && !Thumbnail::error ('[ThumbnailImagick] 新尺寸錯誤，Width：' . $width . '，Height：' . $height . '，尺寸寬高一定要大於 0。'))
//       return $this;

//     if (!(($percent > -1) && ($percent < 101)) && !Thumbnail::error ('[ThumbnailImagick] 比例錯誤，Percent：' . $percent . '，百分比要在 0 ~ 100 之間。'))
//       return $this;

//     if (($width == $this->dimension->width ()) && ($height == $this->dimension->height ()))
//       return $this;
    
//     $newDimension = $this->createNewDimension ($width, $height);
//     $newDimension = $this->calcImageSizeStrict ($this->dimension, $newDimension);
//     $this->resize ($newDimension->width (), $newDimension->height ());
//     $newDimension = $this->createNewDimension ($width, $height);

//     $cropX = $cropY = 0;

//     if ($this->dimension->width () > $newDimension->width ())
//       $cropX = intval (($percent / 100) * ($this->dimension->width () - $newDimension->width ()));
//     else if ($this->dimension->height () > $newDimension->height ())
//       $cropY = intval (($percent / 100) * ($this->dimension->height () - $newDimension->height ()));

//     $workingImage = $this->_machiningImageCrop ($cropX, $cropY, $newDimension->width (), $newDimension->height ());
//     return $this->_updateImage ($workingImage);
//   }

//   public function adaptiveResize ($width, $height) {
//     return $this->adaptiveResizePercent ($width, $height, 50);
//   }

//   public function resizePercent ($percent = 0) {
//     if ($percent < 1 && !Thumbnail::error ('[ThumbnailImagick] 比例錯誤，Percent：' . $percent . '，百分比要大於 1。'))
//       return $this;

//     if ($percent == 100)
//       return $this;

//     $newDimension = $this->calcImageSizePercent ($percent, $this->dimension);
//     return $this->resize ($newDimension->width (), $newDimension->height ());
//   }

//   public function crop ($startX, $startY, $width, $height) {
//     if (!((($width = intval ($width)) > 0) && (($height = intval ($height)) > 0)) && !Thumbnail::error ('[ThumbnailImagick] 新尺寸錯誤，Width：' . $width . '，Height：' . $height . '，尺寸寬高一定要大於 0。'))
//       return $this;

//     if (!(($startX >= 0) && ($startY >= 0)) && !Thumbnail::error ('[ThumbnailImagick] 起始點錯誤，X：' . $startX . '，Y：' . $startY . '，水平、垂直的起始點一定要大於 0。'))
//       return $this;

//     if (($startX == 0) && ($startY == 0) && ($width == $this->dimension->width ()) && ($height == $this->dimension->height ()))
//       return $this;

//     $width  = $this->dimension->width () < $width ? $this->dimension->width () : $width;
//     $height = $this->dimension->height () < $height ? $this->dimension->height () : $height;

//     $startX + $width > $this->dimension->width () && $startX = $this->dimension->width () - $width;
//     $startY + $height > $this->dimension->height () && $startY = $this->dimension->height () - $height;

//     $workingImage = $this->_machiningImageCrop ($startX, $startY, $width, $height);
//     return $this->_updateImage ($workingImage);
//   }

//   public function cropFromCenter ($width, $height) {
//     if (!((($width = intval ($width)) > 0) && (($height = intval ($height)) > 0)) && !Thumbnail::error ('[ThumbnailImagick] 新尺寸錯誤，Width：' . $width . '，Height：' . $height . '，尺寸寬高一定要大於 0。'))
//       return $this;

//     if (($width == $this->dimension->width ()) && ($height == $this->dimension->height ()))
//       return $this;

//     if (($width > $this->dimension->width ()) && ($height > $this->dimension->height ()))
//       return $this->pad ($width, $height);

//     $startX = intval (($this->dimension->width () - $width) / 2);
//     $startY = intval (($this->dimension->height () - $height) / 2);
//     $width  = $this->dimension->width () < $width ? $this->dimension->width () : $width;
//     $height = $this->dimension->height () < $height ? $this->dimension->height () : $height;

//     return $this->crop ($startX, $startY, $width, $height);
//   }

//   public function rotate ($degree, $color = 'transparent') {
//     if (!is_numeric ($degree) && !Thumbnail::error ('[ThumbnailImagick] 角度一定要是數字，Degree：' . $degree))
//       return $this;

//     if (!is_string ($color) && !Thumbnail::error ('[ThumbnailImagick] 色碼格式錯誤，目前只支援字串 HEX 格式。'))
//       return $this;

//     if (!($degree % 360))
//       return $this;

//     $workingImage = $this->_machiningImageRotate ($degree, $color);

//     return $this->_updateImage ($workingImage);
//   }

//   public function adaptiveResizeQuadrant ($width, $height, $item = 'c') {
//     if (!((($width = intval ($width)) > 0) && (($height = intval ($height)) > 0)) && !Thumbnail::error ('[ThumbnailImagick] 新尺寸錯誤，Width：' . $width . '，Height：' . $height . '，尺寸寬高一定要大於 0。'))
//       return $this;

//     if (($width == $this->dimension->width ()) && ($height == $this->dimension->height ()))
//       return $this;

//     $newDimension = $this->createNewDimension ($width, $height);
//     $newDimension = $this->calcImageSizeStrict ($this->dimension, $newDimension);
//     $this->resize ($newDimension->width (), $newDimension->height ());
//     $newDimension = $this->createNewDimension ($width, $height);
//     $cropX = $cropY = 0;

//     if ($this->dimension->width () > $newDimension->width ()) {
//       switch ($item) {
//         case 'l': case 'L': $cropX = 0; break;
//         case 'r': case 'R': $cropX = intval ($this->dimension->width () - $newDimension->width ()); break;
//         case 'c': case 'C': default: $cropX = intval (($this->dimension->width () - $newDimension->width ()) / 2); break;
//       }
//     } else if ($this->dimension->height () > $newDimension->height ()) {
//       switch ($item) {
//         case 't': case 'T': $cropY = 0; break;
//         case 'b': case 'B': $cropY = intval ($this->dimension->height () - $newDimension->height ()); break;
//         case 'c': case 'C': default: $cropY = intval(($this->dimension->height () - $newDimension->height ()) / 2); break;
//       }
//     }

//     $workingImage = $this->_machiningImageCrop ($cropX, $cropY, $newDimension->width (), $newDimension->height ());

//     return $this->_updateImage ($workingImage);
//   }

//   public function filter ($radius, $sigma, $channel = Imagick::CHANNEL_DEFAULT) {
//     $items = array (imagick::CHANNEL_UNDEFINED, imagick::CHANNEL_RED,     imagick::CHANNEL_GRAY,  imagick::CHANNEL_CYAN,
//                     imagick::CHANNEL_GREEN,     imagick::CHANNEL_MAGENTA, imagick::CHANNEL_BLUE,  imagick::CHANNEL_YELLOW,
//                     imagick::CHANNEL_ALPHA,     imagick::CHANNEL_OPACITY, imagick::CHANNEL_BLACK,
//                     imagick::CHANNEL_INDEX,     imagick::CHANNEL_ALL,     imagick::CHANNEL_DEFAULT);

//     if (!is_numeric ($radius) && !Thumbnail::error ('[ThumbnailImagick] 參數錯誤，Radius：' . $radius . '，參數 Radius 要為數字。'))
//       return $this;

//     if (!is_numeric ($sigma) && !Thumbnail::error ('[ThumbnailImagick] 參數錯誤，Sigma：' . $sigma . '，參數 Sigma 要為數字。'))
//       return $this;

//     if (!in_array ($channel, $items) && !Thumbnail::error ('[ThumbnailImagick] 參數錯誤，Channel：' . $channel . '，參數 Channel 格式不正確。'))
//       return $this;

//     $workingImage = $this->_machiningImageFilter ($radius, $sigma, $channel);

//     return $this->_updateImage ($workingImage);
//   }

//   public function lomography () {
//     $newImage = new Imagick ();
//     $newImage->setFormat ($this->format);

//     if ($this->format == 'gif') {
//       $imagick = clone $this->image;
//       $imagick = $imagick->coalesceImages ();
      
//       do {
//         $temp = new Imagick ();
//         $imagick->setimagebackgroundcolor ("black");
//         $imagick->gammaImage (0.75);
//         $imagick->vignetteImage (0, max ($this->dimension->width (), $this->dimension->height ()) * 0.2, 0 - ($this->dimension->width () * 0.05), 0 - ($this->dimension->height () * 0.05));
//         $temp->newImage ($this->dimension->width (), $this->dimension->height (), new ImagickPixel ('transparent'));
//         $temp->compositeImage ($imagick, imagick::COMPOSITE_DEFAULT, 0, 0);

//         $newImage->addImage ($temp);
//         $newImage->setImageDelay ($imagick->getImageDelay ());
//       } while ($imagick->nextImage ());
//     } else {
//       $newImage = clone $this->image;
//       $newImage->setimagebackgroundcolor("black");
//       $newImage->gammaImage (0.75);
//       $newImage->vignetteImage (0, max ($this->dimension->width (), $this->dimension->height ()) * 0.2, 0 - ($this->dimension->width () * 0.05), 0 - ($this->dimension->height () * 0.05));
//     }
//     return $this->_updateImage ($newImage);
//   }

//   public function getAnalysisDatas ($maxCount = 10) {
//     if (!($maxCount > 0) && !Thumbnail::error ('[ThumbnailImagick] 參數錯誤，Max Count：' . $maxCount . '，參數 Max Count 一定要大於 0。'))
//       return array ();

//     $temp = clone $this->image;

//     $temp->quantizeImage ($maxCount, Imagick::COLORSPACE_RGB, 0, false, false );
//     $pixels = $temp->getImageHistogram ();

//     $datas = array ();
//     $index = 0;
//     $pixelCount = $this->dimension->width () * $this->dimension->height ();

//     if ($pixels && $maxCount)
//       foreach ($pixels as $pixel)
//         if ($index++ < $maxCount)
//           array_push ($datas, array ('color' => $pixel->getColor (), 'count' => $pixel->getColorCount (), 'percent' => round ($pixel->getColorCount () / $pixelCount * 100)));
//         else
//           break;

//     return sort_2d_array ('count', $datas);
//   }

//   public function saveAnalysisChart ($filepame, $font, $maxCount = 10, $fontSize = 14, $rawData = true) {
//     if (!is_readable ($font) && !Thumbnail::error ('[ThumbnailImagick] 參數錯誤，Font：' . $font . '，字型檔案不存在。'))
//       return $this;

//     if (!($maxCount > 0) && !Thumbnail::error ('[ThumbnailImagick] 參數錯誤，MaxCount：' . $maxCount . '，參數 MaxCount 一定要大於 0。'))
//       return $this;

//     if (!($fontSize > 0) && !Thumbnail::error ('[ThumbnailImagick] 參數錯誤，FontSize：' . $fontSize . '，參數 FontSize 大小一定要大於 0。'))
//       return $this;

//     $format = pathinfo ($filepame, PATHINFO_EXTENSION);
//     if (!($format && in_array ($format, $this->options['allow'])) && !Thumbnail::error ('[ThumbnailImagick] 不支援此檔案格式，Format：' . $format))
//       return $this;

//     if (!($datas = $this->getAnalysisDatas ($maxCount)) && !Thumbnail::error ('[ThumbnailImagick] 圖像分析錯誤。'))
//       return $this;

//     $newImage = new Imagick ();

//     foreach ($datas as $data) {
//       $newImage->newImage (400, 20, new ImagickPixel ('white'));

//       $draw = new ImagickDraw ();
//       $draw->setFont ($font);
//       $draw->setFontSize ($fontSize);
//       $newImage->annotateImage ($draw, 25, 14, 0, 'Percentage of total pixels : ' . (strlen ($data['percent'])<2?' ':'') . $data['percent'] . '% (' . $data['count'] . ')');

//       $tile = new Imagick ();
//       $tile->newImage (20, 20, new ImagickPixel ('rgb(' . $data['color']['r'] . ',' . $data['color']['g'] . ',' . $data['color']['b'] . ')'));

//       $newImage->compositeImage ($tile, Imagick::COMPOSITE_OVER, 0, 0);
//     }

//     $newImage = $newImage->montageImage (new imagickdraw (), '1x' . count ($datas) . '+0+0', '400x20+4+2>', imagick::MONTAGEMODE_UNFRAME, '0x0+3+3');
//     $newImage->setImageFormat ($format);
//     $newImage->setFormat ($format);
//     $newImage->writeImages ($filepame, $rawData);

//     return $this;
//   }

//   public function addFont ($text, $font, $startX = 0, $startY = 12, $color = 'black', $fontSize = 12, $alpha = 1, $degree = 0) {
//     if (!$text)
//       return $this;

//     if (!is_readable ($font) && !Thumbnail::error ('[ThumbnailImagick] 參數錯誤，Font：' . $font . '，字型檔案不存在。'))
//       return $this;

//     if (!(($startX >= 0) && ($startY >= 0)) && !Thumbnail::error ('[ThumbnailImagick] 起始點錯誤，X：' . $startX . '，Y：' . $startY . '，水平、垂直的起始點一定要大於 0。'))
//       return $this;

//     if (!is_string ($color) && !Thumbnail::error ('[ThumbnailImagick] 色碼格式錯誤，目前只支援字串 HEX 格式。'))
//       return $this;

//     if (!($fontSize > 0) && !Thumbnail::error ('[ThumbnailImagick] 參數錯誤，FontSize：' . $fontSize . '，FontSize 大小一定要大於 0。'))
//       return $this;

//     if (!($alpha && is_numeric ($alpha) && ($alpha >= 0) && ($alpha <= 1)) && !Thumbnail::error ('[ThumbnailImagick] 參數錯誤，Alpha：' . $alpha . '，參數 Alpha 一定要是 0 或 1。'))
//       return $this;

//     if (!is_numeric ($degree %= 360) && !Thumbnail::error ('[ThumbnailImagick] 角度一定要是數字，Degree：' . $degree ))
//       return $this;

//     if (!($draw = $this->_createFont ($font, $fontSize, $color, $alpha)) && !Thumbnail::error ('[ThumbnailImagick]  Create 文字物件失敗'))
//       return $this;

//     if ($this->format == 'gif') {
//       $newImage = new Imagick ();
//       $newImage->setFormat ($this->format);
//       $imagick = clone $this->image;
//       $imagick = $imagick->coalesceImages ();
      
//       do {
//         $temp = new Imagick ();
//         $temp->newImage ($this->dimension->width (), $this->dimension->height (), new ImagickPixel ('transparent'));
//         $temp->compositeImage ($imagick, imagick::COMPOSITE_DEFAULT, 0, 0);
//         $temp->annotateImage ($draw, $startX, $startY, $degree, $text);
//         $newImage->addImage ($temp);
//         $newImage->setImageDelay ($imagick->getImageDelay ());
//       } while ($imagick->nextImage ());
//     } else {
//       $newImage = clone $this->image;
//       $newImage->annotateImage ($draw, $startX, $startY, $degree, $text);
//     }

//     return $this->_updateImage ($newImage);
//   }

//   public static function block9 ($files, $save = null, $rawData = true) {
//     if (!(count ($files) >= 9) && !Thumbnail::error ('[ThumbnailImagick] 參數錯誤，Files Count：' . count ($files) . '，參數 Files 數量一定要大於等於 9。'))
//       return $this;

//     if (!$save && !Thumbnail::error ('[ThumbnailImagick] 錯誤的儲存路徑，Path：' . $save))
//       return $this;

//     $newImage = new Imagick ();
//     $newImage->newImage (266, 200, new ImagickPixel ('white'));
//     $newImage->setFormat (pathinfo ($save, PATHINFO_EXTENSION));

//     $positions = array (
//       array ('left' =>   2, 'top' =>   2, 'width' => 130, 'height' => 130), array ('left' => 134, 'top' =>   2, 'width' =>  64, 'height' =>  64), array ('left' => 200, 'top' =>   2, 'width' =>  64, 'height' =>  64),
//       array ('left' => 134, 'top' =>  68, 'width' =>  64, 'height' =>  64), array ('left' => 200, 'top' =>  68, 'width' =>  64, 'height' =>  64), array ('left' =>   2, 'top' => 134, 'width' =>  64, 'height' =>  64),
//       array ('left' =>  68, 'top' => 134, 'width' =>  64, 'height' =>  64), array ('left' => 134, 'top' => 134, 'width' =>  64, 'height' =>  64), array ('left' => 200, 'top' => 134, 'width' =>  64, 'height' =>  64),
//     );

//     for ($i = 0; $i < 9; $i++)
//       $newImage->compositeImage (Thumbnail::createImagick ($files[$i])->adaptiveResizeQuadrant ($positions[$i]['width'], $positions[$i]['height'])->getImage (), imagick::COMPOSITE_DEFAULT, $positions[$i]['left'], $positions[$i]['top']);

//     return $newImage->writeImages ($save, $rawData);
//   }

//   public static function photos ($files, $save = null, $rawData = true) {
//     if (!(count ($files) >= 1) && !Thumbnail::error ('[ThumbnailImagick] 參數錯誤，Files Count：' . count ($files), '參數 Files 數量一定要大於 1。'))
//       return $this;

//     if (!$save && !Thumbnail::error ('[ThumbnailImagick] 錯誤的儲存路徑，Path：' . $save))
//       return $this;
    
//     $w = 1200;
//     $h = 630;

//     $newImage = new Imagick ();
//     $newImage->newImage ($w, $h, new ImagickPixel ('white'));
//     $newImage->setFormat (pathinfo ($save, PATHINFO_EXTENSION));
    
//     $spacing = 5;
//     $positions = array ();
//     switch (count ($files)) {
//       case 1: $positions = array (array ('left' => 0, 'top' => 0, 'width' => $w, 'height' => $h),); break;
//       case 2: $positions = array (array ('left' => 0, 'top' => 0, 'width' => $w / 2 - $spacing, 'height' => $h), array ('left' => $w / 2 + $spacing, 'top' => 0, 'width' => $w / 2 - $spacing, 'height' => $h),); break;
//       case 3: $positions = array (array ('left' => 0, 'top' => 0, 'width' => $w / 2 - $spacing, 'height' => $h), array ('left' => $w / 2 + $spacing, 'top' => 0, 'width' => $w / 2 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => $w / 2 + $spacing, 'top' => $h / 2 + $spacing, 'width' => $w / 2 - $spacing, 'height' => $h / 2 - $spacing),); break;
//       case 4: $positions = array (array ('left' => 0, 'top' => 0, 'width' => $w, 'height' => $h / 2 - $spacing), array ('left' => 0, 'top' => $h / 2 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => $w / 3 + $spacing, 'top' => $h / 2 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => ($w / 3 + $spacing) * 2, 'top' => $h / 2 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing),); break;
//       case 5: $positions = array (array ('left' => 0, 'top' => 0, 'width' => $w / 2 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => $w / 2 + $spacing, 'top' => 0, 'width' => $w / 2 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => 0, 'top' => $h / 2 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => $w / 3 + $spacing, 'top' => $h / 2 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => ($w / 3 + $spacing) * 2, 'top' => $h / 2 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing),); break;
//       case 6: $positions = array (array ('left' => 0, 'top' => 0, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => $w / 3 + $spacing, 'top' => 0, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => ($w / 3 + $spacing) * 2, 'top' => 0, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => 0, 'top' => $h / 2 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => $w / 3 + $spacing, 'top' => $h / 2 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => ($w / 3 + $spacing) * 2, 'top' => $h / 2 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing),); break;
//       case 7: $positions = array (array ('left' => 0, 'top' => 0, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => 0, 'top' => $h / 2 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => $w / 3 + $spacing, 'top' => 0, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => $w / 3 + $spacing, 'top' => $h / 3 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => $w / 3 + $spacing, 'top' => ($h / 3 + $spacing) * 2, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => ($w / 3 + $spacing) * 2, 'top' => 0, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => ($w / 3 + $spacing) * 2, 'top' => $h / 2 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing),); break;
//       case 8: $positions = array (array ('left' => 0, 'top' => 0, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => 0, 'top' => $h / 3 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => 0, 'top' => ($h / 3 + $spacing) * 2, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => $w / 3 + $spacing, 'top' => 0, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => $w / 3 + $spacing, 'top' => $h / 2 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => ($w / 3 + $spacing) * 2, 'top' => 0, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => ($w / 3 + $spacing) * 2, 'top' => $h / 3 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => ($w / 3 + $spacing) * 2, 'top' => ($h / 3 + $spacing) * 2, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing),); break;
      
//       default: case 9: $positions = array (array ('left' => 0, 'top' => 0, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => 0, 'top' => $h / 3 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => 0, 'top' => ($h / 3 + $spacing) * 2, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => $w / 3 + $spacing, 'top' => 0, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => $w / 3 + $spacing, 'top' => $h / 3 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => $w / 3 + $spacing, 'top' => ($h / 3 + $spacing) * 2, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => ($w / 3 + $spacing) * 2, 'top' => 0, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => ($w / 3 + $spacing) * 2, 'top' => $h / 3 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => ($w / 3 + $spacing) * 2, 'top' => ($h / 3 + $spacing) * 2, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing),); break;
//     }

//     for ($i = 0; $i < count ($positions); $i++)
//       $newImage->compositeImage (Thumbnail::createImagick ($files[$i])->adaptiveResizeQuadrant ($positions[$i]['width'], $positions[$i]['height'])->getImage (), Imagick::COMPOSITE_DEFAULT, $positions[$i]['left'], $positions[$i]['top']);

//     return $newImage->writeImages ($save, $rawData);
//   }
// }