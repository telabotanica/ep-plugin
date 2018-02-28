<?php

namespace Migration\App\Rubrics;

use Migration\Api\MigrationGroup;
use Migration\App\Rubrics\RubricDbResetter;
use Migration\App\Config\DbNamesEnum;

/**
 * Migrates rubrics from SPIP DB to WP DB.
 */
class RubricMigrationGroup extends MigrationGroup {

  function __construct() {

    parent::__construct(
      [new RubricMigration()],
      new RubricDbResetter(DbNamesEnum::Wp));

  }

}
