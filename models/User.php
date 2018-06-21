<?php

namespace M;

class User extends Model {
  static $hasOne = [
  ];
  
  static $hasMany = [
    // 'articles' => 'Article',
    'articles' => ['model' => 'Article', 'select' => 'id'],
  ];

  static $belongsTo = [
  ];
}