<?php

namespace M;

class Tag extends Model {
  // static $tableName ='tags';

  static $hasOne = [
  ];
  
  static $hasMany = [
    'articles' => ['model' => 'Article', 'by' => 'tagMappings'],
    'articleMappings' => ['model' => 'TagArticleMapping'],
  ];

  static $belongsTo = [
  ];
}