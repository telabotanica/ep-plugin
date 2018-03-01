<?php

namespace Migration\App\Rubrics;

use Migration\Api\BaseMigration ;
use Migration\Api\MigrationException ;
use Migration\App\AnnuaireTelaBpProfileDataMap ;
use \Exception;
use \PDO;

/**
 * Migrates rubrics from SPIP DB to WP DB.
 */
class RubricMigration extends BaseMigration {

  private $compteur = 0;

  private $rubriqueCategorie = [];

  /**
   * Migrates rubrics from SPIP DB to WP DB.
   */
  public function migrate() {
    // Contient la table de correspondances rubrique(SPIP)-categorie(WP), indexée par rubrique
    foreach (AnnuaireTelaBpProfileDataMap::getCorrespondencesCategory() as $correspondance) {
      $requeteCategorie = 'SELECT term_id FROM ' . $this->wpTablePrefix . 'terms WHERE name = ' . $this->wpDbConnection->quote($correspondance['titre']) . ' AND slug = ' . $this->wpDbConnection->quote($correspondance['slug']) . ';';

      $categorie = $this->wpDbConnection->query($requeteCategorie)->fetchAll(PDO::FETCH_ASSOC);

      if (0 === count($categorie)) {
        var_dump($correspondance);
        die('catégorie inéxistante, est-elle bien créée coté wordpress ? est-ce le bon slug ?');
      } elseif (1 < count($categorie)) {
        die('catégorie multiple, je suis censé deviner laquelle est la bonne ? merci de faire le ménage :)');
      }

      // On fait correspondre à chaque rubrique à migrer sa catégorie
      foreach ($correspondance['rubrique-a-migrer'] as $rubrique) {
        $this->rubriqueCategorie[$rubrique] = $categorie[0]['term_id'];
      }
    }



    $requeteActualitesRubriques = 'SELECT `id_article` as id, `id_rubrique`
    FROM `spip_articles` WHERE id_rubrique IN (' . AnnuaireTelaBpProfileDataMap::getSpipRubricsToBeMigrated() . ');' ;

    $actualites = $this->spipDbConnection->query($requeteActualitesRubriques)->fetchAll(PDO::FETCH_ASSOC);

    $this->createTermLink($actualites);



    $requeteFichesBazar = 'SELECT `bf_id_fiche`+10000 as id, bfvl_valeur as id_rubrique
    FROM bazar_fiche
    JOIN bazar_fiche_valeur_liste ON bf_id_fiche = bfvl_ce_fiche
    JOIN bazar_nature ON bn_id_nature = bfvl_valeur
    WHERE bfvl_ce_liste = 31';

    $events = $this->telaDbConnection->query($requeteFichesBazar)->fetchAll(PDO::FETCH_ASSOC);

    $this->createTermLink($events);


    $count = count($actualites) + count($events);
    echo '-- ' . $this->compteur . '/' . $count . ' rubriques d\'actualités et d\'évènements migrées. ' . PHP_EOL;
  }

  private function createTermLink($elements) {
    foreach ($elements as $element) {
      $requete = 'INSERT INTO ' . $this->wpTablePrefix . 'term_relationships (`object_id`, `term_taxonomy_id`)
      VALUES(' . $element['id'] . ', ' . $this->rubriqueCategorie[$element['id_rubrique']] . ')
      ON DUPLICATE KEY UPDATE `object_id`=VALUES(`object_id`), `term_taxonomy_id`=VALUES(`term_taxonomy_id`);';

      $updateCompteur = 'INSERT INTO ' . $this->wpTablePrefix . 'term_taxonomy (`term_id`, `taxonomy`, `count`) '
      . 'VALUES(' . $this->rubriqueCategorie[$element['id_rubrique']] . ', "category", 1)'
      . 'ON DUPLICATE KEY UPDATE `term_id`=VALUES(`term_id`), `taxonomy`=VALUES(`taxonomy`), `count`=`count`+1'
      ;

      try {
        $this->wpDbConnection->exec($requete);
        $this->wpDbConnection->exec($updateCompteur);

        $this->compteur++;
      } catch(Exception $e) {
        echo "-- ECHEC " . __FUNCTION__ . " REQUÊTE: [$requete]" . PHP_EOL;
        // Bad! the exception cause could be $updateCompteur!!!
        throw new MigrationException($e, $requete, __FUNCTION__);
      }
    }
  }
}
