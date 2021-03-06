<?php

namespace M;

if (defined('MODEL_LOADED'))
  return;

define('MODEL_LOADED', true);

require_once 'Func.php';
require_once 'Uploader.php';
require_once 'core/Config.php';

$modelRecord = [];

Class Model {

  private static $validOptions = ['where', 'limit', 'offset', 'order', 'select', 'group', 'having', 'include', 'readonly'];

  public static function one() {
    return call_user_func_array(['static', 'find'], array_merge(['one'], func_get_args()));
  }
  
  public static function first() {
    return call_user_func_array(['static', 'find'], array_merge(['first'], func_get_args()));
  }
  
  public static function last() {
    return call_user_func_array(['static', 'find'], array_merge(['last'], func_get_args()));
  }
  
  public static function all() {
    return call_user_func_array(['static', 'find'], array_merge(['all'], func_get_args()));
  }

  public static function count($options = []) {
    $obj = call_user_func_array(['static', 'find'], array_merge(['one'], [array_merge($options, ['select' => 'COUNT(*)', 'readonly' => true])]))->attrs();
    return intval($obj = array_shift($obj));
  }

  public static function find() {
    $className = get_called_class();
    
    $options = func_get_args();
    $options || \_M\Config::error('請給予 ' . $className . ' 查詢條件！');

    // 過濾 method
    is_string($method = array_shift($options)) || \_M\Config::error('請給予 Find 查詢類型！');
    in_array($method, $tmp = ['one', 'first', 'last', 'all']) || \_M\Config::error('Find 僅能使用 ' . implode('、', $tmp) . ' ' . $tmp .'種查詢條件！');
    
    // Model::find('one', Where::create('id = ?', 2));
    isset($options[0]) && $options[0] instanceof \Where && $options[0] = ['where' => $options[0]->toArray()];

    // Model::find('one', 'id = ?', 2);
    isset($options[0]) && is_string($options[0]) && $options[0] = ['where' => $options];

    $options = $options ? array_shift($options) : [];
    
    // Model::find('one', ['where' => 'id = 2']);
    isset($options['where']) && is_string($options['where']) && $options['where'] = [$options['where']];
    
    // Model::find('one', ['where' => Where::create('id = ?', 2)]);
    isset($options['where']) && $options['where'] instanceof \Where && $options['where'] = $options['where']->toArray();

    $method == 'last' && $options['order'] = isset ($options['order']) ? self::reverseOrder ((string)$options['order']) : implode(' DESC, ', static::table()->primaryKeys) . ' DESC';

    // 過濾對的 key by validOptions
    
    $options && $options = array_intersect_key($options, array_flip(self::$validOptions));

    in_array ($method, ['one', 'first']) && $options = array_merge($options, ['limit' => 1, 'offset' => 0]);

    $list = static::table()->find($options);
    
    return $method != 'all' ? (isset($list[0]) ? $list[0] : null) : $list;
  }

  private static function reverseOrder($order) {
    return trim($order) ? implode(', ', array_map(function($part) {
      $v = trim(strtolower($part));
      return strpos($v,' asc') === false ? strpos($v,' desc') === false ? $v . ' DESC' : preg_replace('/desc/i', 'ASC', $v) : preg_replace('/asc/i', 'DESC', $v);
    }, explode(',', $order))) : 'order';
  }

  public static function table() {
    return \_M\Table::instance(get_called_class());
  }

  private $attrs = [];
  private $className = null;
  private $tableName = null;
  
  private $dirty = [];
  private $isNew = true;

  private $relations = [];
  private $isReadonly = false;

  public function __construct($attrs) {
    $this->setAttrs($attrs)
         ->cleanFlagDirty();
  }

  public function setClassName($className) {
    $this->className = $className;
    return $this;
  }

  public function setTableName($tableName) {
    $this->tableName = $tableName;
    return $this;
  }

  public function attrs($key = null, $d4 = null) {
    return $key !== null ? array_key_exists($key, $this->attrs) ? $this->attrs[$key] : $d4 : $this->attrs;
  }

  public function getTableName() {
    return $this->tableName;
  }

  public function setIsNew($isNew) {
    if ($this->isNew = $isNew)
      array_map([$this, 'flagDirty'], array_keys($this->attrs));
    return $this;
  }

  public function setIsReadonly($isReadonly) {
    $this->isReadonly = $isReadonly;
    return $this;
  }

  public function columns() {
    return $this->attrs;
  }

  private function setAttrs($attrs) {
    foreach ($attrs as $name => $value)
      if (isset(static::table()->columns[$name]))
        $this->setAttr($name, $value);
      else
        $this->attrs[$name] = $value;

    return $this;
  }

  public function primaryKeysWithValues() {
    $tmp = [];
    
    foreach (static::table()->primaryKeys as $primaryKey)
      if (array_key_exists($primaryKey, $this->attrs))
        $tmp[$primaryKey] = $this->$primaryKey;
      else
        \_M\Config::error('找不到 Primary Key 的值，請注意是否未 SELECT Primary Key！');
    return $tmp;
  }

  public static function relations($key, $options, $models, $include) {
    $methodOne = in_array($key, ['hasOne', 'belongToOne']);

    if (!$models)
      return $methodOne ? null : [];

    is_string($options) && $options = ['model' => $options];
    
    $className = '\\M\\' . $options['model'];
    $tableName = $models[0]->getTableName();

    $primaryKey = !isset($options['primaryKey']) ? 'id' : $options['primaryKey'];


    $by = null;
    if (isset($options['by']))
      foreach (['hasOne', 'hasMany', 'belongToOne', 'belongToMany'] as $val)
        if (isset($className::$$val) && ($tmp = $className::$$val) && isset($tmp[$options['by']]))
          $by = isset($tmp[$options['by']]['model']) ? $tmp[$options['by']] : array_merge($tmp[$options['by']], ['model' => $options['by']]);

    if ($by) {

      $tableName = isset($className::$tableName) ? $className::$tableName : $options['model'];

      $byClassName  = '\\M\\' . ($byModelName = $by['model']);
      $byTableName  = isset($byClassName::$tableName) ? $byClassName::$tableName : $byModelName;
      $byForeignKey  = array_key_exists('foreignKey', $by) ? $by['foreignKey'] : $tableName . 'Id';
      $byPrimaryKey  = array_key_exists('primaryKey', $by) ? $by['primaryKey'] : 'id';
      $byReadonly  = isset($by['readonly']) && $by['readonly'];
      
      $foreignKey = !isset($options['foreignKey']) ? lcfirst($models[0]->getTableName()) . 'Id' : $options['foreignKey'];
      $primaryKeys = array_unique(array_map(function ($model) use ($primaryKey) { return $model->$primaryKey; }, $models));

      $sql = 'SELECT `' . $tableName . '`.*,TagArticleMapping.articleId FROM `' . $tableName . '` INNER JOIN `' . $byTableName . '` ON(`' . $tableName . '`.`' . $byPrimaryKey . '` = `' . $byTableName . '`.`' . $byForeignKey . '`) WHERE `' . $byTableName . '`.`' . $foreignKey . '`IN(' . implode(',', array_map(function() { return '?'; }, $primaryKeys)) . ')';
      $relations = $className::selectQuery($sql, $primaryKeys);

      $tmps = [];

      foreach ($relations as $relation) {
        $tmp = $relation[$foreignKey];
        unset($relation[$foreignKey]);

        $obj = new $className($relation);
        $obj->setIsNew(false)->setTableName($tableName)->setClassName($className)->setIsReadonly($byReadonly)->setUploadBind();

        if (isset($tmps[$tmp]))
          array_push($tmps[$tmp], $obj);
        else
          $tmps[$tmp] = [$obj];
      }

      foreach ($models as $model)
        if (isset($tmps[$model->$primaryKey]))
          $model->relations[$include] = $methodOne ? $tmps[$model->$primaryKey] ? $tmps[$model->$primaryKey][0] : null : $tmps[$model->$primaryKey];
        else
          $model->relations[$include] = $methodOne ? null : [];

    } else if (in_array($key, ['belongToOne', 'belongToMany'])) {
      $foreignKey = !isset($options['foreignKey']) ? lcfirst($options['model']) . 'Id' : $options['foreignKey'];
      $options && $options = array_intersect_key($options, array_flip(self::$validOptions));
      
      $foreignKeys = array_unique(array_map(function ($model) use ($foreignKey) { return $model->$foreignKey; }, $models));
      
      $where = \Where::create($primaryKey . ' IN (?)', $foreignKeys);
      $options['where'] = isset($options['where']) ? \Where::create($options['where'])->and($where) : $where;
      isset($options['select']) && $options['select'] .= ',' . $primaryKey;
      
      $relations = $className::all($options);
      
      $tmps = [];
      foreach ($relations as $relation) 
        if (isset($tmps[$relation->$primaryKey]))
          array_push($tmps[$relation->$primaryKey], $relation);
        else
          $tmps[$relation->$primaryKey] = [$relation];

      foreach ($models as $model)
        if (isset($tmps[$model->$foreignKey]))
          $model->relations[$include] = $methodOne ? $tmps[$model->$foreignKey] ? $tmps[$model->$foreignKey][0] : null : $tmps[$model->$foreignKey];
        else
          $model->relations[$include] = $methodOne ? null : [];
    } else {
      $foreignKey = !isset($options['foreignKey']) ? lcfirst($tableName) . 'Id' : $options['foreignKey'];

      $options && $options = array_intersect_key($options, array_flip(self::$validOptions));

      $primaryKeys = array_unique(array_map(function ($model) use ($primaryKey) { return $model->$primaryKey; }, $models));

      $where = \Where::create($foreignKey . ' IN (?)', $primaryKeys);
      $options['where'] = isset($options['where']) ? \Where::create($options['where'])->and($where) : $where;
      isset($options['select']) && $options['select'] .= ',' . $foreignKey;

      $relations = $className::all($options);

      $tmps = [];

      foreach ($relations as $relation) 
        if (isset($tmps[$relation->$foreignKey]))
          array_push($tmps[$relation->$foreignKey], $relation);
        else
          $tmps[$relation->$foreignKey] = [$relation];

      foreach ($models as $model)
        if (isset($tmps[$model->$primaryKey]))
          $model->relations[$include] = $methodOne ? $tmps[$model->$primaryKey] ? $tmps[$model->$primaryKey][0] : null : $tmps[$model->$primaryKey];
        else
          $model->relations[$include] = $methodOne ? null : [];
    }

    $tmps = $primaryKeys = $foreignKey = null;
  }

  public function relation($key, $options) {
    is_string($options) && $options = ['model' => $options];
    
    $className = '\\M\\' . ($modelName = $options['model']);
    $isBelong = in_array($key, ['belongToOne', 'belongToMany']);

    $foreignKey = !isset($options['foreignKey']) ? ($isBelong ? lcfirst($options['model']) : lcfirst($this->tableName)) . 'Id' : $options['foreignKey'];
    $primaryKey = !isset($options['primaryKey']) ? 'id' : $options['primaryKey'];

    $by = null;
    if (isset($options['by']))
      foreach (['hasOne', 'hasMany', 'belongToOne', 'belongToMany'] as $val)
        if (isset($className::$$val) && ($tmp = $className::$$val) && isset($tmp[$options['by']]))
          $by = isset($tmp[$options['by']]['model']) ? $tmp[$options['by']] : array_merge($tmp[$options['by']], ['model' => $options['by']]);

    $options && $options = array_intersect_key($options, array_flip(self::$validOptions));
    
    if ($isBelong)
      $options['where'] = isset($options['where']) ? \Where::create($options['where'])->and($primaryKey . ' = ?', $this->$foreignKey) : \Where::create($primaryKey . ' = ?', $this->$foreignKey);
    else
      $options['where'] = isset($options['where']) ? \Where::create($options['where'])->and($foreignKey . ' = ?', $this->$primaryKey) : \Where::create($foreignKey . ' = ?', $this->$primaryKey);
    
    $method = in_array($key, ['hasOne', 'belongToOne']) ? 'one' : 'all';


    if ($by === null)
      return $className::$method($options);

    $tableName = isset($className::$tableName) ? $className::$tableName : $modelName;
    $byClassName  = '\\M\\' . ($byModelName = $by['model']);
    $byTableName  = isset($byClassName::$tableName) ? $byClassName::$tableName : $byModelName;
    $byForeignKey  = array_key_exists('foreignKey', $by) ? $by['foreignKey'] : lcfirst($tableName) . 'Id';
    $byPrimaryKey  = array_key_exists('primaryKey', $by) ? $by['primaryKey'] : 'id';
    $byReadonly  = isset($by['readonly']) && $by['readonly'];

    $sql = 'SELECT `' . $tableName . '`.* FROM `' . $tableName . '` INNER JOIN `' . $byTableName . '` ON(`' . $tableName . '`.`' . $byPrimaryKey . '` = `' . $byTableName . '`.`' . $byForeignKey . '`) WHERE `' . $byTableName . '`.`' . $foreignKey . '`=' . $this->$primaryKey;

    return array_map(function ($row) use($className, $tableName, $byReadonly) {
      $obj = new $className($row);

      return $obj->setIsNew(false)
                 ->setTableName($tableName)
                 ->setClassName($className)
                 ->setIsReadonly($byReadonly)
                 ->setUploadBind();

    }, $className::selectQuery($sql));
  }

  public function save() {
    return $this->isNew ? $this->insert() : $this->update();
  }

  public function __isset($name) {
    return array_key_exists($name, $this->attrs);
  }

  public function &__get($name) {
    if (array_key_exists($name, $this->attrs))
      return $this->attrs[$name];
    
    $className = $this->className;

    if (array_key_exists($name, $this->relations))
      return $this->relations[$name];

    $relation = [];
    foreach (['hasOne', 'hasMany', 'belongToOne', 'belongToMany'] as $val)
      if (isset($className::$$val) && ($tmp = $className::$$val) && isset($tmp[$name])) {
        $this->relations[$name] = $this->relation($val, $tmp[$name]);
        return $this->relations[$name];
      }

    \_M\Config::error($this->className . ' 找不到名稱為「' . $name . '」此物件變數！');
  }

  public function __set($name, $value) {
    if ($this->isReadonly)
      \_M\Config::error('此物件是唯讀的狀態！');

    if (array_key_exists($name, $this->attrs))
      if (isset(static::table()->columns[$name]))
        return $this->setAttr($name, $value);
      else
        return $this->attrs[$name] = $value;

    \_M\Config::error($this->className . ' 找不到名稱為「' . $name . '」此物件變數！');
  }


  private function setAttr($name, $value) {
    $this->attrs[$name] = \M\cast(static::table()->columns[$name]['type'], $value, $this->className . ' 的欄位「' . $name . '」給予的值格式錯誤，請給予「' . static::table()->columns[$name]['type'] . '」的格式！');
    $this->flagDirty($name);
    return $value;
  }

  public function cleanFlagDirty() {
    $this->dirty = [];
    return $this;
  }

  public function flagDirty($name = null) {
    $this->dirty || $this->cleanFlagDirty();
    $this->dirty[$name] = true;
    return $this;
  }

  public function delete() {
    $this->isReadonly && \_M\Config::error('此資料為不可寫入(readonly)型態！');

    $primaryKeys = $this->primaryKeysWithValues();
    $primaryKeys || \_M\Config::error('不能夠更新，因為 ' . $this->tableName . ' 尚未設定 Primary Key！');

    static::table()->delete($primaryKeys);
    return true;
  }

  public function update() {
    $this->isReadonly && \_M\Config::error('此資料為不可寫入(readonly)型態！');

    isset(static::table()->columns['updatedAt']) && array_key_exists('updatedAt', $this->attrs) && !array_key_exists('updatedAt', $this->dirty) && $this->setAttr ('updatedAt', \date(\_M\Config::DATETIME_FORMAT));

    if ($dirty = array_intersect_key($this->attrs, $this->dirty)) {

      $primaryKeys = $this->primaryKeysWithValues();
      $primaryKeys || \_M\Config::error('不能夠更新，因為 ' . $this->tableName . ' 尚未設定 Primary Key！');

      static::table()->update($dirty, $primaryKeys);
    }

    return true;
  }

  public function insert() {
    $this->isReadonly && \_M\Config::error('此資料為不可寫入(readonly)型態！');

    isset(static::table()->columns['createdAt']) && !array_key_exists('createdAt', $this->attrs) && $this->setAttr ('createdAt', \date(\_M\Config::DATETIME_FORMAT));
    isset(static::table()->columns['updatedAt']) && !array_key_exists('updatedAt', $this->attrs) && $this->setAttr ('updatedAt', \date(\_M\Config::DATETIME_FORMAT));
  
    $this->attrs = array_intersect_key($this->attrs, static::table()->columns);

    $table = static::table();
    $table->insert($this->attrs);

    foreach (static::table()->primaryKeys as $primaryKey)
      if (isset(static::table()->columns[$primaryKey]) && static::table()->columns[$primaryKey]['ai'])
        $this->attrs[$primaryKey] = \_M\Connection::instance()->lastInsertId();
    
    $this->setIsNew(false)
         ->cleanFlagDirty();
    return true;
  }

  public static function create($attrs) {
    $className = get_called_class();
    $model = new $className($attrs);
    $model->setIsNew(true);
    $model->save();
    return $model;
  }

  public function __toString() {
    return json_encode(array_map(function ($attr) {
      return '' . $attr;
    }, $this->attrs));
  }

  public static function selectQuery($sql, $values = []) {
    if (!$sql = trim($sql))
      return [];

    $sth = \_M\Connection::instance()->query(trim($sql), $values);
    
    return $sth->fetchAll();
  }

  public function setUploadBind() {
    $className = $this->className;
    $uploaders = isset($className::$uploaders) ? $className::$uploaders : [];
    
    foreach ($uploaders as $column => $class)
      if (class_exists($class = '\\M\\' . $class) && in_array($column, array_keys($this->attrs())))
        $class::bind($this, $column);

    return $this;
  }
}
