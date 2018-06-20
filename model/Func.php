<?php

namespace M;

if (!function_exists('getNamespaces')) {
  function getNamespaces($className) {
    return array_slice(explode('\\', $className), 0, -1);
  }
}
if (!function_exists('deNamespace')) {
  function deNamespace($className) {
    $className = array_slice(explode('\\', $className), -1);
    return array_shift($className);
  }
}

if (!function_exists('transaction')) {
  function transaction($closure, &...$args) {
    if (!is_callable($closure))
      return false;

    try {
      \_M\Connection::instance()->transaction();

      if (call_user_func_array($closure, $args))
        return \_M\Connection::instance()->commit();

      \_M\Connection::instance()->rollback();
      return false;
    } catch (\Exception $e) {
      \_M\Connection::instance()->rollback();
      \_M\Config::log($e);
    }

    return true;
  }
}

if (!function_exists ('modelsColumn')) {
  function modelsColumn ($arr, $key) {
    return array_map (function ($t) use ($key) {
      is_callable ($key) && $key = $key ();
      return $t->$key;
    }, $arr);
  }
}

if (!function_exists('arrayFlatten')) {
  function arrayFlatten(array $array) {
    $i = 0;

    while ($i < count($array))
      if (is_array($array[$i]))
        array_splice($array, $i, 1, $array[$i]);
      else
        ++$i;
    return $array;
  }
}