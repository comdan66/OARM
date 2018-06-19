<?php

namespace M;

class User {
  static $hasOne = [
  ];
  
  static $hasMany = [
    'articles' => ['model' => 'Article'],
  ];

  static $belongsTo = [
  ];
}