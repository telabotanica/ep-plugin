<?php

namespace Migration\App;

use Migration\MigrationGroup;
use Migration\DbNamesEnum;
use Migration\App\Users\UserMigrationGroup;
use Migration\App\Rubrics\RubricMigrationGroup;
use Migration\App\NewsEvents\NewsEventMigrationGroup;

/**
 * Migrates users/news-events/rubrics related data.
 */
class AllMigrationGroup extends MigrationGroup {

  function __construct() {
    Parent::__construct([
        new UserMigrationGroup(),
        new NewsEventMigrationGroup(),
        new RubricMigrationGroup()
      ],
      null);
  }

  public function migrate() {
    foreach($this->migrations as $migration) {
      $migration->migrate();
    }
  }

}
