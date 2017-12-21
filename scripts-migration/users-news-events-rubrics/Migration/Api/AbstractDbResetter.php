<?php

namespace Migration\Api;

use Migration\Api\DbResetter;
use Migration\Api\DatasourceManager;

/**
 * Resets DB prior to a new migration.
 */
abstract class AbstractDbResetter implements DbResetter {

  protected $dbConnection;
  protected $tablePrexix;

  public function __construct($dsName) {
    $dsm = DatasourceManager::getInstance();
    $this->dbConnection = $dsm->getConnection($dsName);
    $this->tablePrexix = $dsm->getTablePrefix($dsName);
  }

  /**
   * Resets DB prior to a new migration.
   */
  public abstract function resetDb();

}
