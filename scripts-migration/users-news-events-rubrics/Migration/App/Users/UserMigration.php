<?php

namespace Migration\App\Users;

use Migration\App\Users\BpPseudoBuilder;
use Migration\Api\BaseMigration;
use Migration\Api\MigrationException;
use \Exception;
use \PDO;

/* @implementationNotes
Dans la table (wp_)users :
  -user_nicename : unique, éditable, suffixé si déjà utilisé, de
                   préférence l'intitulé, voir suffixé avec un rand ou
                   l'ID
  -user_login : unique, immuable, prendra la valeur de user_nicename
  -display_name : éditable (de base WordPress le rempli en fonction d'un
                  réglage, y'a le choix entre différents trucs genre
                  prénom, nom, prénom + nom, ou le nickname)
  -nickname : éditable (équivalent du pseudo, mais coté WP) : intitulé,
              affiché sur le site
              (affiché, mais où ? je ne vois que le pseudo bp en fait)

  // recopier ces metas dans celles de wordpress


Dans la table bp_xprofile_data :
  -xprofileData[champ 1] (c'est le pseudo BP) non-unique, éditable. Il est
      utilisé notamment pour afficher le nom de l'auteur d'un commentaire

(lorsque le profil BP est modifié, sa valeur est copiée dans user_nicename
dans sa version suffixée, et en version originale [saisie par
l'utilisateur] dans display_name et nickname)

Donc pour l'import il faut générer un user_nicename/user_login/pseudo_bp
uniques. Basés sur le pseudo, sinon sur prenom-nom
Tandis que nickname aura la valeur du pseudo actuel
Et display_name prendra la valeur de pseudo, et sinon prenom-nom
*/

/**
 * Migrates all users from Tela DB to WP/BP DB.
 */
class UserMigration extends BaseMigration {


  private $userCount = 0;

  /**
  * Migre les utilisateurs vers la table WordPress
  *
  * Si ça a déjà été fait, ça écrase les valeurs éxistantes coté WordPress
  * Peut donc être relancé plusieurs fois avant la mise en prod
  */
  public function migrate() {

    $utilisateurs = $this->fetchTelaDbUsers();

    foreach ($utilisateurs as $utilisateur) {
      $intitule = $utilisateur['U_SURNAME'] . ' ' . $utilisateur['U_NAME'];

      $usedPseudo = $this->hasUserChosenPseudo($utilisateur['ID']);


      // Has the user chosen a pseudo?
      if ($usedPseudo) {

        $pseudo = $this->getUserPseudo($utilisateur['ID']);

        // If the user has chosen a pseudo, use it as $intitule
        if ($usedPseudo && $pseudo) {
          // // Verbose
          //var_dump($pseudo);
          $intitule = $pseudo;
        }

      }

      // Normalize $intitule as WP would
      $futur_pseudo = BpPseudoBuilder::buildPseudo($intitule);
      $unique = $futur_pseudo;

      $count = 0;
      do {
        $count++;
        if ($count%100 === 0) {
          echo($count . 'ème étape pour ' . $unique);
        }
        $existant = $this->isExistingUser($unique);
        // si ça existe déjà on suffixe et on recommence
        if (true === $existant) {
          $unique = $futur_pseudo . '-' . rand(0, 1000);
        }
      } while (true === $existant);

      $utilisateur['user_nicename'] = $unique;
      $utilisateur['user_login'] = $unique;
      $utilisateur['display_name'] = $intitule;
      // $utilisateur['nickname'] = $pseudo; // nickname est dans les user_meta
      unset($utilisateur['U_SURNAME']);
      unset($utilisateur['U_NAME']);

      $this->insertUserIntoWpUserTable($utilisateur);

    }
    echo('-- ' . $this->userCount . '/' . count($utilisateurs) .
      ' utilisateurs migrés. ' . PHP_EOL);

  }

  private function isExistingUser($username) {

    $existant = false;
    // on cherche si un utilisateur existant possède déjà ces propriétés
    foreach (['user_nicename', 'user_login'] as $champ) {
      $requete_pseudo = "SELECT $champ FROM " . $this->wpTablePrefix . "users WHERE $champ = '$username'";
      // die(var_dump($requete_pseudo));

      try {
        $utilisateur_existant = $this->wpDbConnection->query($requete_pseudo)->fetchAll(PDO::FETCH_ASSOC);
        // die(var_dump($utilisateur_existant));
      } catch(Exception $e) {
        echo "-- ECHEC " . __FUNCTION__ . " REQUÊTE: [$requete_pseudo]" . PHP_EOL;
        throw new MigrationException($e, $requete_pseudo, __FUNCTION__);
      }

      if (!empty($utilisateur_existant)) {
        $existant = true;
        break;
      }
    }

    return $existant;

  }// end isExistingUser($unique)


