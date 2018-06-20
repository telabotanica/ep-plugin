<?php

namespace Migration\Api;


use \Migration\Api\Migration;

/**
 * Resets the target DB and launches chained
 * <code>Migration</code>s.
 */
class MigrationGroup implements Migration {

  // The migrations to be launched:
  protected $migrations;
  // The DbResetter to reset target DB:
  private $dbResetter;
  // Testing session ?
  protected $test;

  public function __construct($migrations, $dbResetter = null, $test = false) {
    $this->migrations = $migrations;
    $this->dbResetter = $dbResetter;
    $this->test = $test;
  }

  /**
   * Resets target DB and launches chained <code>Migration</code>s.
   */
  public function migrate() {
    if (isset($this->dbResetter)) {
      $this->dbResetter->resetDb();
    }
    foreach($this->migrations as $migration) {
      $migration->migrate($this->test);
    }
  }

}
