<?php

namespace M;

class Comment extends Model {
  static $hasOne = [
  ];
  
  static $hasMany = [
  ];

  static $belongsTo = [
    'user' => ['model' => 'User'],
    'article' => ['model' => 'Article'],
  ];
}