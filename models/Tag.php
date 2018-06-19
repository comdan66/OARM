<?php

namespace M;

class Tag {
  static $hasOne = [
  ];
  
  static $hasMany = [
    'articles' => ['model' => 'Article', 'by' => 'tagMappings'],
    'articleMappings' => ['model' => 'TagArticleMapping'],
  ];

  static $belongsTo = [
  ];
}