<?php
date_default_timezone_set('Asia/Taipei');


include 'model/Model.php';

function gg () {
  echo '1<meta http-equiv="Content-type" content="text/html; charset=utf-8" /><pre>';
  var_dump (func_get_args());
  exit ();
}

class Benchmark {
  private static $times = [];
  private static $memories = [];

  public static function markStar ($key) {
    isset (self::$times[$key]) || self::$times[$key] = [];
    isset (self::$memories[$key]) || self::$memories[$key] = [];

    self::$times[$key]['s'] = microtime (true);
    self::$memories[$key]['s'] = memory_get_usage ();
  }
  public static function markEnd ($key) {
    isset (self::$times[$key]) || self::$times[$key] = [];
    isset (self::$memories[$key]) || self::$memories[$key] = [];

    self::$times[$key]['e'] = microtime (true);
    self::$memories[$key]['e'] = memory_get_usage ();
  }

  public static function memoryUsage ($decimals = 4) {
    return round (memory_get_usage () / pow (1024, 2), 4) . 'MB';
  }

  public static function elapsedTime ($key = null, $decimals = 4) {
    if ($key === null) {
      $arr = [];
      foreach (self::$times as $key => $time)
        if (isset ($time['s']))
          $arr[$key] = number_format ((isset ($time['e']) ? $time['e'] : microtime (true)) - $time['s'], $decimals);
      return $arr;
    }

    if (!isset (self::$times[$key], self::$times[$key]['s']))
      return null;

    isset (self::$times[$key]['e']) || self::$times[$key]['e'] = microtime (true);

    return number_format (self::$times[$key]['e'] - self::$times[$key]['s'], $decimals);
  }

  public static function elapsedMemory ($key = null, $decimals = 4) {
    if ($key === null) {
      $arr = [];
      foreach (self::$memories as $key => $memory)
        if (isset ($memory['s']))
          $arr[$key] = round (((isset ($memory['e']) ? $memory['e'] : memory_get_usage ()) - $memory['s']) / pow (1024, 2), $decimals) . 'MB';
      return $arr;
    }

    if (!isset (self::$memories[$key], self::$memories[$key]['s']))
      return null;

    isset (self::$memories[$key]['e']) || self::$memories[$key]['e'] = memory_get_usage ();

    return round ((self::$memories[$key]['e'] - self::$memories[$key]['s']) / pow (1024, 2), $decimals) . 'MB';
  }
}

class Log {
  public static function query($valid, $time, $sql, $values) {
    var_dump ($valid, $time, $sql, $values);
    // exit ();
  }
  public static function error($error) {
    var_dump ($error);
    exit ();
  }
}

Benchmark::markStar('整體');

\_M\Config::setModelsDir (__DIR__ . '/models/');
// \_M\Config::setQueryLogerFunc ('Log::query');
\_M\Config::setLogerFunc ('Log::error');
\_M\Config::setErrorFunc ('gg');
\_M\Config::setConnection ([
  'hostName' => '127.0.0.1',
  'database' => 'gps.kerker',
  'userName' => 'root',
  'password' => '1234',
  'charSet'  => 'utf8mb4',
]);

// new \M\Article();
// \M\transaction(function () {
//   $sth = \_M\Connection::instance()->query("INSERT INTO `devices`(`name`,`uuid`,`created_at`) VALUES(?,?,?)", ['oa', 'asd', '2018-06-19 22:50:55']);
  
//   return true;
// });

$obj = M\Article::create ([
  'name' => 'OA'
]);

$obj->name = 'OA';

// $obj = M\Article::one();

// $obj = M\Article::one();
// $obj->name = '!!!';
// $obj->createdAt = '2018-12-13 12:12:13';
// $obj->delete();

// exit ();

// M\Article::first();
// M\Article::last();
// M\Article::all();



Benchmark::markEnd('整體');

echo '<meta http-equiv="Content-type" content="text/html; charset=utf-8" /><pre>';
var_dump(Benchmark::elapsedTime());
var_dump(Benchmark::elapsedMemory());
exit ();

// $sth = \_M\Connection::instance()->query('select * from `BookArticle`;');

// while ($row = $sth->fetch()) {
//   echo '<meta http-equiv="Content-type" content="text/html; charset=utf-8" /><pre>';
//   var_dump ($row);
//   exit ();
// }