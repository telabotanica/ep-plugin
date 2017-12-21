<?php

namespace Migration\Api;

use Migration\Api\AbstractDbResetter;
use Migration\Api\MigrationException;
use \Exception;

/**
 * Resets the target DB prior to a new migration by issuing a
 * list of SQL queries.
 */
class QueryDbResetter extends AbstractDbResetter {

  protected $queries = [];

  public function __construct($dbName) {
      parent::__construct($dbName);
  }

  public function setQueries($queries) {
    $this->queries = $queries;
  }

  public function resetDb() {
    foreach($this->queries as $query) {
      try {
        $this->dbConnection->exec($query);
      } catch (Exception $ex) {
        echo "-- ECHEC " . __FUNCTION__ . " REQUÊTE: [$query]" . PHP_EOL;
        throw new MigrationException($ex, $query, __FUNCTION__);

      }

    }

  }

}
