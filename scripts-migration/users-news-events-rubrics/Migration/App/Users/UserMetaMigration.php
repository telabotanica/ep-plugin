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

      $access = 'a:12:{i:0;s:2:"20";i:1;s:2:"21";i:2;s:2:"22";i:3;s:2:"23";i:4;s:2:"24";i:5;s:2:"26";i:6;s:2:"27";i:7;s:2:"28";i:8;s:2:"29";i:9;s:2:"31";i:10;s:2:"32";i:11;s:2:"39";}';
      $bp_xprofile_visibility_levels = 'a:12:{i:1;s:6:"public";i:60;s:6:"public";i:61;s:6:"public";i:49;s:6:"public";i:55;s:6:"public";i:48;s:6:"public";i:62;s:6:"public";i:63;s:6:"public";i:68;s:6:"public";i:76;s:6:"public";i:120;s:6:"public";i:81;s:6:"public";}';
      $capabilities = 'a:1:{s:11:"contributor";b:1;}';
      $user_level = '1';

      switch ($utilisateurMeta['U_ID']) {
        case '2': //Ex-telabotaniste
          $access = 'a:0:{}';
          $bp_xprofile_visibility_levels = 'a:18:{i:46;s:10:"adminsonly";i:10;s:10:"adminsonly";i:9;s:10:"adminsonly";i:3;s:10:"adminsonly";i:592;s:10:"adminsonly";i:4;s:10:"adminsonly";i:59;s:6:"public";i:1;s:6:"public";i:51;s:6:"public";i:54;s:6:"public";i:53;s:10:"adminsonly";i:2;s:10:"adminsonly";i:52;s:10:"adminsonly";i:26;s:10:"adminsonly";i:12;s:10:"adminsonly";i:69;s:10:"adminsonly";i:63;s:10:"adminsonly";i:61;s:10:"adminsonly";}';
          $capabilities = 'a:1:{s:15:"deleted_tb_user";b:1;}';
          $user_level = '0';
          break;
        case '5': //tb_president
          $capabilities = 'a:1:{s:12:"tb_president";b:1;}';
          break;
        default:
          break;
      }
      // _access est pour définir les catégories d'articles que l'utilisateur pourra écrire
      $query = 'INSERT INTO ' . $this->wpTablePrefix . "usermeta (`user_id`, `meta_key`, `meta_value`) VALUES "
      . "({$utilisateurMeta['U_ID']}, 'last_activity', '2017-05-19 15:06:16'), "
      . "({$utilisateurMeta['U_ID']}, 'first_name', {$this->wpDbConnection->quote($utilisateurMeta['U_SURNAME'])}), "
      . "({$utilisateurMeta['U_ID']}, 'last_name', {$this->wpDbConnection->quote($utilisateurMeta['U_NAME'])}), "
      . "({$utilisateurMeta['U_ID']}, 'nickname', {$this->wpDbConnection->quote($nickname)}), "
      . "({$utilisateurMeta['U_ID']}, 'description', ''), "
      . "({$utilisateurMeta['U_ID']}, 'rich_editing', 'true'), "
      . "({$utilisateurMeta['U_ID']}, 'comment_shortcuts', 'false'), "
      . "({$utilisateurMeta['U_ID']}, 'admin_color', 'fresh'), "
      . "({$utilisateurMeta['U_ID']}, 'use_ssl', '0'), "
      . "({$utilisateurMeta['U_ID']}, 'show_admin_bar_front', 'true'), "
      . "({$utilisateurMeta['U_ID']}, '" . $this->wpTablePrefix . "capabilities', $this->wpDbConnection->quote($capabilities)), "
      . "({$utilisateurMeta['U_ID']}, '" . $this->wpTablePrefix . "user_level', $this->wpDbConnection->quote($user_level)), "
      . "({$utilisateurMeta['U_ID']}, 'dismissed_wp_pointers', ''), "
      . "({$utilisateurMeta['U_ID']}, 'wp_dashboard_quick_press_last_post_id', '63'), " // c koi ?
      . "({$utilisateurMeta['U_ID']}, '_restrict_media', '1'), " // lié au plugin restrict author media
      . "({$utilisateurMeta['U_ID']}, '_access', $this->wpDbConnection->quote($access)), "
      . "({$utilisateurMeta['U_ID']}, 'bp_xprofile_visibility_levels', $this->wpDbConnection->quote($bp_xprofile_visibility_levels)) "
      . ";";

      try {
        $this->wpDbConnection->exec($query);

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
    ('1', {$utilisateurId}, {$this->wpDbConnection->quote($nickname)}, '2017-05-19 15:06:16')
    ON DUPLICATE KEY UPDATE `field_id`=VALUES(`field_id`), `user_id`=VALUES(`user_id`), `value`=VALUES(`value`), `last_updated`=VALUES(`last_updated`);";

    try {
      $this->wpDbConnection->exec($requete_pseudo_bp);
    } catch(Exception $e) {
      echo "-- ECHEC " . __FUNCTION__ . " REQUÊTE: [$requete_pseudo_bp]" . PHP_EOL;
      throw new MigrationException($e, $requete_pseudo_bp, __FUNCTION__);
    }

  }// end method insertUserIntoBpXprofileDataTable($utilisateur)

}
