<?php

namespace M;

abstract class Uploader {
  protected static $driver = null;
  protected static $baseDirs = ['upload'];
  protected static $tmpDir = null;
  protected static $baseUrl = '';

  public static function setDriver($driver) {
    is_string($driver) && self::$driver = $driver;
  }

  public static function setTmpDir($tmpDir) {
    is_writable($tmpDir) && self::$tmpDir = rtrim($tmpDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
  }

  public static function setBaseDirs($baseDirs) {
    is_array($baseDirs) && self::$baseDirs = $baseDirs;
  }

  public static function setBaseUrl($baseUrl) {
    is_string($baseUrl) && self::$baseUrl = rtrim($baseUrl, '/') . '/';
  }

  public static function bind ($orm, $column) {
    $class = get_called_class();
    return new $class($orm, $column);
  }

  protected $orm = null;
  protected $column = null;
  protected $value = null;

  public function __construct ($orm, $column) {
    in_array($column, array_keys ($attrs = $orm->getAttrs())) || \_M\Config::error('[Uploader] Class 「' . get_class($orm) . '」 無 「' . $column . '」 欄位。');
    array_key_exists($this->uniqueColumn(), $attrs)           || \_M\Config::error('[Uploader] Class 「' . get_class($orm) . '」 無 「' . $this->uniqueColumn() . '」 欄位。');
    self::$tmpDir !== null                                    || \_M\Config::error('[Uploader] 尚未設定 Tmp 目錄。');
    is_writable(self::$tmpDir)                                || \_M\Config::error('[Uploader] Tmp 目錄沒有權限寫入。tmpDir：' . self::$tmpDir);
    self::$driver !== null                                    || \_M\Config::error('[Uploader] 尚未設定 Driver 目錄。');
    in_array(self::$driver, ['s3', 'local'])                  || \_M\Config::error('[Uploader] Driver 只允許 s3、local。driver：' . self::$driver);

    $this->orm = $orm;
    $this->column = $column;
    $this->value = $orm->$column;
    $orm->$column = $this;
  }

  protected function uniqueColumn () {
    return 'id';
  }
  public function __toString () {
    return (string)$this->value;
  }

  public function url($key = '') {
    $path = $this->path($key);

    switch (Uploader::$driver) {
      case 'local':
        return $path ? self::$baseUrl . implode('/', $path) : $this->d4Url();
        break;

      case 's3':
        return $path ? self::$baseUrl . implode('/', $path) : $this->d4Url();
        break;
    }
  }

  public function path($fileName = '') {
    
    switch (Uploader::$driver) {
      case 'local':
        return $fileName ? array_merge(self::$baseDirs, $this->getSavePath(), [$fileName]) : [];
        break;

      case 's3':
        return $fileName ? array_merge ($this->getBaseDirectory (), $this->getSavePath (), array ($fileName)) : array ();
        break;
    }
  }

  public function getSavePath() {
    return is_numeric($id = $this->orm->getAttrs($this->uniqueColumn(), 0)) ? array_merge([$this->orm->getTableName(), $this->column], str_split(sprintf('%016s', dechex($id)), 2)) : [$this->orm->getTableName(), $this->column];
  }

  protected function d4Url() {
    return '';
  }
  
  protected function getRandomName () {
    return md5 (uniqid (mt_rand (), true));
  }

  public function put($fileInfo) {
    if (!($fileInfo && (is_array($fileInfo) || (is_string($fileInfo) && file_exists($fileInfo))))) {
      \_M\Config::log('[Uploader] put 格式有誤(1)。');
      return false;
    }

    $isUseMoveUploadedFile = is_array($fileInfo);

    if ($isUseMoveUploadedFile) {
      foreach (array ('name', 'tmp_name', 'type', 'error', 'size') as $key)
        if (!array_key_exists($key, $fileInfo)) {
          \_M\Config::log('[Uploader] put 格式有誤(2)。');
          return false;
        }

      $name = $fileInfo['name'];
    } else {
      $name = basename($fileInfo);
      $fileInfo = ['name' => 'file', 'tmp_name' => $fileInfo, 'type' => '', 'error' => '', 'size' => '1'];
    }

    $name = preg_replace("/[^a-zA-Z0-9\\._-]/", "", $name);
    $format = ($format = pathinfo($name, PATHINFO_EXTENSION)) ? '.' . $format : '';
    $name = ($name = pathinfo($name, PATHINFO_FILENAME)) ? $name . $format : $this->getRandomName() . $format;

    if (!$temp = $this->moveOriFile($fileInfo, $isUseMoveUploadedFile)) {
      \_M\Config::log('[Uploader] 搬移至暫存資料夾時發生錯誤。');
      return false;
    }

    if (!$savePath = $this->verifySavePath()) {
      \_M\Config::log('[Uploader] 確認儲存路徑發生錯誤。');
      return false;
    }

    if (!$result = $this->moveFileAndUploadColumn($temp, $savePath, $name)) {
      \_M\Config::log('[Uploader] 搬移預設位置時發生錯誤。');
      return false;
    }

    return true;
  }

  private function moveOriFile($fileInfo, $isUseMoveUploadedFile) {
    $temp = self::$tmpDir . 'uploader_' . $this->getRandomName();

    if ($isUseMoveUploadedFile)
      @move_uploaded_file($fileInfo['tmp_name'], $temp);
    else
      @rename($fileInfo['tmp_name'], $temp);

    umaskChmod($temp, 0777);

    if (!file_exists ($temp)) {
      \_M\Config::log('[Uploader] moveOriFile 移動檔案失敗。Path：' . $temp);
      return false;
    }

    return $temp;
  }

  private function verifySavePath() {
    switch (Uploader::$driver) {
      case 'local':

        if (!is_writable($path = implode (DIRECTORY_SEPARATOR, Uploader::$baseDirs))) {
          \_M\Config::log('[Uploader] verifySavePath 資料夾不能儲存。Path：' . $path);
          return false;
        }

        if (!file_exists ($tmp = implode (DIRECTORY_SEPARATOR, $path = array_merge (Uploader::$baseDirs, $this->getSavePath()))))
          umaskMkdir($tmp, 0777, true);

        if (!is_writable($tmp)) {
          \_M\Config::log('[Uploader] verifySavePath 資料夾不能儲存。Path：' . $tmp);
          return false;
        }

        return $path;
        break;

      case 's3':
        // return array_merge ($this->getBaseDirectory (), $this->getSavePath ());
        break;
    }
    return false;
  }
  
  protected function moveFileAndUploadColumn($temp, $savePath, $oriName) {
    switch (Uploader::$driver) {
      case 'local':
        if (!@rename($temp, $path = implode(DIRECTORY_SEPARATOR, $savePath) . DIRECTORY_SEPARATOR . $oriName)) {
          \_M\Config::log('[Uploader] moveFileAndUploadColumn rename 搬移預設位置時發生錯誤。Path：' . $path);
          return false;
        }

        if (!$this->uploadColumnAndUpload('')) {
          \_M\Config::log('[Uploader] moveFileAndUploadColumn uploadColumnAndUpload = 0 錯誤。');
          return false;
        }

        if (!$this->uploadColumnAndUpload($oriName)) {
          \_M\Config::log('[Uploader] moveFileAndUploadColumn uploadColumnAndUpload = ' . $oriName . ' 錯誤。');
          return false;
        }

        return true;
        break;

      case 's3':
        // return Uploader::s3ExceptionRetuen ('putObject', $temp, $this->getS3Bucket (), implode ('/', $savePath) . '/' . $oriName) && $this->uploadColumnAndUpload ('') ? $this->uploadColumnAndUpload ($oriName) && @unlink ($temp) : Uploader::error ('[Uploader] moveFileAndUploadColumn 搬移預設位置時發生錯誤。');
        break;
    }
    return false;
  }
  protected function uploadColumnAndUpload($value, $isSave = true) {
    if (!$this->cleanOldFile ()) {
      \_M\Config::log('[Uploader] uploadColumnAndUpload 清除值發生錯誤。');
      return false;
    }

    return $isSave ? $this->uploadColumn($value) : true;
  }

  protected function cleanOldFile() {
    switch (Uploader::$driver) {
      case 'local':
        if ($paths = $this->getAllPaths())
          foreach ($paths as $path)
            if (is_writable($path = implode (DIRECTORY_SEPARATOR, $path)))
              if (!@unlink($path)) {
                \_M\Config::log('[Uploader] cleanOldFile 清除檔案發生錯誤。Path：' . $path);
                return false;
              }
        return true;
        break;

      case 's3':
        // if ($paths = $this->getAllPaths ())
        //   foreach ($paths as $path)
        //     if (!Uploader::s3ExceptionRetuen ('deleteObject', $this->getS3Bucket (), implode ('/', $path)))
        //       return Uploader::error ('[Uploader] cleanOldFile 清除檔案發生錯誤。Path：' . $path);
        // return true;
        break;
    }
    return false;
  }
  public function getAllPaths () {
    if (!(string)$this->value)
      return array ();

    return [array_merge (self::$baseDirs, $this->getSavePath(), [(string)$this->value])];
  }
  protected function uploadColumn($value) {
    $column = $this->column;
    $this->orm->$column = $value;

    if (!$this->orm->save())
      return false;

    $this->value = $value;
    $this->orm->$column = $this;
    return true;
  }











//   public function __construct ($val) {
//     $this->val = $val;

// // echo '<meta http-equiv="Content-type" content="text/html; charset=utf-8" /><pre>';
// // var_dump (__DIR__);
// // exit ();

//     // Uploader::$driver = \_M\Config::getUploaderDriver();
//   }

//   pu

  // private static $debug = false;

  // private $driverConfigs = array ();
  // protected $tmpDir = null;




  // protected function getS3Bucket () {
  //   return $this->driverConfigs['bucket'];
  // }

  // protected function getDriver () {
  //   return config ('uploader', 'driver');
  // }


  // public static function mustError () {
  //   throw new UploaderException (call_user_func_array ('sprintf', func_get_args ()));
  // }

  // public static function s3ExceptionRetuen () {
  //   if (!(class_exists ('S3') && ($args = func_get_args ()) && ($method = array_shift ($args)) && is_callable (array ('S3', $method))))
  //     return Uploader::error ('S3 物件未初始，或錯誤的 Method。');

  //   try {
  //     return call_user_func_array (array ('S3', $method), $args);
  //   } catch (S3Exception $e) {
  //     return Uploader::error ($e->getMessage ());
  //   }
  // }

  // public static function error () {
  //   if (self::$debug)
  //     throw new UploaderException (call_user_func_array ('sprintf', func_get_args ()));

  //   Log::error (call_user_func_array ('sprintf', func_get_args ()));
  //   return false;
  // }


  // public function getValue () {
  //   return (string)$this->value;
  // }



  // protected function getBaseDirectory () {
  //   return $this->driverConfigs['base_dir'];
  // }










  // public function cleanAllFiles ($isSave = true) {
  //   return $this->uploadColumnAndUpload ('');
  // }

  public function putUrl($url) {
    $format = strtolower(pathinfo($url, PATHINFO_EXTENSION));
    $temp = downloadWebFile($url, self::$tmpDir . $this->getRandomName () . ($format ? '.' . $format : ''));
    return $temp && $this->put($temp, false) ? file_exists($temp) ? @unlink ($temp) : true : false;
  }
}

class FileUploader extends Uploader {
  public function url ($url ='') {
    return parent::url ('');
  }

