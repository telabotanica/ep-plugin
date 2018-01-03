<?php

namespace Migration\Api;


use \Migration\Api\Migration;

/**
 * Returns a particular <code>Migration</code> instance based on a given
 * context name (e.g. 'users', 'news-events', 'rubrics', 'all').
 */
class MigrationFactory {

  // The migrations to be launched:
  protected $migrationContextClassNameMap;

  public function __construct($migrationContextClassNameMap) {
    $this->migrationContextClassNameMap = $migrationContextClassNameMap;
  }

  /**
   * Returns a particular <code>Migration</code> instance based on the provided
   * context name.
   */
  public function getMigration($ctxName) {

    if (array_key_exists($ctxName, $this->migrationContextClassNameMap)) {
      $class = $this->migrationContextClassNameMap[$ctxName];
      return new $class();
    }
    else {
      return null;
    }
  }

  /**
   * Returns the list of context names.
   */
  public function getContexts() {
    return array_keys($this->migrationContextClassNameMap);
  }

}
