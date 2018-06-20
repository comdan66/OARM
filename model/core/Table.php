<?php

namespace _M;


class Table {
  private static $caches = [];
  private $className;
  
  public $tableName;
  public $columns;
  public $primaryKeys;

  protected function __construct($className) {
    $this->setTableName($className)
         ->getMetaData()
         ->setPrimaryKeys();
  }
  private function setTableName($className){
    $this->className = $className;
    $this->tableName = isset($className::$tableName) ? $className::$tableName : \M\deNamespace($className);
    return $this;
  }

  private function getMetaData () {
    $this->columns = [];
    $sth = Connection::instance()->query("SHOW COLUMNS FROM " . Config::quoteName($this->tableName));
    
    while ($row = $sth->fetch())
      if ($c = Column::create($row))
        $this->columns[$c->name] = $c;

    return $this;
  }

  private function setPrimaryKeys() {
    $className = $this->className;
    $this->primaryKeys = isset($className::$primaryKeys) ? is_array($className::$primaryKeys) ? $className::$primaryKeys : [$className::$primaryKeys] : \M\modelsColumn(array_values(array_filter($this->columns, function ($column) { return $column->isPrimaryKey; })), 'name');
    return $this;
  }

  public static function instance($className) {
    return isset(self::$caches[$className]) ? self::$caches[$className] : self::$caches[$className] = new Table($className);
  }
 
  public function find($options) {
    $sql = SqlBuilder::create(Config::quoteName($this->tableName))
                     ->setSelectOption($options);

    return $this->findBySql($sql, $sql->getValues(), isset($options['readonly']) ? (bool)$options['readonly'] : false);
  }

  public function processDataToStr($data) {
    foreach ($data as $name => &$value)
      if ($value instanceof \DateTime)
        if (isset($this->columns[$name]) && $this->columns[$name]->type == Column::DATE)
          $hash[$name] = $value->format(\_M\Config::DATE_FORMAT);
        else
          $hash[$name] = $value->format(\_M\Config::DATETIME_FORMAT);
      else
        $hash[$name] = $value;

    return $hash;
  }

  private function mergeWherePrimaryKeys($primaryKeys) {
    $where = \Where::create();
    foreach ($primaryKeys as $primaryKey => $value)
      $where->and($primaryKey . ' = ?', $value);

    return $where;
  }

  public function delete($primaryKeys) {
    $data = $this->processDataToStr($primaryKeys);
    
    $where = $this->mergeWherePrimaryKeys($primaryKeys);

    $sql = SqlBuilder::create(Config::quoteName($this->tableName))
                     ->delete()
                     ->where($where->toArray())
                     ->bindValues();
    
    return Connection::instance()->query($sql, $sql->getValues());
  }

  public function insert($data) {
    $data = $this->processDataToStr($data);

    $sql = SqlBuilder::create(Config::quoteName($this->tableName))
                     ->insert($data);

    return Connection::instance()->query($sql, array_values($data));
  }
  public function update($data, $primaryKeys) {
    $data = $this->processDataToStr($data);

    $where = $this->mergeWherePrimaryKeys($primaryKeys);

    $sql = SqlBuilder::create(Config::quoteName($this->tableName))
                     ->update($data)
                     ->where($where->toArray())
                     ->bindValues();

    return Connection::instance()->query($sql, $sql->getValues());
  }

  public function findBySql($sql, $values = [], $readonly = false, $includes = []) {
    $list = $attrs = [];

    $sth = Connection::instance()->query($sql, $values);

    while ($row = $sth->fetch()) {
      $model = new $this->className($row);
      $model->setIsNew(false);
      $model->setTableName($this->tableName);
      $model->setClassName($this->className);
      $model->setIsReadonly($readonly);

      array_push($list, $model);
    }

    return $list;
  }

}