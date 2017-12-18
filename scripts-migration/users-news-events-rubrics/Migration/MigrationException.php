<?php

namespace Migration;

use \Exception;

/**
 * Custom exception indicating that a problem occurred during the migration. This means
 * some of the destination tables must be restored to their inital state.
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
  public function __construct($message, $code, $query, $func) {

    parent::__construct($message, $code);
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