  public function path($fileName = '') {
    return parent::path((string)$this->value);
  }
}

class ImageUploader extends Uploader {
  const SYMBOL = '_';
//   private $config = array ();

//   public function __construct ($orm, $column) {
//     parent::__construct ($orm, $column);

//     $this->config = config ('uploader', 'thumbnail');
//   }

  protected function getVersions() {
    return ['' => []];
  }
  private function versions() {
    return ($versions = $this->getVersions()) ? $versions : ['' => []];
  }

  public function path($key = '') {
    return ($versions = $this->versions ()) && isset ($versions[$key]) && ($value = (string)$this->value) && ($fileName = $key . ImageUploader::SYMBOL . $value) ? parent::path($fileName) : array ();
  }

//   public function getAllPaths () {
//     if (!($versions = $this->versions ()) && !Uploader::error ('[ImageUploader] getAllPaths Versions 格式錯誤。'))
//       return array ();

//     $paths = array ();
//     foreach ($versions as $key => $version)
//       array_push ($paths, array_merge ($this->getBaseDirectory (), $this->getSavePath (), array ($key . $this->config['separate_symbol'] . $this->getValue ())));

//     return $paths;
//   }

//   protected function moveFileAndUploadColumn ($temp, $savePath, $ori_name) {
//     if (!($versions = $this->versions ()) && !Uploader::error ('[ImageUploader] moveFileAndUploadColumn Versions 格式錯誤。'))
//       return false;

//     Load::sysLib ('Thumbnail.php');
//     $method = 'create' . $this->config['driver'];

//     if (!(class_exists ('Thumbnail') && is_callable (array ('Thumbnail', $method))) && !Uploader::error ('[ImageUploader] moveFileAndUploadColumn 錯誤的函式。Method：' . $method))
//       return false;

//     $news = array ();
//     $info = @exif_read_data ($temp);
//     $orientation = $info && isset ($info['Orientation']) ? $info['Orientation'] : 0;

//     try {

//       foreach ($versions as $key => $version) {
//         $image = Thumbnail::$method ($temp);
//         $image->rotate ($orientation == 6 ? 90 : ($orientation == 8 ? -90 : ($orientation == 3 ? 180 : 0)));

//         $name = !isset ($name) ? $this->getRandomName () . ($this->config['auto_add_format'] ? '.' . $image->getFormat () : '') : $name;
//         $new_name = $key . $this->config['separate_symbol'] . $name;

//         $new_path = $this->tmpDir . $new_name;
//         $this->_utility ($image, $new_path, $key, $version) || Uploader::error ('[ImageUploader] moveFileAndUploadColumn 圖像處理失敗。');
//         array_push ($news, array ('name' => $new_name, 'path' => $new_path));
//       }
//     } catch (Exception $e) {
//       return Uploader::error ('[ImageUploader] moveFileAndUploadColumn 圖像處理失敗。Message：' . $e->getMessage ());
//     }

//     count ($news) == count ($versions) || Uploader::error ('[ImageUploader] moveFileAndUploadColumn 不明原因錯誤。');

//     switch ($this->getDriver ()) {
//       case 'local':
//         foreach ($news as $new)
//           if (!@rename ($new['path'], FCPATH . implode (DIRECTORY_SEPARATOR, $savePath) . DIRECTORY_SEPARATOR . $new['name']))
//             return Uploader::error ('[ImageUploader] moveFileAndUploadColumn 不明原因錯誤。');
//         return self::uploadColumnAndUpload ('') && self::uploadColumnAndUpload ($name) && @unlink ($temp);
//         break;

//       case 's3':
//         foreach ($news as $new)
//           if (!(Uploader::s3ExceptionRetuen ('putObject', $new['path'], $this->getS3Bucket (), implode (DIRECTORY_SEPARATOR, $savePath) . DIRECTORY_SEPARATOR . $new['name']) && @unlink ($new['path'])))
//             return Uploader::error ('[ImageUploader] moveFileAndUploadColumn 不明原因錯誤。');
//         return self::uploadColumnAndUpload ('') && self::uploadColumnAndUpload ($name) && @unlink ($temp);
//         break;
//     }
//     return false;
//   }

//   private function _utility ($image, $save, $key, $version) {
//     if (!$version)
//       return $image->save ($save, true);

//     if (!is_callable (array ($image, $method = array_shift ($version))))
//       return Uploader::error ('[ImageUploader] _utility 無法呼叫的 Method，Method：' . $method);

//     call_user_func_array (array ($image, $method), $version);
//     return $image->save ($save, true);
//   }

//   public function saveAs ($key, $version) {
//     if (!($key && $version) && !Uploader::error ('[ImageUploader] saveAs 參數錯誤。'))
//       return false;

//     Load::sysLib ('Thumbnail.php');
//     $method = 'create' . $this->config['driver'];

//     if (!(class_exists ('Thumbnail') && is_callable (array ('Thumbnail', $method))) && !Uploader::error ('[ImageUploader] moveFileAndUploadColumn 錯誤的函式。Method：' . $method))
//       return false;

//     if (!($versions = $this->versions ()) && !Uploader::error ('[ImageUploader] saveAs 沒有其他版本可用。'))
//       return false;

//     if (isset ($versions[$key]) && !Uploader::error ('[ImageUploader] saveAs 已經有相符合的 key 名稱。'))
//       return false;

//     switch ($this->getDriver ()) {
//       case 'local':
//         foreach (array_keys ($versions) as $oriKey)
//           if (is_readable ($oriPath = FCPATH . implode (DIRECTORY_SEPARATOR, array_merge ($this->getBaseDirectory (), $this->getSavePath (), array ($oriKey . $this->config['separate_symbol'] . ($name = $this->getValue ()))))))
//             break;

//         if (!file_exists ($t = FCPATH . implode (DIRECTORY_SEPARATOR, ($path = array_merge ($this->getBaseDirectory (), $this->getSavePath ())))))
//           Uploader::mkdir ($t, 0777, true);

//         if (!is_writable ($t) && !Uploader::error ('[ImageUploader] saveAs 資料夾不能儲存。Path：' . $path))
//           return @unlink ($t) && false;

//         try {
//           $image = Thumbnail::$method ($oriPath);
//           $path = $t . DIRECTORY_SEPARATOR . $key . $this->config['separate_symbol'] . $name;

//           if (!$this->_utility ($image, $path, $key, $version))
//             return false;

//           return $path;
//         } catch (Exception $e) {
//           return Uploader::error ('[ImageUploader] moveFileAndUploadColumn 圖像處理失敗。Message：' . $e->getMessage ());
//         }
//         break;

//       case 's3':
//         if (!Uploader::s3ExceptionRetuen ('getObject', implode (DIRECTORY_SEPARATOR, array_merge ($path = array_merge ($this->getBaseDirectory (), $this->getSavePath ()), array ($fileName = array_shift (array_keys ($versions)) . $this->config['separate_symbol'] . ($name = $this->getValue ())))), FCPATH . implode (DIRECTORY_SEPARATOR, $fileName = array_merge ($this->getTempDirectory (), array ($fileName)))))
//           return $this->getDebug () ? error ('ImageUploader 錯誤。', '沒有任何的檔案可以被使用。', '請確認 getVersions () 函式內有存在的檔案可被另存。', '請程式設計者確認狀況。') : array ();

//         try {
//           $image = ImageUtility::create ($fileName = FCPATH . implode (DIRECTORY_SEPARATOR, $fileName), null);
//           $newPath = array_merge ($path, array ($newName = $key . $this->config['separate_symbol'] . $name));


//           if ($this->_utility ($image, FCPATH . implode (DIRECTORY_SEPARATOR, $newFileName = array_merge ($this->getTempDirectory (), array ($newName))), $key, $version) && Uploader::s3ExceptionRetuen ('putFile', $newFileName = FCPATH . implode (DIRECTORY_SEPARATOR, $newFileName), $this->getS3Bucket (), implode (DIRECTORY_SEPARATOR, $newPath)) && @unlink ($newFileName) && @unlink ($fileName))
//             return $newPath;
//           else
//             return array ();
//         } catch (Exception $e) {
//           return $this->getDebug () ? call_user_func_array ('error', $e->getMessages ()) : '';
//         }
//         break;
//     }

//     return false;
//   }

//   public function toImageTag ($key = '', $attrs = array ()) { // $attrs = array ('class' => 'i')
//     return ($url = ($url = $this->url ($key)) ? $url : $this->d4Url ()) ? '<img src="' . $url . '"' . ($attrs ? ' ' . implode (' ', array_map (function ($key, $value) { return $key . '="' . $value . '"'; }, array_keys ($attrs), $attrs)) : '') . '>' : '';
//   }
//   public function toDivImageTag ($key = '', $attrs = array ()) { // $attrs = array ('class' => 'i')
//     return ($str = $this->toImageTag ($key)) ? '<div' . ($attrs ? ' ' . implode (' ', array_map (function ($key, $value) { return $key . '="' . $value . '"'; }, array_keys ($attrs), $attrs)) : '') . '>' . $str . '</div>' : '';
//   }
}

// class UploaderException extends Exception {}
