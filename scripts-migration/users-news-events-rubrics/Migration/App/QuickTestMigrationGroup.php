<?php

namespace Migration\App;

use Migration\Api\MigrationGroup;
use Migration\App\Users\UserMigrationGroup;
use Migration\App\Rubrics\RubricMigrationGroup;
use Migration\App\NewsEvents\NewsEventMigrationGroup;

/**
 * Migrates users/news-events/rubrics related data.
 */
class QuickTestMigrationGroup extends MigrationGroup {

  function __construct() {
    $test = true;

    parent::__construct([
        new UserMigrationGroup($test),
        new NewsEventMigrationGroup(),
        new RubricMigrationGroup(),
      ]);
  }

}
