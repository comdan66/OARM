<?php

namespace M;

class TagArticleMapping extends Model {
  // static $tableName ='devices';
  static $hasOne = [
  ];
  
  static $hasMany = [
  ];

  static $belongsTo = [
    'tag' => ['model' => 'Tag'],
    'article' => ['model' => 'Article'],
  ];
}