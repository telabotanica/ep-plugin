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
   * @param $ex the root exception.
   * @param $query the SQL query which raised the exception.
   * @param $func the name of the function which raised the exception.
   *
   * @return a new <code>MigrationException</code> instance.
   */
  public function __construct($ex, $query, $func) {
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
    return $this->query;
  }

  /**
   * Returns the name of the function issuing the query which failed.
   *
   * @return the name of the function issuing the query which failed.
   */
  public function getFunc() {
    return $this->func;
  }

}
