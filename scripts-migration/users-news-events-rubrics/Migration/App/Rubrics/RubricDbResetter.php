<?php

namespace Migration\App\Rubrics;

use Migration\Api\QueryDbResetter;
use Migration\App\AnnuaireTelaBpProfileDataMap;
use \Exception;

// vide les term_taxonomy (WHERE term_id IN [listes des IDs des catégories d'articles et évènements migrés (voir correspondanceCategorieRubriques)])
// supprime les liens entre posts et catégories des posts importés
/**
 * Deletes previously imported rubric related data from WP DB.
 */
class RubricDbResetter extends QueryDbResetter {

    public function __construct($dbName) {
        parent::__construct($dbName);
        parent::setQueries([
          'DELETE FROM term_relationships JOIN posts ON (posts.ID = term_relationships.object_id AND posts.post_type = "post")',
        ]);
    }

    /**
     * Deletes previously imported rubric related data from WP DB.
     */
    public function resetDb() {

        $idCategorie = [];
        foreach (AnnuaireTelaBpProfileDataMap::getRubriqueCategoryArray() as $rubriques) {
          foreach ($rubriques['rubrique-a-migrer'] as $rubrique) {
            $idCategorie[] = $rubrique;
          }
        }
        $idCategorie = implode("','", $idCategorie);
        $sql = "DELETE FROM term_taxonomy WHERE term_id IN ('$idCategorie')";
        array_push($this->queries, $sql);
        parent::resetDb();

    }

}
