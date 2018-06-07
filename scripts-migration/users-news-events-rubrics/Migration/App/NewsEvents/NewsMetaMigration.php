<?php

namespace Migration\App\NewsEvents;

use Migration\Api\BaseMigration;
use Migration\Api\MigrationException;
use Migration\App\AnnuaireTelaBpProfileDataMap;
use \Exception;
use \PDO;

/**
 * Migrates news meta from SPIP DB to WP DB.
 */
class NewsMetaMigration extends BaseMigration {

  public function migrate() {

    $requete = "SELECT spip_articles.`id_article` AS ID,
    replace(replace(replace(replace(replace(replace(replace(replace(replace(convert( convert( texte USING latin1 ) USING utf8 ),'{{{{','<chapo>'), '}}}}', '</chapo>'), '{{{','<h2>'), '}}}', '</h2>'), '{{', '<strong>'), '}}', '</strong>'), '{', '<em>'), '}', '</em>'), '_ ', '') AS post_content
    FROM `spip_articles` LEFT JOIN spip_auteurs_articles ON spip_auteurs_articles.`id_article` =  spip_articles.`id_article`
    WHERE id_rubrique in ( " . AnnuaireTelaBpProfileDataMap::getSpipRubricsToBeMigrated() . " ) GROUP BY ID";
    $articles = $this->spipDbConnection->query($requete)->fetchAll(PDO::FETCH_ASSOC);

    $compteurSucces = 0;
    foreach ($articles as $article) {
      // search for chapo (intro) in post content
      if (preg_match("@{{{{(.*)}}}}@", $article['post_content'], $matches)) {

        $req = 'INSERT INTO ' . $this->wpTablePrefix . 'postmeta (`post_id`, `meta_key`, `meta_value`) VALUES'
          . '(`:postId`, `intro`, `:chapo`),'
          . '(`:postId`, `_intro`, `field_582b32add4527`)'
        ;

        try {
          $this->wpDbConnection->exec($req, [
            ':postId' => $article['ID'],
            ':chapo' => $matches[1],
          ]);
          // // Verbose
          // echo $compteurSucces . PHP_EOL;
          $compteurSucces++;
        } catch(Exception $e) {
          echo "-- ECHEC " . basename(__FILE__) . ':' . __FUNCTION__ . " REQUÊTE: [$req]" . PHP_EOL;

          throw new MigrationException($e, $req, basename(__FILE__) . ':' . __FUNCTION__);
        }
      }
    }

    echo '-- ' . $compteurSucces . ' metas d\'actualités migrées. ' . PHP_EOL;
  }
}
