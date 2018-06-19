<?php

namespace _M;

use PDO;
use PDOException;
// use Closure;

class Connection {
  private static $instance = null;
  public static $pdoOptions = [PDO::ATTR_CASE => PDO::CASE_NATURAL, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL, PDO::ATTR_STRINGIFY_FETCHES => false];
  private $connection = null;

  // const DATETIME_TRANSLATE_FORMAT = 'Y-m-d H:i:s';
  // public static $quoteCharacter = '`';

  protected function __construct() {
    $config = Config::getConnection ();
    $config || Config::error('沒有設定 MySQL 連線資訊！');
    
    foreach (['hostName', 'userName', 'password', 'database', 'charSet'] as $key)
      isset($config[$key]) || Config::error('MySQL 連線資訊缺少「' . $key . '」！');

    try {
      $this->connection = new PDO('mysql:host=' . $config['hostName'] . ';dbname=' . $config['database'], $config['userName'], $config['password'], Connection::$pdoOptions);
    } catch (PDOException $e) {

      Config::error($e);
    }

    $this->setEncoding($config['charSet']);
  }

  public static function instance() {
    if (self::$instance)
      return self::$instance;

    return self::$instance = new Connection();
  }

  public function close() {
    $this->connection = null;
  }

  public function setEncoding($charset) {
    $this->query('SET NAMES ?', [$charset]);
    return $this;
  }
  
  public function query($sql, $vals = []) {

    try {
      $sth = $this->connection->prepare((string)$sql);
      $sth || Config::error('執行 Connection prepare 失敗！');

      $sth->setFetchMode(PDO::FETCH_ASSOC);

      $this->execute($sth, $sql, $vals) || Config::error('執行 Connection execute 失敗！');
    } catch (PDOException $e) {
      Config::error($e);
    }

    return $sth;
  }

  private function execute($sth, $sql, $vals) {
    if (!Config::getQueryLogerFunc())
      return $sth->execute($vals);

    $start = microtime(true);
    $valid = $sth->execute($vals);
    $time = number_format((microtime(true) - $start) * 1000, 1);
    Config::queryLog((bool)$valid, $time, $sql, $vals);

    return $valid;
  }

  public function transaction() {
    if (!$this->connection) return false;
    $this->connection->beginTransaction() || Config::error('Transaction 失敗！');
    return true;
  }

  public function commit() {
    if (!$this->connection) return false;
    $this->connection->commit() || Config::error('Commit 失敗！');
    return true;
  }

  public function rollback() {
    if (!$this->connection) return false;
    $this->connection->rollback() || Config::error('Rollback 失敗！');
    return true;
  }



  // public function quoteName($string) {
  //   return $string[0] === static::$quoteCharacter || $string[strlen($string) - 1] === static::$quoteCharacter ? $string : static::$quoteCharacter . $string . static::$quoteCharacter;
  // }


  // public function columns($table) {

  //   $columns = [];
  //   $sth = $this->queryColumnInfo($table);
    
  //   while ($row = $sth->fetch()) {
  //     $c = $this->createColumn($row);
  //     $columns[$c->name] = $c;
  //   }
    
  //   return $columns;
  // }

  // public function stringTodatetime($string) {
  //   $date = date_create($string);

  //   $errors = \DateTime::getLastErrors();

  //   if ($errors['warning_count'] > 0 || $errors['error_count'] > 0)
  //     return null;

  //   return $date;

  //   // $date_class = Config::instance()->get_date_class();

  //   // return $date_class::createFromFormat(
  //   //   static::DATETIME_TRANSLATE_FORMAT,
  //   //   $date->format(static::DATETIME_TRANSLATE_FORMAT),
  //   //   $date->getTimezone()
  //   // );
  // }

  // abstract public function queryColumnInfo($table);

  // //   $config = Config::instance();

  // //   if (strpos($connection_string_or_connection_name, '://') === false)
  // //   {
  // //     $connection_string = $connection_string_or_connection_name ?
  // //       $config->get_connection($connection_string_or_connection_name) :
  // //       $config->get_default_connection_string();
  // //   }
  // //   else
  // //     $connection_string = $connection_string_or_connection_name;

  // //   if (!$connection_string)
  // //     throw new DatabaseException("Empty connection string");

  // //   $fqclass = static::load_adapter_class($info->protocol);


  // // private static function load_adapter_class($adapter)
  // // {
  // //   $class = ucwords($adapter) . 'Adapter';
  // //   $fqclass = 'ActiveRecord\\' . $class;
  // //   $source = __DIR__ . "/adapters/$class.php";

  // //   if (!file_exists($source))
  // //     throw new DatabaseException("$fqclass not found!");

  // //   require_once($source);
  // //   return $fqclass;
  // // }



  // // public function escape($string)
  // // {
  // //   return $this->connection->quote($string);
  // // }

  // // public function insert_id($sequence=null)
  // // {
  // //   return $this->connection->lastInsertId($sequence);
  // // }


  // // public function query_and_fetch_one($sql, &$values=array())
  // // {
  // //   $sth = $this->query($sql, $values);
  // //   $row = $sth->fetch(PDO::FETCH_NUM);
  // //   return $row[0];
  // // }

  // // public function query_and_fetch($sql, Closure $handler)
  // // {
  // //   $sth = $this->query($sql);

  // //   while (($row = $sth->fetch(PDO::FETCH_ASSOC)))
  // //     $handler($row);
  // // }

  // // public function tables()
  // // {
  // //   $tables = array();
  // //   $sth = $this->query_for_tables();

  // //   while (($row = $sth->fetch(PDO::FETCH_NUM)))
  // //     $tables[] = $row[0];

  // //   return $tables;
  // // }

  // // function supports_sequences()
  // // {
  // //   return false;
  // // }

  // // public function get_sequence_name($table, $column_name)
  // // {
  // //   return "{$table}_seq";
  // // }

  // // public function next_sequence_value($sequence_name)
  // // {
  // //   return null;
  // // }


  // // public function date_to_string($datetime)
  // // {
  // //   return $datetime->format(static::$date_format);
  // // }

  // // public function datetime_to_string($datetime)
  // // {
  // //   return $datetime->format(static::$datetime_format);
  // // }




  // // abstract function query_for_tables();


  // // abstract public function native_database_types();

  // // public function accepts_limit_and_order_for_update_and_delete()
  // // {
  // //   return false;
  // }
}
