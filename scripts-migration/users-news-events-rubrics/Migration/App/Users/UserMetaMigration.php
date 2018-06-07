<?php

namespace Migration\App\Users;

use Migration\Api\BaseMigration;
use Migration\Api\MigrationException;
use \Exception;
use \PDO;

/**
 * Migrates user metas from Tela DB to WP/BP DB..
 */
class UserMetaMigration extends BaseMigration {

  /**
   * Migrates user metas from Tela DB to WP/BP DB..
   */
  public function migrate() {

    $requeteUtilisateursMeta = "SELECT `U_ID`, `U_NAME`, `U_SURNAME` FROM `annuaire_tela`;";
    $utilisateursMeta = $this->telaDbConnection->query($requeteUtilisateursMeta)->fetchAll(PDO::FETCH_ASSOC);

    $compteur = 0;
    foreach ($utilisateursMeta as $utilisateurMeta) {

      $pseudo = $this->telaDbConnection->query('SELECT amv_valeur AS pseudo FROM annu_meta_valeurs WHERE amv_ce_colonne = 99 AND amv_cle_ligne = ' . $utilisateurMeta['U_ID'])->fetch(PDO::FETCH_ASSOC);
      $pseudo_utilise = $this->telaDbConnection->query('SELECT amv_valeur AS pseudo_utilise FROM annu_meta_valeurs WHERE amv_ce_colonne = 136 AND amv_cle_ligne = ' . $utilisateurMeta['U_ID'])->fetch(PDO::FETCH_ASSOC);


      $nickname = $utilisateurMeta['U_SURNAME'] . ' ' . ucwords(strtolower($utilisateurMeta['U_NAME']));
      if ($pseudo_utilise['pseudo_utilise'] && $pseudo['pseudo']) {
        $nickname = $pseudo['pseudo'];
      }

      // _access est pour définir les catégories d'articles que l'utilisateur pourra écrire
      $query = 'INSERT INTO ' . $this->wpTablePrefix . "usermeta (`user_id`, `meta_key`, `meta_value`) VALUES "
      . "({:userId}, 'last_activity', '2017-05-19 15:06:16'), "
      . "({:userId}, 'first_name', {:surname}), "
      . "({:userId}, 'last_name', {:name}), "
      . "({:userId}, 'nickname', {:nickname}), "
      . "({:userId}, 'description', ''), "
      . "({:userId}, 'rich_editing', 'true'), "
      . "({:userId}, 'comment_shortcuts', 'false'), "
      . "({:userId}, 'admin_color', 'fresh'), "
      . "({:userId}, 'use_ssl', '0'), "
      . "({:userId}, 'show_admin_bar_front', 'true'), "
      . "({:userId}, '" . $this->wpTablePrefix . "capabilities', 'a:1:{s:11:\"contributor\";b:1;}'), "
      . "({:userId}, '" . $this->wpTablePrefix . "user_level', '1'), "
      . "({:userId}, 'dismissed_wp_pointers', ''), "
      . "({:userId}, 'wp_dashboard_quick_press_last_post_id', '63'), " // c koi ?
      . "({:userId}, '_restrict_media', '1'), " // lié au plugin restrict author media
      . "({:userId}, '_access', 'a:4:{i:0;s:1:\"2\";i:1;s:1:\"5\";i:2;s:1:\"6\";i:3;s:1:\"7\";}'), "
      . "({:userId}, 'bp_xprofile_visibility_levels', 'a:12:{i:1;s:6:\"public\";i:60;s:6:\'public\';i:61;s:6:\'public\';i:49;s:6:\'public\';i:55;s:6:\'public\';i:48;s:6:\'public\';i:62;s:6:\'public\';i:63;s:6:\'public\';i:68;s:6:\'public\';i:76;s:6:\'public\';i:120;s:6:\'public\';i:81;s:6:\'public\';}') "
      . ";";

      try {
        $this->wpDbConnection->exec($query, [
          ':userId' => $utilisateurMeta['U_ID'],
          ':surname' => $utilisateurMeta['U_SURNAME'],
          ':name' => $utilisateurMeta['U_NAME'],
          ':nickname' => $nickname,
        ]);

        $compteur++;
      } catch(Exception $e) {
        echo "-- ECHEC " . __FUNCTION__ . " REQUÊTE: [$query]" . PHP_EOL;
        throw new MigrationException($e, $query, __FUNCTION__);
      }

      $this->insertUserIntoBpXprofileDataTable($utilisateurMeta['U_ID'], $nickname);
    }

    echo '-- ' . $compteur . '/' . count($utilisateursMeta) . ' metas d\'utilisateur migrées. ' . PHP_EOL;

  }


  /**
   *  Inserts a new record into bp_xprofile_data with the given user informations.
   *
   * @param      integer                            $utilisateurId  The utilisateur identifier
   * @param      string                             $nickname       The utilisateur nickname
   *
   * @throws     \Migration\Api\MigrationException  (description)
   */
  private function insertUserIntoBpXprofileDataTable($utilisateurId, $nickname) {

    $requete_pseudo_bp = "INSERT INTO " . $this->wpTablePrefix . "bp_xprofile_data (`field_id`, `user_id`, `value`, `last_updated`) VALUES
    ('1', {:userId}, {:nickname}, '2017-05-19 15:06:16')
    ON DUPLICATE KEY UPDATE `field_id`=VALUES(`field_id`), `user_id`=VALUES(`user_id`), `value`=VALUES(`value`), `last_updated`=VALUES(`last_updated`);";

    try {
      $this->wpDbConnection->exec($requete_pseudo_bp, [
        ':userId' => $utilisateurId,
        ':nickname' => $nickname,
      ]);
    } catch(Exception $e) {
      echo "-- ECHEC " . __FUNCTION__ . " REQUÊTE: [$requete_pseudo_bp]" . PHP_EOL;
      throw new MigrationException($e, $requete_pseudo_bp, __FUNCTION__);
    }

  }// end method insertUserIntoBpXprofileDataTable($utilisateur)

}
