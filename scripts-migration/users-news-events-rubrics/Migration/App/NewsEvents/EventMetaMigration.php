<?php

namespace Migration\App\NewsEvents;

use Migration\Api\BaseMigration;
use Migration\Api\MigrationException;
use \PDO;
use \Exception;

/**
 * Migrates event metas.
 */
class EventMetaMigration  extends BaseMigration {

  /**
   * Migrates event metas.
   */
  /*
   * Donc concernant l'adresse, faut prendre les champs bf_lieu_evenement bf_cp_lieu_evenement et bf_adresse pi faire un string potable avec.
   * Pour le prix faut déduire is_free à partir du contenu des colonnes bf_tarif_individuel bf_tarif_entreprise bf_tarif_opca
   * Pour le contact c'est bf_nom_contact bf_mail bf_telephone (voir si c'est pas plus compliqué en fait, genre faut un tableau)
   * Pour les dates, date_end doit être null si bf_date_fin_evenement === bf_date_debut_evenement
  */
  public function migrate() {

    $fin = 'AS meta_value FROM `bazar_fiche` WHERE year(`bf_date_debut_validite_fiche`) >= 2017';

    $requeteEvenementsChampsACF = "SELECT `bf_id_fiche`+10000 AS post_id,  '_edit_lock' AS meta_key, CONVERT(CONVERT('1476892394:1'  USING utf8)  USING utf8) :fin UNION
    SELECT `bf_id_fiche`+10000 AS post_id,  '_edit_last' AS meta_key, CONVERT('1'  USING utf8) :fin UNION
    SELECT `bf_id_fiche`+10000 AS post_id,  '_alp_processed' AS meta_key, CONVERT('1476891070'  USING utf8) :fin UNION

    SELECT `bf_id_fiche`+10000 AS post_id,  '_place' AS meta_key, CONVERT('field_580366d5a9e01'  USING utf8) :fin UNION
    SELECT `bf_id_fiche`+10000 AS post_id,  'place' AS meta_key, CONVERT('a:3:{s:7:\"address\";s:47:\"53 Bd Bonne Nouvelle, 34000 Montpellier, France\";s:3:\"lat\";s:17:\"43.61335152461514\";s:3:\"lng\";s:17:\"3.880716562271118\";}'  USING utf8) :fin UNION

    SELECT `bf_id_fiche`+10000 AS post_id,  '_contact_0_description' AS meta_key, CONVERT('field_580e45789024f'  USING utf8) :fin UNION
    SELECT `bf_id_fiche`+10000 AS post_id,  'contact_0_description' AS meta_key, CONVERT(bf_nom_contact  USING utf8) :fin UNION

    SELECT `bf_id_fiche`+10000 AS post_id,  '_prices' AS meta_key, CONVERT('field_5803a6059a5d1'  USING utf8) :fin UNION
    SELECT `bf_id_fiche`+10000 AS post_id,  'prices' AS meta_key, CONVERT(bf_tarif_individuel  USING utf8) :fin UNION
    SELECT `bf_id_fiche`+10000 AS post_id,  '_is_free' AS meta_key, CONVERT('field_5803a5d09a5d0'  USING utf8) :fin UNION
    SELECT `bf_id_fiche`+10000 AS post_id,  'is_free' AS meta_key, CONVERT('0'  USING utf8) :fin UNION

    SELECT `bf_id_fiche`+10000 AS post_id,  '_image' AS meta_key, CONVERT('field_5803a65a08014'  USING utf8) :fin UNION
    SELECT `bf_id_fiche`+10000 AS post_id,  'image' AS meta_key, CONVERT('7669'  USING utf8) :fin UNION
    SELECT `bf_id_fiche`+10000 AS post_id,  '_contact' AS meta_key, CONVERT('field_580e45279024d'  USING utf8) :fin UNION
    SELECT `bf_id_fiche`+10000 AS post_id,  'contact' AS meta_key, CONVERT('1'  USING utf8) :fin UNION

    SELECT `bf_id_fiche`+10000 AS post_id,  '_description' AS meta_key, CONVERT('field_580366bfa9e00'  USING utf8) :fin UNION
    SELECT `bf_id_fiche`+10000 AS post_id,  'description' AS meta_key, CONVERT(bf_description  USING utf8) :fin UNION

    SELECT `bf_id_fiche`+10000 AS post_id,  '_date' AS meta_key, CONVERT('field_580364c892ee1'  USING utf8) :fin UNION
    SELECT `bf_id_fiche`+10000 AS post_id,  'date' AS meta_key, CONVERT(date_format(bf_date_debut_evenement, '%Y%m%d') USING utf8) :fin UNION
    SELECT `bf_id_fiche`+10000 AS post_id,  '_date_end' AS meta_key, CONVERT('field_5803659792ee5'  USING utf8) :fin UNION
    SELECT `bf_id_fiche`+10000 AS post_id,  'date_end' AS meta_key, IF(CONVERT(date_format(bf_date_fin_evenement, '%Y%m%d') USING utf8) = CONVERT(date_format(bf_date_debut_evenement, '%Y%m%d') USING utf8), null, CONVERT(date_format(bf_date_fin_evenement, '%Y%m%d') USING utf8)) :fin;";

    $evenementsChampsACF = $this->telaDbConnection
      ->exec($requeteEvenementsChampsACF, [':fin' => $fin])
      ->fetchAll(PDO::FETCH_ASSOC)
    ;

    $compteur = 0;
    foreach ($evenementsChampsACF as $champACF) {
      $requete = 'INSERT INTO ' . $this->wpTablePrefix . 'postmeta (`post_id`, `meta_key`, `meta_value`) VALUES'
      . '(' . implode(', ', array_map(array($this->wpDbConnection, 'quote'), $champACF)) . ')'
      . 'ON DUPLICATE KEY UPDATE `post_id`=VALUES(`post_id`), `meta_key`=VALUES(`meta_key`), `meta_value`=VALUES(`meta_value`);'
      ;

      try {
        $this->wpDbConnection->exec($requete);

        $compteur++;
      } catch(Exception $e) {
        echo "-- ECHEC " . basename(__FILE__) . ':' . __FUNCTION__ . " REQUÊTE: [$requete]" . PHP_EOL;
        throw new MigrationException($e, $requete, basename(__FILE__) . ':' . __FUNCTION__);
      }
    }

    echo '-- ' . $compteur . '/' . count($evenementsChampsACF) . ' meta d\'évènements migrées. ' . PHP_EOL;
  }

}
