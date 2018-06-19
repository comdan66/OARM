<?php

include 'model/Model.php';

function gg () {
  echo '1<meta http-equiv="Content-type" content="text/html; charset=utf-8" /><pre>';
  var_dump (func_get_args());
  exit ();
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
\M\transaction(function () {
  $sth = \_M\Connection::instance()->query("INSERT INTO `devices`(`name`,`uuid`,`created_at`) VALUES(?,?,?)", ['oa', 'asd', '2018-06-19 22:50:55']);
  
  return true;
});
// $sth = \_M\Connection::instance()->query('select * from `BookArticle`;');

// while ($row = $sth->fetch()) {
//   echo '<meta http-equiv="Content-type" content="text/html; charset=utf-8" /><pre>';
//   var_dump ($row);
//   exit ();
// }