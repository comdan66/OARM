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