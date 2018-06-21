<?php

namespace _M;

class SqlBuilder {
  private $quoteTableName;
  private $toStringFunc = null;
  private $select = '*';

  private $where;
  private $order;
  private $limit;
  private $offset;
  private $group;
  private $having;
  private $values;
  private $data;

  public function __construct($quoteTableName) {
    $this->quoteTableName = $quoteTableName;

    $this->toStringFunc = 'buildSelect';
    $this->select = '*';
    $this->where = null;
    $this->order = null;
    $this->limit = 0;
    $this->offset = 0;
    $this->group = null;
    $this->having = null;
    $this->values = [];
  }

  public static function create($quoteTableName) {
    return new static($quoteTableName);
  }
  public function select($select) {
    if ($select === null)
      return $this;

    $this->toStringFunc = 'buildSelect';

    $this->select = $select ? $select : '*';
    return $this;
  }

  public function where($where) {
    if ($where === null)
      return $this;

    $whereStr = array_shift($where);

    $i = 0;
    foreach ($where as &$value)
      if (is_array($value)) {
        ($i = strpos($whereStr, '(?)', $i)) === false && Config::error('Where 格式有誤！', '條件：'. $whereStr, '參數：' . implode(',', $value));
        $whereStr = substr($whereStr, 0, $i) . '(' . ($value ? implode(',', array_map(function () { return '?'; }, $value)) : '?') . ')' . substr($whereStr, $i += 3);
        $value = $value ? $value : 'null';
      }

    $where = \M\arrayFlatten($where);
    substr_count($whereStr, '?') == count($where) || Config::error('Where 格式有誤！', '條件：' . $whereStr, '參數：' . implode(',', $where));

    $this->where = $whereStr;
    $this->values = $where;

    return $this;
  }

  public function order($order) {
    $this->order = (string)$order;
    return $this;
  }

  public function limit($limit) {
    $this->limit = intval($limit);
    return $this;
  }

  public function offset($offset) {
    $this->offset = intval($offset);
    return $this;
  }

  public function group($group) {
    if ($group === null)
      return $this;

    $this->group = $group;
    return $this;
  }

  public function having($having) {
    if ($having === null)
      return $this;

    $this->having = $having;
    return $this;
  }

  public function setSelectOption($options) {
    foreach (['select', 'where', 'order', 'limit', 'offset', 'group', 'having'] as $method)
      isset($options[$method]) && $this->$method($options[$method]);
    return $this;
  }

  public function __toString() {
    return $this->toString();
  }

  public function toString() {
    $func = $this->toStringFunc;
    return $this->$func();
  }

  private function buildSelect() {
    $sql = "SELECT " . $this->select . " FROM " . $this->quoteTableName;

    $this->where  && $sql .= ' WHERE ' . $this->where;
    $this->group  && $sql .= ' GROUP BY ' . $this->group;
    $this->having && $sql .= ' HAVING ' . $this->having;
    $this->order  && $sql .= ' ORDER BY ' . $this->order;
    
    if ($this->limit || $this->offset)
      $sql .= ' LIMIT ' . intval($this->offset) . ', ' . intval($this->limit);

    return $sql;
  }

  public function getValues() {
    return $this->values;
  }
  
  private function buildUpdate() {
    $set = implode('=?, ', array_map(function ($t) { return Config::quoteName($t); }, array_keys($this->data))) . '=?';
    $sql = "UPDATE " . $this->quoteTableName . " SET " . $set;
    $this->where && $sql .= " WHERE " . $this->where;

    return $sql;
  }
  public function update($data) {
    $this->toStringFunc = 'buildUpdate';
    $this->data = $data;
    return $this;
  }
  public function bindValues() {
    $ret = [];

    if ($this->data)
      $ret = array_values($this->data);

    if ($this->values)
      $ret = array_merge($ret, $this->values);

    $this->values = \M\arrayFlatten($ret);
    
    return $this;
  }

  public function delete($data = []) {
    $this->toStringFunc = 'buildDelete';
    $this->data = $data;
    return $this;
  }
  public function buildDelete() {
    $sql = "DELETE FROM " . $this->quoteTableName;
    $this->where && $sql .= " WHERE " . $this->where;

    return $sql;
  }
  public function buildInsert() {
    $keys = array_map(function ($t) { return Config::quoteName($t); }, array_keys($this->data));
    $sql = "INSERT INTO " . $this->quoteTableName . "(" . implode(', ', $keys) . ") VALUES(" . implode(', ', array_map(function () { return '?'; }, $keys)) . ")";
    return $sql;
  }

  public function insert($data, $pk=null, $sequence_name=null) {
    $this->toStringFunc = 'buildInsert';
    $this->data = $data;
    return $this;
  }









  // private $order;
  // private $limit;
  // private $offset;
  // private $group;
  // private $having;
  // private $update;

  // private $where_values = array();

  
  // private $sequence;


  // /**
  //  * Returns the bind values.
  //  *
  //  * @return array
  //  */
  // public function bindValues()
  // {
  //   $ret = array();

  //   if ($this->data)
  //     $ret = array_values($this->data);

  //   if ($this->get_where_values())
  //     $ret = array_merge($ret,$this->get_where_values());

  //   return array_flatten($ret);
  // }










  // /**
  //  * Reverses an order clause.
  //  */
  // public static function reverse_order($order)
  // {
  //   if (!trim($order))
  //     return $order;

  //   $parts = explode(',',$order);

