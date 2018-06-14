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
   * Donc concernant l'adresse, faut prendre les champs bf_adresse et bf_cp_lieu_evenement
   * Pour la localisation GPS c'est bf_latitude et bf_longitude
   * exemple de meta d'adresse :
   * {"type":"address","name":"3 Rue d'Alsace","city":"Montpellier","country":"France","countryCode":"fr","administrative":"Occitanie","latlng":{"lat":43.6058,"lng":3.8831},"postcode":"34070","value":"3 Rue d'Alsace, Montpellier, Occitanie, France"}
   * Pour le prix :
   *  faut déduire is_free à partir du contenu de la colonne bf_tarif_individuel
   *  (bf_tarif_entreprise bf_tarif_opca sont toujours vides)
   *  (si gratuit on trouve souvent la valeur 'GRATUIT', '0€' ou 'entrée libre')
   * Pour le contact c'est bf_nom_contact (y'a tout dedans alors que bf_mail bf_telephone sont vides)
   *   en base on trouve ces metas :
   *   contact_0_name (texte)
   *   contact_0_description (texte)
   *   contact_0_phone (texte)
   *   contact_0_email (email valide)
   *   contact_0_website (url valide + protocol)
   *   contact_0_image (un id d'image de la galerie mais ça marche à l'affichage pour le moment)
   * Pour les dates, date_end doit être null si bf_date_fin_evenement === bf_date_debut_evenement
  */
  public function migrate() {

    $fin = 'AS meta_value FROM `bazar_fiche` WHERE year(`bf_date_debut_validite_fiche`) >= 2017 AND bf_statut_fiche = 1';

    // brouillon d'exemple
    // $place = json_encode([
    //   'type' => 'address',
    //   'name' => $bf_adresse,
    //   'city' => '',
    //   'country' => '',
    //   'countryCode' => '',
    //   'administrative' => '',
    //   'latlng' => [
    //     'lat' => $bf_latitude,
    //     'lng' => $bf_longitude,
    //   ],
    //   'postcode' => $bf_cp_lieu_evenement,
    //   'value' => $bf_adresse . ' ' . $bf_cp_lieu_evenement
    // ]);

    // autre brouillon pour une approche plus simple
    // CONCAT('{"type":"address","name":"', bf_adresse,'","city":"","country":"","countryCode":"","administrative":"","latlng":{"lat":', bf_latitude,',"lng":', bf_longitude,'},"postcode":"', bf_cp_lieu_evenement,'","value":"', bf_adresse, ' ', bf_cp_lieu_evenement,'"}')

    $requeteEvenementsChampsACF = "
    SELECT `bf_id_fiche`+10000 AS post_id,  '_edit_lock' AS meta_key, CONVERT('1476892394:1'  USING utf8) $fin UNION
    SELECT `bf_id_fiche`+10000 AS post_id,  '_edit_last' AS meta_key, CONVERT('1'  USING utf8) $fin UNION
    SELECT `bf_id_fiche`+10000 AS post_id,  '_alp_processed' AS meta_key, CONVERT('1476891070'  USING utf8) $fin UNION

    SELECT `bf_id_fiche`+10000 AS post_id,  '_place' AS meta_key, CONVERT('field_580366d5a9e01'  USING utf8) $fin UNION
    SELECT `bf_id_fiche`+10000 AS post_id,  'place' AS meta_key, CONCAT('{\"type\":\"address\",\"name\":\"', CONVERT(bf_adresse USING utf8),'\",\"city\":\"\",\"country\":\"\",\"countryCode\":\"\",\"administrative\":\"\",\"latlng\":{\"lat\":', CONVERT(bf_latitude USING utf8),',\"lng\":', CONVERT(bf_longitude USING utf8),'},\"postcode\":\"', CONVERT(bf_cp_lieu_evenement USING utf8),'\",\"value\":\"', CONVERT(bf_adresse USING utf8), ' ', CONVERT(bf_cp_lieu_evenement USING utf8),'\"}') $fin UNION

    SELECT `bf_id_fiche`+10000 AS post_id,  '_contact_0_description' AS meta_key, CONVERT('field_580e45789024f'  USING utf8) $fin UNION
    SELECT `bf_id_fiche`+10000 AS post_id,  'contact_0_description' AS meta_key, CONVERT(bf_nom_contact  USING utf8) $fin UNION

    SELECT `bf_id_fiche`+10000 AS post_id,  '_prices' AS meta_key, CONVERT('field_5803a6059a5d1'  USING utf8) $fin UNION
    SELECT `bf_id_fiche`+10000 AS post_id,  'prices' AS meta_key, CONVERT(bf_tarif_individuel  USING utf8) $fin UNION
    SELECT `bf_id_fiche`+10000 AS post_id,  '_is_free' AS meta_key, CONVERT('field_5803a5d09a5d0'  USING utf8) $fin UNION
    SELECT `bf_id_fiche`+10000 AS post_id,  'is_free' AS meta_key, CONVERT('0'  USING utf8) $fin UNION

    SELECT `bf_id_fiche`+10000 AS post_id,  '_image' AS meta_key, CONVERT('field_5803a65a08014'  USING utf8) $fin UNION
    SELECT `bf_id_fiche`+10000 AS post_id,  'image' AS meta_key, CONVERT(''  USING utf8) $fin UNION
    SELECT `bf_id_fiche`+10000 AS post_id,  '_contact' AS meta_key, CONVERT('field_580e45279024d'  USING utf8) $fin UNION
    SELECT `bf_id_fiche`+10000 AS post_id,  'contact' AS meta_key, CONVERT('1'  USING utf8) $fin UNION

    SELECT `bf_id_fiche`+10000 AS post_id,  '_description' AS meta_key, CONVERT('field_580366bfa9e00'  USING utf8) $fin UNION
    SELECT `bf_id_fiche`+10000 AS post_id,  'description' AS meta_key, CONVERT(bf_description  USING utf8) $fin UNION

    SELECT `bf_id_fiche`+10000 AS post_id,  '_date' AS meta_key, CONVERT('field_580364c892ee1'  USING utf8) $fin UNION
    SELECT `bf_id_fiche`+10000 AS post_id,  'date' AS meta_key, CONVERT(date_format(bf_date_debut_evenement, '%Y%m%d') USING utf8) $fin UNION
    SELECT `bf_id_fiche`+10000 AS post_id,  '_date_end' AS meta_key, CONVERT('field_5803659792ee5'  USING utf8) $fin UNION
    SELECT `bf_id_fiche`+10000 AS post_id,  'date_end' AS meta_key, IF(CONVERT(date_format(bf_date_fin_evenement, '%Y%m%d') USING utf8) = CONVERT(date_format(bf_date_debut_evenement, '%Y%m%d') USING utf8), null, CONVERT(date_format(bf_date_fin_evenement, '%Y%m%d') USING utf8)) $fin;";

    $evenementsChampsACF = $this->telaDbConnection
      ->exec($requeteEvenementsChampsACF)
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
