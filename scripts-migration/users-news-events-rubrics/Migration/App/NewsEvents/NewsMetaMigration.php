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
      if (preg_match("@<chapo>(.*)</chapo>@", $article['post_content'], $matches)) {

        $req = 'INSERT INTO ' . $this->wpTablePrefix . 'postmeta (`post_id`, `meta_key`, `meta_value`) VALUES '
          . '(:postId, "intro", :chapo), '
          . '(:postId2, "_intro", "field_582b32add4527")'
        ;

        try {
          $this->wpDbConnection->exec($req, [
            ':postId' => $article['ID'],
            ':postId2' => $article['ID'],
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

      // search for iframe and there height
      if (preg_match_all("@<iframe src=\"http(.*)\" (?:.*height: (\d+)px.*)?<\/iframe>@", $article['post_content'], $matches)) {
        $values = '';
        $components = [];
        foreach($matches[0] as $id => $match) {
          $components[] = 'embed';
          $values .= "
            ({$article['ID']}, 'components_{$id}_method',  'iframe'),
            ({$article['ID']}, '_components_{$id}_method', 'field_581795fe075cc_field_5817976d9ed35'),
            ({$article['ID']}, 'components_{$id}_iframe',  'https{$matches[1][$id]}'),
            ({$article['ID']}, '_components_{$id}_iframe', 'field_581795fe075cc_field_5817972c0cb54'),
            ({$article['ID']}, 'components_{$id}_height',  '{$matches[2][$id]}'),
            ({$article['ID']}, '_components_{$id}_height', 'field_581795fe075cc_field_582c5263899c7'),
            ({$article['ID']}, 'components_{$id}_description',  ''),
            ({$article['ID']}, '_components_{$id}_description', 'field_581795fe075cc_field_58179c698ffe2'),
          ";
        }

        $components = serialize($components);
        $values .= "
          ({$article['ID']}, 'components',  '$components'),
          ({$article['ID']}, '_components', 'field_58177612a0f3b')
        ";

        $req = "INSERT INTO {$this->wpTablePrefix}postmeta (`post_id`, `meta_key`, `meta_value`) VALUES $values ";

        try {
          $this->wpDbConnection->exec($req);
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
