<?php

namespace Migration\Api;

use \Migration\Api\Migration;
use \Migration\Api\DatasourceManager;
use \Migration\App\Config\DbNamesEnum;


/**
 * Abstract parent classes implementing the migration process for a given
 * resource.
 */
abstract class BaseMigration implements Migration {

  // Connection (PDO) to tela "annuaire" source DB.
  protected $telaDbConnection;
  // Connection (PDO) to SPIP source DB.
  protected $spipDbConnection;
  // WP table prefix for target DB.
  protected $wpTablePrefix;
  // Connection (PDO) to WP target DB.
  protected $wpDbConnection;

  public function __construct() {
    $dsm = DatasourceManager::getInstance();
    $this->wpTablePrefix    = $dsm->getTablePrefix(DbNamesEnum::Wp);
    $this->spipDbConnection = $dsm->getConnection(DbNamesEnum::Spip);
    $this->telaDbConnection = $dsm->getConnection(DbNamesEnum::Tela);
    $this->wpDbConnection   = $dsm->getConnection(DbNamesEnum::Wp);
  }

  /**
   * Migrates data to WP.
   */
  abstract public function migrate();

}
