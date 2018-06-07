<?php

namespace Migration\App\NewsEvents;

use Migration\Api\MigrationGroup;
use Migration\App\NewsEvents\NewsEventDbResetter;
use Migration\App\Config\DbNamesEnum;

/**
 * Migrates news-event data (news, news comments, news covers,
 * events, event metas) .
 */
class NewsEventMigrationGroup extends MigrationGroup {

  function __construct() {
    $newsEventDbResetter = new NewsEventDbResetter(DbNamesEnum::Wp);
    parent::__construct([
      new NewsMigration(),
      new NewsMetaMigration(),
      new NewsCommentMigration(),
      new EventMigration(),
      new EventMetaMigration()
    ], $newsEventDbResetter);

  }

}
