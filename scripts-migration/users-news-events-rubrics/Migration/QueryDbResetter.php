<?php

namespace Migration;

use Migration\AbstractDbResetter;
use \Exception;

class QueryDbResetter extends AbstractDbResetter {

  protected $queries = [];

  public function setQueries($queries) {
    $this->queries = $queries;
  }

  public function resetDb() {
var_dump(  $this->queries);
    foreach($this->queries as $query) {
      try {
        echo $query . PHP_EOL;
        $this->dbConnection->exec($query);
      } catch (Exception $ex) {
        echo "MIREDA!";

      }

    }

  }

}
