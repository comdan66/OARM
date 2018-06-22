<?php

namespace M;

class User extends Model {
  static $hasOne = [
    'article' => ['model' => 'Article'],
  ];
  
  static $hasMany = [
    // 'articles' => ['model' => 'Article'],
  ];

  static $belongsTo = [
  ];
}