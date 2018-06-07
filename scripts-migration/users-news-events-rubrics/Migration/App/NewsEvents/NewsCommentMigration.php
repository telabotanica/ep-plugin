<?php

namespace Migration\App\NewsEvents;

use Migration\Api\BaseMigration;
use Migration\Api\MigrationException;
use Migration\App\AnnuaireTelaBpProfileDataMap;
use \Exception;
use \PDO;

/**
 * Migrates news comments from SPIP DB to WP DB.
 */
class NewsCommentMigration extends BaseMigration {

  /**
   * Migrates news comments from SPIP DB to WP DB.
   */
  /*
  * Explication des champs de la table wp_posts : https://deliciousbrains.com/tour-wordpress-database/#wp_posts
  *
  * @todo : revoir la méthode d'ajout, là c'est un peu violent, ça écrase les menus, le footer, toussa
  * (wai les menus c'est stocké dans la table posts, deal with it)
  * Vérifier l'auto-incrément de la table Posts, 20000 ids sont censés être réservés aux articles importés
  * Voir : https://wordpress.stackexchange.com/a/78317
  */
  public function migrate() {

    $requeteActualitesCommentaires = 'SELECT `id_forum` , `id_article` , `auteur` , `email_auteur` , "" AS url, `ip` , `date_heure` , `date_heure` AS gmt, `texte` , "0" AS karma, replace(`statut`, "publie", "1") , "" AS agent, "" AS TYPE , `id_parent` , COALESCE(a.U_ID, 0) as `id_auteur`
    FROM `spip_forum`
    LEFT JOIN tela_prod_v4.annuaire_tela a ON email_auteur = a.U_MAIL
    WHERE id_article IN (SELECT `id_article` FROM `spip_articles` WHERE id_rubrique IN (' . AnnuaireTelaBpProfileDataMap::getSpipRubricsToBeMigrated() . ')) AND statut = "publie"';

    $actualitesCommentaires = $this->spipDbConnection->query($requeteActualitesCommentaires)->fetchAll(PDO::FETCH_ASSOC);

    $compteur = 0;
    foreach ($actualitesCommentaires as $actualiteCommentaire) {
      $requete = 'INSERT INTO ' . $this->wpTablePrefix . 'comments (`comment_ID`, `comment_post_ID`, `comment_author`, `comment_author_email`, `comment_author_url`, `comment_author_IP`, `comment_date`, `comment_date_gmt`, `comment_content`, `comment_karma`, `comment_approved`, `comment_agent`, `comment_type`, `comment_parent`, `user_id`) '
      . 'VALUES(' . implode(', ', array_map(array($this->wpDbConnection, 'quote'), $actualiteCommentaire)) . ')
      ON DUPLICATE KEY UPDATE `comment_ID`=VALUES(`comment_ID`), `comment_post_ID`=VALUES(`comment_post_ID`), `comment_author`=VALUES(`comment_author`), `comment_author_email`=VALUES(`comment_author_email`), `comment_author_url`=VALUES(`comment_author_url`), `comment_author_IP`=VALUES(`comment_author_IP`), `comment_date`=VALUES(`comment_date`), `comment_date_gmt`=VALUES(`comment_date_gmt`), `comment_content`=VALUES(`comment_content`), `comment_karma`=VALUES(`comment_karma`), `comment_approved`=VALUES(`comment_approved`), `comment_agent`=VALUES(`comment_agent`), `comment_type`=VALUES(`comment_type`), `comment_parent`=VALUES(`comment_parent`), `user_id`=VALUES(`user_id`);';

      $updateCompteurCommentaires = 'UPDATE ' . $this->wpTablePrefix . 'posts SET comment_count = comment_count + 1 WHERE ID = ' . $actualiteCommentaire['id_article'] . ';';

      try {
        $this->wpDbConnection->exec($requete);
        $this->wpDbConnection->exec($updateCompteurCommentaires);
        // // Verbose
        // echo $compteur . PHP_EOL;
        $compteur++;
      } catch(Exception $e) {
        echo "-- ECHEC " . basename(__FILE__) . ':' . __FUNCTION__ . " REQUÊTE: [$requete]" . PHP_EOL;
          throw new MigrationException($e, $requete, basename(__FILE__) . ':' . __FUNCTION__);
      }
    }

    echo '-- ' . $compteur . '/' . count($actualitesCommentaires) . ' commentaires d\'actualité migrés. ' . PHP_EOL;
  }


}
