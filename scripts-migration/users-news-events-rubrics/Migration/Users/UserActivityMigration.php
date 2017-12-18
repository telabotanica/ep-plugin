<?php

namespace Migration\Users;

use Migration\BaseMigration ;
use Migration\MigrationException ;
use \PDO;

class UserActivityMigration extends BaseMigration {

  /**
  * Migre les utilisateurs vers la table WordPress
  *
  * Si ça a déjà été fait, ça écrase les valeurs éxistantes coté WordPress
  * Peut donc être relancé plusieurs fois avant la mise en prod
  */
  public function migrate() {

    $requete = 'SELECT `U_ID` FROM `annuaire_tela`;';
    $utilisateurs = $this->telaDbConnection->query($requete)->fetchAll(PDO::FETCH_ASSOC);

    $compteur = 0;
    foreach ($utilisateurs as $utilisateur) {
      $requeteInsert = 'INSERT INTO ' . $this->wpTablePrefix . "bp_activity
      (`id`, `user_id`, `component`, `type`, `action`, `content`, `primary_link`, `item_id`, `secondary_item_id`, `date_recorded`, `hide_sitewide`, `mptt_left`, `mptt_right`, `is_spam`)
      VALUES (NULL, {$utilisateur['U_ID']}, 'members', 'last_activity', '', '', '', '0', NULL, '2017-05-19 15:06:16', '0', '0', '0', '0');"; // @todo voir si ON DUPLICATE KEY UPDATE est pertinent ici mais on dirait bien

      try {
        $this->wpDbConnection->exec($requeteInsert);

        $compteur++;
      } catch(Exception $e) {
        throw new MigrationException($e->getMessage(), $e->getCode(), $requeteInsert, __FUNCTION__);
      }
    }

    echo '-- ' . $compteur . '/' . count($utilisateurs) . ' activités d\'utilisateur migrés. ' . PHP_EOL;


  }

}
