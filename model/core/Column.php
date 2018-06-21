<?php

namespace _M;

class Column {
  const STRING    = 1;
  const INTEGER   = 2;
  const DECIMAL   = 3;
  const DATETIME  = 4;
  const DATE      = 5;
  const TIME      = 6;

  static $types = [
    'datetime'  => self::DATETIME,
    'timestamp' => self::DATETIME,
    'date'      => self::DATE,
    'time'      => self::TIME,

    'tinyint'   => self::INTEGER,
    'smallint'  => self::INTEGER,
    'mediumint' => self::INTEGER,
    'int'       => self::INTEGER,
    'bigint'    => self::INTEGER,

    'float'     => self::DECIMAL,
    'double'    => self::DECIMAL,
    'numeric'   => self::DECIMAL,
    'decimal'   => self::DECIMAL,
    'dec'       => self::DECIMAL];

  public $name;
  public $isNullable;
  public $isPrimaryKey;
  public $isAutoIncrement;
  public $rawType;
  public $length;

  public $type;
  public $default;

  protected function __construct($row) {
    $this->name            = $row['field'];
    $this->isNullable      = $row['null'] === 'YES';
    $this->isPrimaryKey    = $row['key'] === 'PRI';
    $this->isAutoIncrement = $row['extra'] === 'auto_increment';

    $this->setRawTypeLength($row);

    $this->setType();
    $this->default = $this->cast($row['default'], false);

  }

  private function setRawTypeLength($row) {
    if ($row['type'] == 'timestamp' || $row['type'] == 'datetime') {
      $this->rawType = 'datetime';
      $this->length  = 19;
    } elseif ($row['type'] == 'date') {
      $this->rawType = 'date';
      $this->length  = 10;
    } elseif ($row['type'] == 'time') {
      $this->rawType = 'time';
      $this->length  = 8;
    } else {
      preg_match('/^([A-Za-z0-9_]+)(\(([0-9]+(,[0-9]+)?)\))?/', $row['type'], $matches);
      $this->rawType = (count($matches) > 0 ? $matches[1] : $row['type']);
      count($matches) < 3 || $this->length = intval($matches[3]);
    }

    $this->rawType == 'integer' && $this->rawType = 'int';
    return $this;
  }

  public function setType() {
    return $this->type = isset(self::$types[$this->rawType]) ? self::$types[$this->rawType] : self::STRING;
  }

  public static function create($row) {
    return new Column(array_change_key_case($row, CASE_LOWER));
  }

  public function cast($val, $checkFormat) {
    if ($val === null)
      return null;

    switch ($this->type) {
      case self::STRING:
        return (string)$val;
      
      case self::INTEGER:
        return static::castIntegerSafely($val);
      
      case self::DECIMAL:
        return (double)$val;

      case self::DATETIME: case self::DATE:
        if (!$val)
          return null;

        $val = DateTime::createByString($val, $this->type);
        $checkFormat && !$val->isFormat() && Config::error($checkFormat);

        return $val;
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