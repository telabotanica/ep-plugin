<?php

namespace Migration\App\Users;

use Migration\Api\BaseMigration;
use Migration\Api\MigrationException;
use \Exception;
use \PDO;

/**
 * Migrates user activities from Tela DB to WP/BP DB..
 */
class UserActivityMigration extends BaseMigration {

  /**
   * Migrates user activities from Tela DB to WP/BP DB..
   */
  public function migrate() {

    $requete = 'SELECT `U_ID` FROM `annuaire_tela`;';
    $utilisateurs = $this->telaDbConnection->query($requete)->fetchAll(PDO::FETCH_ASSOC);

    $compteur = 0;
    foreach ($utilisateurs as $utilisateur) {
      $query = 'INSERT INTO ' . $this->wpTablePrefix . "bp_activity
      (`id`, `user_id`, `component`, `type`, `action`, `content`, `primary_link`, `item_id`, `secondary_item_id`, `date_recorded`, `hide_sitewide`, `mptt_left`, `mptt_right`, `is_spam`)
      VALUES (NULL, {:userId}, 'members', 'last_activity', '', '', '', '0', NULL, '2017-05-19 15:06:16', '0', '0', '0', '0');"; // @todo voir si ON DUPLICATE KEY UPDATE est pertinent ici mais on dirait bien

      try {
        $this->wpDbConnection->exec($query, [':userId' => $utilisateur['U_ID']]);

        $compteur++;
      } catch(Exception $e) {
        throw new MigrationException($e, $query, __FUNCTION__);
      }
    }

    echo '-- ' . $compteur . '/' . count($utilisateurs) . ' activités d\'utilisateur migrés. ' . PHP_EOL;


  }

}
