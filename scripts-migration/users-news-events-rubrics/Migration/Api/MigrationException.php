<?php

namespace Migration\Api;

use \Exception;

/**
 * Custom exception indicating that a problem occurred during the
 * migration. 
 */
class MigrationException extends Exception {

  private $query;
  private $func;

  /**
   * Returns a new <code>MigrationException</code> instance.
   *
   * @param $dbConn a connection to the destination DB.
   * @param $dbTableNamesToBeRestoredArray an array containing the names of the tables
   *        to be restored.
   * @return a new <code>MigrationException</code> instance.
   */
  public function __construct($ex, $query, $func) {
    var_dump($ex);
    var_dump($ex->getMessage());
    var_dump($ex->getCode());
    parent::__construct($ex->getMessage(), 0, $ex);
    $this->query = $query;
    $this->func = $func;
  }




  /**
   * Returns the SQL query which failed.
   *
   * @return the SQL query which failed.
   */
  public function getQuery() {
    return $query;
  }

  /**
   * Returns the name of the function issuing the query which failed.
   *
   * @return the name of the function issuing the query which failed.
   */
  public function getFunc() {
    return $func;
  }

}
