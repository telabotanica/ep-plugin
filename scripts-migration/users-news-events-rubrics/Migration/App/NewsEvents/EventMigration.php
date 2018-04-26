<?php

namespace Migration\App\NewsEvents;

use Migration\Api\BaseMigration;
use Migration\Api\MigrationException;
use Migration\App\Utilz\WpmlIclTranslationDAO;
use \Exception;
use \PDO;
use \DateTime;
use \DateTimeZone;

class EventMigration extends BaseMigration {

  /**
   * Migrates events from Tela DB (bazar-fiche) to WP DB.
   */
  public function migrate() {

    $wpmlIclTranslationDao = new WpmlIclTranslationDAO();
    $trGrId = $wpmlIclTranslationDao->getMaxTranslationGroupId();

    $requeteEvenements = "SELECT `bf_id_fiche`+10000 AS `ID`, `bf_ce_utilisateur` AS  `post_author`, `bf_date_creation_fiche` AS  `post_date`, `bf_date_creation_fiche` AS  `post_date_gmt`, '' AS  `post_content`, `bf_titre` AS  `post_title`, '' AS  `post_excerpt`, 'publish' AS  `post_status`, 'open' AS  `comment_status`, 'open' AS  `ping_status`, '' AS  `post_password`, `bf_id_fiche`+10000 AS  `post_name`, '' AS  `to_ping`, '' AS  `pinged`, `bf_date_maj_fiche` AS  `post_modified`, `bf_date_maj_fiche` AS  `post_modified_gmt`, '' AS  `post_content_filtered`, 0 AS  `post_parent`, `bf_id_fiche`+10000 AS  `guid`, 0 AS  `menu_order`, 'post' AS  `post_type`, '' AS  `post_mime_type`, 0 AS  `comment_count` FROM `bazar_fiche` WHERE year(`bf_date_debut_validite_fiche`) >= 2017;";

    $evenements = $this->telaDbConnection->query($requeteEvenements)->fetchAll(PDO::FETCH_ASSOC);

    $compteur = 0;
    foreach ($evenements as $evenement) {
      // gestion des dates normales et dates en GMT
      $date = new DateTime($evenement['post_date'], new DateTimeZone('Europe/Paris'));
      $evenement['post_date_gmt'] = $date->setTimezone(new DateTimeZone('GMT'))->format('Y-m-d H:i:s');
      $date = new DateTime($evenement['post_modified'], new DateTimeZone('Europe/Paris'));
      $evenement['post_modified_gmt'] = $date->setTimezone(new DateTimeZone('GMT'))->format('Y-m-d H:i:s');

      $requete = 'INSERT INTO ' . $this->wpTablePrefix . 'posts (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES'
      . '(' . implode(', ', array_map(array($this->wpDbConnection, 'quote'), $evenement)) . ')'
      . 'ON DUPLICATE KEY UPDATE `ID`=VALUES(`ID`), `post_author`=VALUES(`post_author`), `post_date`=VALUES(`post_date`), `post_date_gmt`=VALUES(`post_date_gmt`), `post_content`=VALUES(`post_content`), `post_title`=VALUES(`post_title`), `post_excerpt`=VALUES(`post_excerpt`), `post_status`=VALUES(`post_status`), `comment_status`=VALUES(`comment_status`), `ping_status`=VALUES(`ping_status`), `post_password`=VALUES(`post_password`), `post_name`=VALUES(`post_name`), `to_ping`=VALUES(`to_ping`), `pinged`=VALUES(`pinged`), `post_modified`=VALUES(`post_modified`), `post_modified_gmt`=VALUES(`post_modified_gmt`), `post_content_filtered`=VALUES(`post_content_filtered`), `post_parent`=VALUES(`post_parent`), `guid`=VALUES(`guid`), `menu_order`=VALUES(`menu_order`), `post_type`=VALUES(`post_type`), `post_mime_type`=VALUES(`post_mime_type`), `comment_count`=VALUES(`comment_count`);'
      ;

      try {
        $this->wpDbConnection->exec($requete);
        $lastInsertId =  $this->wpDbConnection->lastInsertId();
        $wpmlIclTranslationDao->create("'post_post'", $lastInsertId, $trGrId, "'fr'", 'NULL');
        $trGrId++;
        $compteur++;
      } catch(Exception $e) {
        echo "-- ECHEC " . __FUNCTION__ . " REQUÊTE: [$requete]" . PHP_EOL;
        $tb2beRestored = [$e->getMessage(), $e->getCode(), $this->wpTablePrefix . 'posts'];
        throw new MigrationException($e, $requete, __FUNCTION__);
      }

      // collecte les infos pour l'enregistrement des redirections 301 des articles
      $ancienne_url = 'http://www.tela-botanica.org/page:evenements?action=8&id_fiche=' . ($evenement['ID'] - 10000);
      // BUG IN THE SCIPT!!!!!!!!!!!!!!!!!
      //$insert_redirection[] = '(' . $article['ID'] . ', ' . $wpDbConnection->quote($ancienne_url) . ')';
      $insert_redirection[] = '(' . $evenement['ID'] . ', ' . $this->wpDbConnection->quote($ancienne_url) . ')';

      $query = 'INSERT INTO ' . $this->wpTablePrefix . 'slug_history (`post_id`, `url`)
      VALUES ' . implode(', ', $insert_redirection) . '
      ON DUPLICATE KEY UPDATE `post_id`=VALUES(`post_id`), `url`=VALUES(`url`);';

      try {
        $this->wpDbConnection->exec($query);
      } catch(Exception $e) {
        echo "-- ECHEC " . __FUNCTION__ . " REQUÊTE: [$query]" . PHP_EOL;

        if (true !== $modeBourrin) {
          throw new MigrationException($e, $query, __FUNCTION__);
        }
      }
    }

    echo '-- ' . $compteur . '/' . count($evenements) . ' évènements migrés. ' . PHP_EOL;



  }

}
