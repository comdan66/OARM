<?php

namespace _M;

class Column {
  // const STRING    = 1;
  // const INTEGER   = 2;
  // const DECIMAL   = 3;
  // const DATETIME  = 4;
  // const DATE      = 5;
  // const TIME      = 6;
  // const UPLOADER  = 7;

  // static $types = [
  //   'datetime'  => self::DATETIME,
  //   'timestamp' => self::DATETIME,
  //   'date'      => self::DATE,
  //   'time'      => self::TIME,

  //   'tinyint'   => self::INTEGER,
  //   'smallint'  => self::INTEGER,
  //   'mediumint' => self::INTEGER,
  //   'int'       => self::INTEGER,
  //   'bigint'    => self::INTEGER,

  //   'float'     => self::DECIMAL,
  //   'double'    => self::DECIMAL,
  //   'numeric'   => self::DECIMAL,
  //   'decimal'   => self::DECIMAL,
  //   'dec'       => self::DECIMAL,

  //   'uploader'  => self::UPLOADER];

  public $name;
  public $isNullable;
  public $isPrimaryKey;
  public $isAutoIncrement;
  public $uploaders;
  public $type;

  protected function __construct($row, $className) {
    $this->name            = $row['field'];
    $this->isNullable      = $row['null'] === 'YES';
    $this->isPrimaryKey    = $row['key'] === 'PRI';
    $this->isAutoIncrement = $row['extra'] === 'auto_increment';
    $this->setRawTypeLength($row);
  }

  private function setRawTypeLength($row) {
    if ($row['type'] == 'timestamp' || $row['type'] == 'datetime') {
      $this->type = 'datetime';
    } elseif ($row['type'] == 'date') {
      $this->type = 'date';
    } elseif ($row['type'] == 'time') {
      $this->type = 'time';
    } else {
      preg_match('/^([A-Za-z0-9_]+)(\(([0-9]+(,[0-9]+)?)\))?/', $row['type'], $matches);
      $this->type = (count($matches) > 0 ? $matches[1] : $row['type']);
    }

    $this->type == 'integer' && $this->type = 'int';
    return $this;
  }

  public static function create($row, $className) {
    return new Column(array_change_key_case($row, CASE_LOWER), $className);
  }

  public function cast($val, $checkFormat) {
    if ($val === null)
      return null;
    
    switch ($this->type) {
      case 'tinyint': case 'smallint': case 'mediumint': case 'int': case 'bigint':
        return static::castIntegerSafely($val);
      
      case 'float': case 'double': case 'numeric': case 'decimal': case 'dec':
        return (double)$val;

      case 'datetime': case 'timestamp': case 'date': case 'time':
        if (!$val)
          return null;

        $val = DateTime::createByString($val, $this->type);
        $checkFormat && !$val->isFormat() && Config::error($checkFormat);
        return $val;

      default:
        if ($val instanceof \M\Uploader)
          return $val;
        return (string)$val;
    }
    return $val;
  }

  public static function castIntegerSafely($val) {
    if (is_int($val))
      return $val;
    elseif (is_numeric($val) && floor($val) != $val)
      return (int)$val;
    elseif (is_string($val) && is_float($val + 0))
      return (string) $val;
    elseif (is_float($val) && $val >= PHP_INT_MAX)
      return number_format($val, 0, '', '');
    else
      return (int)$val;
  }
}