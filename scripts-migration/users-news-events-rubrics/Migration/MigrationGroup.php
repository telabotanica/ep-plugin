<?php

namespace Migration;

class MigrationGroup {

  private $migrations;
  private $dbResetter;

  public function __construct($migrations, $dbResetter) {
    $this->migrations = $migrations;
    $this->dbResetter = $dbResetter;
  }

  public function migrate() {
    if (isset($this->dbResetter)) {
      $this->dbResetter->resetDb();
    }
    foreach($this->migrations as $migration) {
      $migration->migrate();
    }
  }

}
