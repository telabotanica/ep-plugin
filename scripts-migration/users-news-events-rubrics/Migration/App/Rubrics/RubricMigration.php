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

  /*
  * Ça c'est fait pour que les articles importés avant se retrouvent dans la bonne catégorie
  * (Du coup faut gerer les correspondances et tout, folie!)
  * Là dans l'état faut créer les catégories correctement coté Wordpress avant de lancer le script, puis corriger les noms/slugs toussa
  *
  * @todo revoir l'insertion, ça défonce les menus là... Voir : https://wordpress.stackexchange.com/a/78317
  * @todo vérifier que les évènements sont aussi migrés ou alors créer la fonction de migration équivalente pour eux
  */
  /**
   * Migrates rubrics from SPIP DB to WP DB.
   */
  public function migrate() {
    // Contient la table de correspondances rubrique(SPIP)-categorie(WP), indexée par rubrique
    $rubriqueCategorie = [];
    foreach (AnnuaireTelaBpProfileDataMap::getRubriqueCategoryArray()  as $correspondance) {
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
        $rubriqueCategorie[$rubrique] = $categorie[0]['term_id'];
      }

      // Initialisation des compteurs
      $compteur[$categorie[0]['term_id']] = 0;
    }


    $requeteActualitesRubriques = 'SELECT `id_article`, `id_rubrique`
    FROM `spip_articles` WHERE id_rubrique IN (' . AnnuaireTelaBpProfileDataMap::getSpipRubricsToBeMigrated() . ');' ;

    $actualites = $this->spipDbConnection->query($requeteActualitesRubriques)->fetchAll(PDO::FETCH_ASSOC);

    $compteur = 0;
    foreach ($actualites as $actualite) {
      $requete = 'INSERT INTO ' . $this->wpTablePrefix . 'term_relationships (`object_id`, `term_taxonomy_id`)
      VALUES(' . $actualite['id_article'] . ', ' . $rubriqueCategorie[$actualite['id_rubrique']] . ')
      ON DUPLICATE KEY UPDATE `object_id`=VALUES(`object_id`), `term_taxonomy_id`=VALUES(`term_taxonomy_id`);';

      $updateCompteur = 'INSERT INTO ' . $this->wpTablePrefix . 'term_taxonomy (`term_id`, `taxonomy`, `count`) '
      . 'VALUES(' . $rubriqueCategorie[$actualite['id_rubrique']] . ', "category", 1)'
      . 'ON DUPLICATE KEY UPDATE `term_id`=VALUES(`term_id`), `taxonomy`=VALUES(`taxonomy`), `count`=`count`+1'
      ;

      try {
        $this->wpDbConnection->exec($requete);
        $this->wpDbConnection->exec($updateCompteur);

        $compteur++;
      } catch(Exception $e) {
        echo "-- ECHEC " . __FUNCTION__ . " REQUÊTE: [$requete]" . PHP_EOL;
        // Bad! the exception cause could be $updateCompteur!!!
        throw new MigrationException($e, $requete, __FUNCTION__);
      }
    }

    echo '-- ' . $compteur . '/' . count($actualites) . ' rubriques d\'actualités migrées. ' . PHP_EOL;
  }


  // Retourne les id des rubriques SPIP à migrer
  function _rubriquesSpipAMigrer() {

    $rubriquesAMigrer = [];
    foreach (AnnuaireTelaBpProfileDataMap::getRubriqueCategoryArray() as $correspondance) {
      $rubriquesAMigrer[] = implode(',', $correspondance['rubrique-a-migrer']);
    }

    return implode(',', $rubriquesAMigrer);
  }

}
