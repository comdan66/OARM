<?php

namespace M;

class Comment extends Model {
  static $hasOne = [
  ];
  
  static $hasMany = [
  ];

  static $belongToOne = [
    'user' => ['model' => 'User'],
    'article' => ['model' => 'Article'],
  ];
}