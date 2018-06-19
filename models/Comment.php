<?php

namespace M;

class Comment {
  static $hasOne = [
  ];
  
  static $hasMany = [
  ];

  static $belongsTo = [
    'user' => ['model' => 'User'],
    'article' => ['model' => 'Article'],
  ];
}