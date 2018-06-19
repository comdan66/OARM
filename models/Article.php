<?php

namespace M;

class Article extends Model {
  static $hasOne = [
  ];
  
  static $hasMany = [
    'tags'        => ['model' => 'Tag', 'by' => 'articleMappings'],
    'comments'    => ['model' => 'Comment'],
    'tagMappings' => ['model' => 'TagArticleMapping'],
  ];

  static $belongsTo = [
    'user' => ['model' => 'User'],
  ];

  static $uploaders = [
    'file'  => 'ArticleCoverFileUploader',
    'cover' => 'ArticleCoverImageUploader',
  ];
}

// class ArticleCoverImageUploader extends ImageUploader {
//   public function versions() {
//     return [
//       ''          => [],
//       'w100'      => ['resize', 100, 100, 'width'],
//       'w1440'     => ['resize', 1440, 1440, 'width'],
//       'c1200x630' => ['adaptiveResizeQuadrant', 1200, 630, 't'],
//     ];
//   }
// }

// class ArticleCoverFileUploader extends FileUploader {

// }