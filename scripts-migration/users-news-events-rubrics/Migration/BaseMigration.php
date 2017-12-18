<?php

namespace Migration;

/**
 * Abstract parent classes implementing the migration process for a given resource.
 */
abstract class BaseMigration {

  protected $wpDbConnection;
  protected $telaDbConnection;
  protected $wpTablePrefix;
  protected $spipDbConnection;

  public function __construct($wpTablePrefix, $spipDbConnection, $telaDbConnection, $wpDbConnection) {
    $this->wpTablePrefix = $wpTablePrefix;
    $this->spipDbConnection = $spipDbConnection;
    $this->telaDbConnection = $telaDbConnection;
    $this->wpDbConnection = $wpDbConnection;
  }

  public function getTargetConnection() {
    return $this->wpDbConnection;
  }

  abstract public function migrate();

}
