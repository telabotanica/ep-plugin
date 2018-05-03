<?php

namespace Migration\App\Users;

use Migration\Api\BaseMigration;
use Migration\Api\MigrationException;
use Migration\App\AnnuaireTelaBpProfileDataMap;
use \Exception;
use \PDO;

/**
* Migrates user profiles from Tela DB to WP/BP DB..
*/
class UserProfileMigration extends BaseMigration {

  /**
  * Migrates user profiles from Tela DB to WP/BP DB..
  */
  public function migrate() {

     $requete_supp = "SELECT *  FROM `annu_meta_valeurs` WHERE `amv_ce_colonne` in (99, 2, 137, 4, 8, 120, 133, 132, 125, 14, 15) AND (amv_valeur != ''  AND amv_valeur != 0)";
    $infos_supp = $this->telaDbConnection->query($requete_supp)->fetchAll(PDO::FETCH_ASSOC);

    foreach ($infos_supp as $infos) {
      switch ($infos['amv_ce_colonne']) {
        case 2: // Langues pratiquées
          //exemple a:3:{i:0;s:7:"Anglais";i:1;s:8:"Espagnol";i:2;s:7:"Italien";}
          $langues = explode(';;', $infos['amv_valeur']);
          if (count($langues)) {
            $supp[$infos['amv_cle_ligne']][$infos['amv_ce_colonne']] = serialize($langues);
          }

          break;
        case 4: // Expérience botanique
          $supp[$infos['amv_cle_ligne']][$infos['amv_ce_colonne']] = AnnuaireTelaBpProfileDataMap::getBotanicLevel($infos['amv_valeur']);
          break;
        case 133: // Vous êtes membre d'une association botanique ou en lien avec la botanique
          $supp[$infos['amv_cle_ligne']][$infos['amv_ce_colonne']] = AnnuaireTelaBpProfileDataMap::getNaturalistAssociationMembership($infos['amv_valeur']);

          break;
        case 15: // Conditions d'utilisation
          if (1 == $infos['amv_valeur']) {
            $supp[$infos['amv_cle_ligne']][$infos['amv_ce_colonne']] = serialize("J\'accepte les conditions d\'utilisation");
          }

          break;
        case 120: // Spécialités (zone géographique)
          // exemple: a:3:{i:0;s:16:"Zones tropicales";i:1;s:24:"Zones méditerranéennes";i:2;s:8:"Montagne";}
          $zones_geo = explode(';;', $infos['amv_valeur']);
          if (count($zones_geo)) {
            $supp[$infos['amv_cle_ligne']][$infos['amv_ce_colonne']] = serialize($zones_geo);
          }

          break;
        case 8: // Spécialités (groupe(s) de plantes étudié(s))
        case 14: // Inscription à la lettre d'actualité
        case 99: // Pseudo
        case 125: // Présentation
        case 132: // Adresse
        case 137: // Date de naissance
        default:
          $supp[$infos['amv_cle_ligne']][$infos['amv_ce_colonne']] = $infos['amv_valeur'];

          break;
      }
    }

    $requete = "SELECT `U_ID`, `U_NAME`, `U_SURNAME`, U_WEB, `U_CITY`, `U_COUNTRY`, pays, `U_ZIP_CODE` FROM `annuaire_tela`
      LEFT JOIN (SELECT  `amo_nom` AS pays,  `amo_abreviation` FROM `annu_meta_ontologie` WHERE  `amo_ce_parent` = 1074) liste_pays  ON `amo_abreviation` = `U_COUNTRY`";
    $utilisateurs = $this->telaDbConnection->query($requete)->fetchAll(PDO::FETCH_ASSOC);

    $compteur = 0;
    foreach ($utilisateurs as $utilisateur) {
      // on va essayer de trouver le département à partir du code postal
      if (preg_match('@(?:^(\d{2})(\d)\d{2}.*$)|(?:^(\d{2})\s.*$)@', $utilisateur['U_ZIP_CODE'], $matches)) {
        $numero_departement = $matches[1];
        $numero_complementaire = $matches[2]; // pour la corse (201/202) ou les  // DOM (97X) et TOM (98X)

        switch ($numero_departement) {
          case 975: // Saint-Pierre-et-Miquelon
          case 98: // TOM (98X)
            // on ne gère pas ces cas, considérés comme des pays à part dans le formulaire d'inscription
            break;
          case 97: // DOM (97X)
          case 20: // Corse (201/202)
            if ( null != AnnuaireTelaBpProfileDataMap::getDepartment($numero_departement . $numero_complementaire)) {
              $utilisateur['U_ZIP_CODE'] = AnnuaireTelaBpProfileDataMap::getDepartment($numero_departement . $numero_complementaire);
            }
            break;
          default:
            if (null != AnnuaireTelaBpProfileDataMap::getDepartment($numero_departement)) {
              $utilisateur['U_ZIP_CODE'] = AnnuaireTelaBpProfileDataMap::getDepartment($numero_departement);
            }
            break;
        }
      }

      $requeteInsert = "INSERT INTO " . $this->wpTablePrefix . "bp_xprofile_data (`field_id`, `user_id`, `value`, `last_updated`) VALUES
        ('3', {$utilisateur['U_ID']}, {$this->wpDbConnection->quote($utilisateur['pays'])}, '2017-05-19 15:06:16'),
        ('4', {$utilisateur['U_ID']}, {$this->wpDbConnection->quote($utilisateur['U_CITY'])}, '2017-05-19 15:06:16'),
        ('9', {$utilisateur['U_ID']}, {$this->wpDbConnection->quote($utilisateur['U_NAME'])}, '2017-05-19 15:06:16'),
        ('10', {$utilisateur['U_ID']}, {$this->wpDbConnection->quote($utilisateur['U_SURNAME'])}, '2017-05-19 15:06:16'),
        ('592', {$utilisateur['U_ID']}, {$this->wpDbConnection->quote($utilisateur['U_ZIP_CODE'])}, '2017-05-19 15:06:16'),
        ('21', {$utilisateur['U_ID']}, {$this->wpDbConnection->quote($utilisateur['U_WEB'])}, '2017-05-19 15:06:16')";
      if (isset($supp[$utilisateur['U_ID']])) {
        foreach ($supp[$utilisateur['U_ID']] as $num => $val){
          $requeteInsert .= ",(" . AnnuaireTelaBpProfileDataMap::getCategory($num) . ", {$utilisateur['U_ID']}, {$this->wpDbConnection->quote($val)}, '2017-05-19 15:06:16')";
        }
      }
      $requeteInsert .= "
        ON DUPLICATE KEY UPDATE `field_id`=VALUES(`field_id`), `user_id`=VALUES(`user_id`), `value`=VALUES(`value`), `last_updated`=VALUES(`last_updated`);";

      try {
        $this->wpDbConnection->exec($requeteInsert);
        // // Verbose
        // echo $compteur . "num dep=". $numero_departement .  PHP_EOL;
        $compteur++;
      } catch(Exception $e) {
        echo "-- ECHEC " . __FUNCTION__ . " REQUÊTE: [$requeteInsert]" . PHP_EOL;
        throw new MigrationException($e, $requeteInsert, __FUNCTION__);
      }
    }

    echo '-- ' . $compteur . '/' . count($utilisateurs) . ' profils d\'utilisateur migrés. ' . PHP_EOL;

  }


}
