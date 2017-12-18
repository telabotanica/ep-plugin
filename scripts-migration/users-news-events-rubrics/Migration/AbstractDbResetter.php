<?php

namespace Migration;

use Migration\DbResetter;

abstract class AbstractDbResetter implements DbResetter {

  protected $dbConnection;
  protected $tablePrexix;

  public function __construct($dbConnection, $tablePrexix) {
      $this->dbConnection = $dbConnection;
      $this->tablePrexix = $tablePrexix;
  }

  public abstract function resetDb();

}
