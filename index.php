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

define('PATH_LOG', __DIR__ . '/log/');

class Log {
  private static $extension = '.log';
  private static $permissions = 0777;
  private static $dateFormat = 'Y-m-d H:i:s';
  private static $fopens = [];

  public static function message($text, $prefix = 'log-') {
    if (!(is_dir(PATH_LOG) && is_writable(PATH_LOG)))
      return false;

    $newfile = !file_exists($path = PATH_LOG . $prefix . date('Y-m-d') . self::$extension);

    if (!isset(self::$fopens[$path]))
      if (!$fopen = @fopen($path, 'ab'))
        return false;
      else
        self::$fopens[$path] = $fopen;

    for($written = 0, $length = strlen($text); $written < $length; $written += $result)
      if (($result = fwrite(self::$fopens[$path], substr($text, $written))) === false)
        break;

    $newfile && @chmod($path, self::$permissions);

    return is_int($result);
  }


  public static function query($valid, $time, $sql, $values) {
    // echo '<meta http-equiv="Content-type" content="text/html; charset=utf-8" /><pre>';
    self::message (($valid ? 'ok' : 'no') . ' ' . $time . ' SQL：' . $sql . ' Value：'. implode(',', $values) . "\n");
    // echo "<hr/>";
    // exit ();
  }
  public static function error($error) {
    var_dump ($error);
    // exit ();
  }
}

Benchmark::markStar('整體');

\_M\Config::setModelsDir (__DIR__ . '/models/');
\_M\Config::setQueryLogerFunc ('Log::query');
\_M\Config::setLogerFunc ('Log::error');
\_M\Config::setErrorFunc ('gg');
\_M\Config::setConnection ([
  'hostName' => '127.0.0.1',
  'database' => 'gps.kerker',
  'userName' => 'root',
  'password' => '1234',
  'charSet'  => 'utf8mb4',
]);

\M\Uploader::setDriver('local');
\M\Uploader::setBaseDirs(['upload']);
\M\Uploader::setTmpDir(__DIR__ . '/tmp/');
\M\Uploader::setBaseUrl('https://qwe.ds/');

// new \M\Article();
// \M\transaction(function () {
//   $sth = \_M\Connection::instance()->query("INSERT INTO `devices`(`name`,`uuid`,`created_at`) VALUES(?,?,?)", ['oa', 'asd', '2018-06-19 22:50:55']);
  
//   return true;
// });

// $obj = M\Article::create ([
//         'name' => 'oa1',
//         'd' => date('2017-11-11'),
// ]);

// $user = M\User::one(['where' => 'id = 1']);

// echo '<meta http-equiv="Content-type" content="text/html; charset=utf-8" /><pre>';
// var_dump (count($user->articles));
// exit ();

// $article = M\Article::one(['where' => 'id = 1']);

echo '<meta http-equiv="Content-type" content="text/html; charset=utf-8" /><pre>';
// var_dump ($article->user->name);
// exit ();

// $users = \M\User::all(['readonly' => true]);
// // $users[0]->setIsReadonly(false);
// echo '<meta http-equiv="Content-type" content="text/html; charset=utf-8" /><pre>';
// var_dump ($users[0]);
// // exit ();
// // echo '<meta http-equiv="Content-type" content="text/html; charset=utf-8" /><pre>';
// // var_dump (spl_object_hash($users[0]));
// // var_dump (spl_object_hash($users[0]));
// exit ();
// $tag = \M\Tag::one(['where' => 'id = 1']);
// echo '<meta http-equiv="Content-type" content="text/html; charset=utf-8" /><pre>';
// var_dump ($tag->articleMappings);
// exit ();

$article = \M\Article::one(['select' => 'id, cover']);
// echo '<meta http-equiv="Content-type" content="text/html; charset=utf-8" /><pre>';
// var_dump ($article->cover->url());
echo $article->cover->putUrl('http://flowers.taipei/imagespace/plant_tree/original/thumb_image_6710831.JPG');
// $article->cover = 'sss';

// echo '<meta http-equiv="Content-type" content="text/html; charset=utf-8" /><pre>';
// var_dump ($article->save());
// exit ();

//   echo '<meta http-equiv="Content-type" content="text/html; charset=utf-8" /><pre>';
// foreach ($articles as $article) {
//   echo $article->id . '<br>';
//   array_map(function ($t) {
//     echo $t->name . "<br/>";
//   }, $article->tags);
//   echo '<hr>';
// }
// exit ();

// $objs = \M\Model::query('select * from Article');
// echo '<meta http-equiv="Content-type" content="text/html; charset=utf-8" /><pre>';
// var_dump ($objs);
// exit ();

// $objs = \M\Model::query('INSERT Comment(`articleId`,`title`,`userId`) values(?,?,?)', [1,2,3]);
// echo '<meta http-equiv="Content-type" content="text/html; charset=utf-8" /><pre>';
// var_dump ($objs);
// exit ();
// echo '<meta http-equiv="Content-type" content="text/html; charset=utf-8" /><pre>';
// var_dump ($users);
// exit ();
// foreach ($users as $user) {
//   echo "User ID:" . $user->id . "<br/>";
//   echo $user->article ? " ===> Article ID:" . $user->article->id . "<br/>" : '';

  // foreach ($user->articles as $article) {
  //   echo " ===> Article ID:" . $article->id . "<br/>";

  //   foreach ($article->comments as $comment) {
  //     echo " ------> Commit ID:" . $comment->id . "<br/>";
  //     echo " ------> User ID:" . $comment->user->id . "<br/>";
  //     // foreach ( as $key => $value) {
  //     //   # code...
  //     // }
  //   }

  //   echo "<br/>";
  // }
  // echo "<br/>";
//   echo "<br/>";
// }

// $articles = \M\Article::all(['limit' => 10, 'include' => ['users']]);

// foreach ($articles as $article) {
//   echo $article->id .' - ';
//   var_dump(count($article->users));
//   echo "<br>";
// }





// exit();

// $users = \M\User::all(['limit' => 10, 'include' => ['article']]);

// // echo '<meta http-equiv="Content-type" content="text/html; charset=utf-8" /><pre>';
// // var_dump ($users);
// // exit ();
// echo '<meta http-equiv="Content-type" content="text/html; charset=utf-8" /><pre>';
// foreach ($users as $user) {
//   echo $user->id .' - ';
//   var_dump($user->article->id);
//   echo "<br>";
// }

// var_dump ();
// exit (); 
// $articles = M\Article::all(['where' => 'userId = 1']);

// echo '<meta http-equiv="Content-type" content="text/html; charset=utf-8" /><pre>';
// var_dump ($user->columns(), $articles);

// echo '<meta http-equiv="Content-type" content="text/html; charset=utf-8" /><pre>';
// var_dump ($obj->createdAt);
// exit ();
// sleep(1);

// $obj->d = '2017-11-11';
// echo '<meta http-equiv="Content-type" content="text/html; charset=utf-8" /><pre>';
// var_dump ($obj->d);
// exit ();
// echo '<meta http-equiv="Content-type" content="text/html; charset=utf-8" /><pre>';
// var_dump ($obj->createdAt);
// exit ();
// $obj->save();

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