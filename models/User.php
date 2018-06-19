<?php

namespace M;

class User extends Model {
  static $hasOne = [
  ];
  
  static $hasMany = [
    'articles' => ['model' => 'Article'],
  ];

  static $belongsTo = [
  ];
}