  /**
   * Inserts a new record into (wp_)users with the given user informations.
   *
   * @parameter $utilisateur a map containing user informations.
   */
  private function insertUserIntoWpUserTable($utilisateur) {

    $utilisateur_values = [
      'ID' => $utilisateur['ID'],
      'user_login' => $utilisateur['user_login'],
      'user_pass' => $utilisateur['user_pass'],
      'user_nicename' => $utilisateur['user_nicename'],
      'user_email' => $utilisateur['user_email'],
      'user_url' => $utilisateur['user_url'],
      'user_registered' => $utilisateur['user_registered'],
      'user_status' => $utilisateur['user_status'],
      'display_name' => $utilisateur['display_name'],
    ];

    $req = 'INSERT INTO ' . $this->wpTablePrefix . 'users '
    . '(`ID`, `user_login`, `user_pass`, `user_nicename`, `user_email`, '
    .'`user_url`, `user_registered`, `user_status`, `display_name`) '
    . 'VALUES(' . implode(', ', array_map(array($this->wpDbConnection, 'quote'), $utilisateur_values)) . ') '
    . 'ON DUPLICATE KEY UPDATE `ID`=VALUES(`ID`), `user_login`=VALUES(`user_login`), `user_pass`=VALUES(`user_pass`), `user_nicename`=VALUES(`user_nicename`), `user_email`=VALUES(`user_email`), `user_url`=VALUES(`user_url`), `user_registered`=VALUES(`user_registered`), `user_status`=VALUES(`user_status`), `display_name`=VALUES(`display_name`);'
    ;

    try {
      $this->wpDbConnection->exec($req);
      $this->userCount++;
    } catch(Exception $e) {
      echo "-- ECHEC " . __FUNCTION__ . " REQUÊTE: [$req]" . PHP_EOL;
      throw new MigrationException($e, $req, __FUNCTION__);
    }

  }// end method insertUserIntoWpUserTable($utilisateur)


  /**
   * Returns true if the user has chosen a pseudo. Else false.
   **/
  private function hasUserChosenPseudo($userId) {

    $usedPseudo = null;
    $usedPseudoQuery = 'SELECT amv_valeur AS pseudo_utilise FROM '.
      'annu_meta_valeurs WHERE amv_ce_colonne = 136 AND amv_cle_ligne = ' .
      $userId;

    try {
      $usedPseudo = $this->telaDbConnection->query($usedPseudoQuery)->fetch(PDO::FETCH_ASSOC);
    } catch(Exception $e) {
      echo "-- ECHEC " . __FUNCTION__ . " REQUÊTE: [$requete_pseudo_bp]" . PHP_EOL;
      throw new MigrationException($e, $requete_pseudo_bp, __FUNCTION__);
    }

    return $usedPseudo["pseudo_utilise"];
  }


  /**
   * Returns the user's pseudo.
   **/
  private function getUserPseudo($userId) {

    $resuls = null;
    $pseudoQuery = 'SELECT amv_valeur AS pseudo FROM annu_meta_valeurs'.
      ' WHERE amv_ce_colonne = 99 AND amv_cle_ligne = ' .
      $userId;

    try {
      $resuls = $this->telaDbConnection->query($pseudoQuery)->fetch(PDO::FETCH_ASSOC);
    } catch(Exception $e) {
      echo "-- ECHEC " . __FUNCTION__ . " REQUÊTE: [$pseudoQuery]" . PHP_EOL;
      throw new MigrationException($e, $pseudoQuery, __FUNCTION__);
    }

    return $resuls['pseudo'];
  }



  /**
   * Returns records for all users in Tela DB
   **/
  private function fetchTelaDbUsers() {
    $resuls = null;
    $requeteUtilisateurs = "SELECT `U_ID` AS `ID`,
    `U_MAIL` AS `user_login`,
    `U_PASSWD` AS `user_pass`,
    `U_MAIL` AS user_email,
    `U_WEB` AS user_url,
    `U_DATE` AS user_registered, '0' AS user_status,
    `U_SURNAME`,
    `U_NAME`,
    concat(`U_SURNAME`,' ',`U_NAME`) AS display_name
    FROM `annuaire_tela`";

    try {
      $resuls = $this->telaDbConnection->query($requeteUtilisateurs)->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e) {
      echo "-- ECHEC " . __FUNCTION__ . " REQUÊTE: [$pseudoQuery]" . PHP_EOL;
      throw new MigrationException($e, $pseudoQuery, __FUNCTION__);
    }

    return $resuls;

  }// end method fetchTelaDbUsers()

}// end class UserMigration
