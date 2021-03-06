<?php

namespace _M;

use PDO;
use PDOException;

class Connection {
  private static $instance = null;
  public static $pdoOptions = [PDO::ATTR_CASE => PDO::CASE_NATURAL, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL, PDO::ATTR_STRINGIFY_FETCHES => false];
  private $connection = null;

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
  
  public function query($sql, $vals = [], $fetchModel = PDO::FETCH_ASSOC) {
    try {
      $sth = $this->connection->prepare((string)$sql);
      $sth || Config::error('執行 Connection prepare 失敗！');
      $sth->setFetchMode($fetchModel);
      $this->execute($sth, $sql, $vals) || Config::error('執行 Connection execute 失敗！');
    } catch (PDOException $e) {
      Config::error($e);
    }

    return $sth;
  }

  private function execute($sth, $sql, $vals) {
    if (Config::noQueryLogerFunc())
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

  public function lastInsertId() {
    return $this->connection->lastInsertId();
  }

  public function rollback() {
    if (!$this->connection) return false;
    $this->connection->rollback() || Config::error('Rollback 失敗！');
    return true;
  }
}
