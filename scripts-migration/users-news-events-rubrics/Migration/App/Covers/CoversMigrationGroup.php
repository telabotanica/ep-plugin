<?php

namespace Migration\App\Covers;

use Migration\Api\MigrationGroup;
use Migration\App\NewsEvents\CoversDbResetter;
use Migration\App\Config\DbNamesEnum;

/**
 * Migrates covers for imported news
 */
class CoversMigrationGroup extends MigrationGroup {

  function __construct() {
    Parent::__construct([
      new CoversSync(),
      new CoversMigration(),
    ]);
  }

}
