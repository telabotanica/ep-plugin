<?php

namespace Migration\App\DocumentsLinks;

use Migration\Api\MigrationGroup;
use Migration\App\Config\DbNamesEnum;

/**
 * Migrates Documents links data for imported news.
 */
class DocumentsLinksMigrationGroup extends MigrationGroup {

  function __construct() {
    parent::__construct([
      new DocumentsLinksMigration()
    ]);

  }

}
