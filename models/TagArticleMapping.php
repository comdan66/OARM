<?php

namespace M;

class TagArticleMapping {
  static $hasOne = [
  ];
  
  static $hasMany = [
  ];

  static $belongsTo = [
    'tag' => ['model' => 'Tag'],
    'article' => ['model' => 'Article'],
  ];
}