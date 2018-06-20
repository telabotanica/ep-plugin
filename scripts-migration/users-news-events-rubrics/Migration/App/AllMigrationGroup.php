<?php

namespace Migration\App;

use Migration\Api\MigrationGroup;
use Migration\App\Users\UserMigrationGroup;
use Migration\App\Rubrics\RubricMigrationGroup;
use Migration\App\NewsEvents\NewsEventMigrationGroup;
use Migration\App\Covers\CoversMigrationGroup;

/**
 * Migrates users/news-events/rubrics related data.
 */
class AllMigrationGroup extends MigrationGroup {

  function __construct() {
    parent::__construct([
        new UserMigrationGroup(),
        new NewsEventMigrationGroup(),
        new RubricMigrationGroup(),
        new CoversMigrationGroup(),
      ]);
  }

}