  //   for ($i=0,$n=count($parts); $i<$n; ++$i)
  //   {
  //     $v = strtolower($parts[$i]);

  //     if (strpos($v,' asc') !== false)
  //       $parts[$i] = preg_replace('/asc/i','DESC',$parts[$i]);
  //     elseif (strpos($v,' desc') !== false)
  //       $parts[$i] = preg_replace('/desc/i','ASC',$parts[$i]);
  //     else
  //       $parts[$i] .= ' DESC';
  //   }
  //   return join(',',$parts);
  // }

  // /**
  //  * Converts a string like "id_and_name_or_z" into a where value like array("id=? AND name=? OR z=?", values, ...).
  //  *
  //  * @param Connection $connection
  //  * @param $name Underscored string
  //  * @param $values Array of values for the field names. This is used
  //  *   to determine what kind of bind marker to use: =?, IN(?), IS NULL
  //  * @param $map A hash of "mapped_column_name" => "real_column_name"
  //  * @return A where array in the form array(sql_string, value1, value2,...)
  //  */
  // public static function create_where_from_underscored_string(Connection $connection, $name, &$values=array(), &$map=null)
  // {
  //   if (!$name)
  //     return null;

  //   $parts = preg_split('/(_and_|_or_)/i',$name,-1,PREG_SPLIT_DELIM_CAPTURE);
  //   $num_values = count($values);
  //   $where = array('');

  //   for ($i=0,$j=0,$n=count($parts); $i<$n; $i+=2,++$j)
  //   {
  //     if ($i >= 2)
  //       $where[0] .= preg_replace(array('/_and_/i','/_or_/i'),array(' AND ',' OR '),$parts[$i-1]);

  //     if ($j < $num_values)
  //     {
  //       if (!is_null($values[$j]))
  //       {
  //         $bind = is_array($values[$j]) ? ' IN(?)' : '=?';
  //         $where[] = $values[$j];
  //       }
  //       else
  //         $bind = ' IS NULL';
  //     }
  //     else
  //       $bind = ' IS NULL';

  //     // map to correct name if $map was supplied
  //     $name = $map && isset($map[$parts[$i]]) ? $map[$parts[$i]] : $parts[$i];

  //     $where[0] .= $connection->quote_name($name) . $bind;
  //   }
  //   return $where;
  // }

  // /**
  //  * Like create_where_from_underscored_string but returns a hash of name => value array instead.
  //  *
  //  * @param string $name A string containing attribute names connected with _and_ or _or_
  //  * @param $args Array of values for each attribute in $name
  //  * @param $map A hash of "mapped_column_name" => "real_column_name"
  //  * @return array A hash of array(name => value, ...)
  //  */
  // public static function create_hash_from_underscored_string($name, &$values=array(), &$map=null)
  // {
  //   $parts = preg_split('/(_and_|_or_)/i',$name);
  //   $hash = array();

  //   for ($i=0,$n=count($parts); $i<$n; ++$i)
  //   {
  //     // map to correct name if $map was supplied
  //     $name = $map && isset($map[$parts[$i]]) ? $map[$parts[$i]] : $parts[$i];
  //     $hash[$name] = $values[$i];
  //   }
  //   return $hash;
  // }

  // /**
  //  * prepends table name to hash of field names to get around ambiguous fields when SQL builder
  //  * has joins
  //  *
  //  * @param array $hash
  //  * @return array $new
  //  */
  // private function prepend_table_name_to_fields($hash=array())
  // {
  //   $new = array();
  //   $table = $this->connection->quote_name($this->table);

  //   foreach ($hash as $key => $value)
  //   {
  //     $k = $this->connection->quote_name($key);
  //     $new[$table.'.'.$k] = $value;
  //   }

  //   return $new;
  // }


  // private function build_delete()
  // {
  //   $sql = "DELETE FROM $this->table";

  //   if ($this->where)
  //     $sql .= " WHERE $this->where";

  //   if ($this->connection->accepts_limit_and_order_for_update_and_delete())
  //   {
  //     if ($this->order)
  //       $sql .= " ORDER BY $this->order";

  //     if ($this->limit)
  //       $sql = $this->connection->limit($sql,null,$this->limit);
  //   }

  //   return $sql;
  // }

  // private function build_insert()
  // {
  //   require_once 'Expressions.php';
  //   $keys = join(',',$this->quoted_key_names());

  //   if ($this->sequence)
  //   {
  //     $sql =
  //       "INSERT INTO $this->table($keys," . $this->connection->quote_name($this->sequence[0]) .
  //       ") VALUES(?," . $this->connection->next_sequence_value($this->sequence[1]) . ")";
  //   }
  //   else
  //     $sql = "INSERT INTO $this->table($keys) VALUES(?)";

  //   $e = new Expressions($this->connection,$sql,array_values($this->data));
  //   return $e->to_s();
  // }


  // private function build_update()
  // {
  //   if (strlen($this->update) > 0)
  //     $set = $this->update;
  //   else
  //     $set = join('=?, ', $this->quoted_key_names()) . '=?';

  //   $sql = "UPDATE $this->table SET $set";

  //   if ($this->where)
  //     $sql .= " WHERE $this->where";

  //   if ($this->connection->accepts_limit_and_order_for_update_and_delete())
  //   {
  //     if ($this->order)
  //       $sql .= " ORDER BY $this->order";

  //     if ($this->limit)
  //       $sql = $this->connection->limit($sql,null,$this->limit);
  //   }

  //   return $sql;
  // }

}