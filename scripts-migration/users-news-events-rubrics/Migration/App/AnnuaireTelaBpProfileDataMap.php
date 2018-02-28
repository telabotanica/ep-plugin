<?php

namespace Migration\App;

use Migration\Api\DatasourceManager;
use Migration\App\Config\DbNamesEnum;

use \PDO;

/**
* Maps between the source DB values and corresponding target DB values.
 */
class AnnuaireTelaBpProfileDataMap {

  private static $correspondances_niveau_bota = [
    '30786' => 'Débutant',
    '30787' => 'Ayant une bonne pratique',
    '30788' => 'Confirmé',
    '30790' => 'Ne se prononce pas'
  ];

  private static $correspondances_membre_asso_naturaliste = [
    '30833' => 'Oui',
    '30834' => 'Non',
    '30811' => 'Ne se prononce pas',
    '30812' => 'Oui',
    '30813' => 'Non'
  ];

  // a:8:{i:0;s:14:"Zones polaires";i:1;s:17:"Zones tempérées";i:2;s:16:"Zones tropicales";i:3;s:24:"Zones méditerranéennes";i:4;s:6:"Plaine";i:5;s:13:"Basses terres";i:6;s:13:"Hautes terres";i:7;s:8:"Montagne";}
  private static $correspondances_zones_geo = [
    '30805' => 'Zones tempérées', // Zones géographiques : tempérées et boréales
    '30806' => 'Zones méditerranéennes', // Zone géographique : Méditerranéenne
    '30807' => 'Zones tropicales' // Zones géographiques : subtropicales à tropicales
  ];


  // Export des meta de buddypress :
  // INSERT INTO `bp_xprofile_fields` (`id`, `group_id`, `parent_id`, `type`, `name`, `description`, `is_required`, `is_default_option`, `field_order`, `option_order`, `order_by`, `can_delete`)
  // (1, 1, 0, 'textbox', 'Pseudo', '', 1, 0, 0, 0, '', 0),
  // (2, 3, 0, 'datebox', 'Date de naissance', '', 0, 0, 1, 0, '', 1),
  // (3, 1, 0, 'selectbox', 'Pays', '', 1, 0, 4, 0, 'custom', 1),
  // (4, 1, 0, 'textbox', 'Ville', '', 1, 0, 6, 0, '', 1),
  // (9, 1, 0, 'textbox', 'Nom', '', 1, 0, 3, 0, '', 1),
  // (10, 1, 0, 'textbox', 'Prénom', '', 0, 0, 2, 0, '', 1),
  // (12, 2, 0, 'selectbox', 'Expérience botanique', '', 1, 0, 0, 0, 'custom', 1),
  // (63, 2, 0, 'textbox', 'Espèce d''intérêt', 'Pour préciser votre famille, genre voire espèce de spécialisation ou noter votre plante préférée', 0, 0, 2, 0, 'custom', 1),
  // (61, 2, 0, 'checkbox', 'Zones géographiques d''intérêt', 'Pour préciser vos zones phytogéographiques et altitudes de prédilection.', 0, 0, 3, 0, 'custom', 1),
  // (26, 3, 0, 'selectbox', 'Métier', '', 0, 0, 3, 0, 'custom', 1),
  // (46, 1, 0, 'selectbox', 'Compte', 'Merci de préciser si votre compte est un compte personnel ou professionnel (utilisé par une personne) ou de structure (partagé par plusieurs personnes)', 1, 0, 1, 0, 'custom', 1),
  // (69, 2, 0, 'selectbox', 'Membre d''une association naturaliste', '', 0, 0, 1, 0, 'custom', 1),
  // (51, 1, 0, 'textbox', 'Adresse', 'Votre adresse ne sera pas communiquée mais nous est utile pour éditer les reçus fiscaux si vous nous faites un don.', 0, 0, 8, 0, '', 1),
  // (52, 3, 0, 'url', 'Site web', 'Si vous avez un site web personnel', 0, 0, 2, 0, '', 1),
  // (53, 3, 0, 'textarea', 'Présentation', '', 0, 0, 0, 0, '', 1),
  // (54, 1, 0, 'checkbox', 'Inscription à la lettre d''actualité', '', 0, 0, 9, 0, 'custom', 1),
  // (59, 1, 0, 'checkbox', 'Conditions d''utilisation', 'Lire les  <a href="https://www.tela-botanica.org/mentions-legales/#conditions-d-utilisation"> conditions d''utilisation du site</a>', 1, 0, 10, 0, 'custom', 1),
  // (592, 1, 0, 'selectbox', 'Département', '', 0, 0, 5, 0, 'custom', 1),

  // Correspondances entre les anciennes meta et celles de BP :
  // nom du champ             ; id actuel ; id BP       ; valeurs
  // prenom                   ; 7         ; 10
  // experience bota          ; 4         ; 12      (également présent dans la table annuaire_tela.U_NIV mais n'est jamais mis à jour)
  // departement (ou cp)      ; 13        ; 592         ; pas trouvé d'exemple (faut utiliser annuaire_tela.U_ZIP_CODE)
  // conditions d'utilisation ; 15        ; 59 sur test (1035 sur preprod)          ; 1
  // lettre d'actu            ; 14        ; 54          ; pas trouvé d'exemple
  // presentation             ; 125       ; 53          ; juste du texte
  // site web                 ; 134       ; 52      (n'est pas rempli, c'est la valeur de annuaire_tela.U_WEB qui est utilisée en vrai)
  // adresse                  ; 132       ; 51          ; pas trouvé d'exemple
  // membre asso natur        ; 133       ; 69          ; [30833, 30834, 30811, 30812, 30813]
  // compte                   ; pas d'équivalent ; 46
  // metier                   ; pas d'équivalent ; 26
  // zone geo d'interet       ; 120       ; 61          ; [30806] ex: 30805;;30806;;30807
  // espece d'interet         ; pas forcément ça 8 ; 63 ; [30829]

  // l'index c'est l'identifiant actuel (amv_ce_colonne), et la valeur c'est celle coté bp

  // l'index c'est l'identifiant actuel (amv_ce_colonne), et la valeur c'est celle coté bp
  private static $correspondance_categories = [
    '99'  => '1', // pseudo
    '2'   => '137', // langues
    '13'  => '592', // code postal
    '137' => '2', // date naissance
    '12'  => '3', // pays
    '103' => '4', // ville
    '1'   => '9', // nom
    '7'   => '10', // prenom
    '4'   => '12', // niveau bota
    '8'   => '63', // espece d'interet
    '120' => '61', // zones géo
    '133' => '69', // membre asso natur
    '132' => '51', // adresse
    '134' => '52', // site web
    '125' => '53', // presentation
    '14'  => '54', // lettre d'actu
    '15'  => '59', // conditions d'utilisation
  ];

  // Détails des différentes rubriques à migrer
  // on associe le titre et le slug d'une catégorie wordpress avec des rubriques spip
  // les articles des rubriques seront migrés vers la catégorie donnée
  private static $correspondanceCategorieRubriques = array(
    // Actualités
    array('titre' => 'Brèves', 'slug' => 'breves', 'rubrique-a-migrer' => [22, 31, 35]),
    array('titre' => 'Contribuez', 'slug' => 'contribuez', 'rubrique-a-migrer' => [70, 71]),
    array('titre' => 'En kiosque', 'slug' => 'en-kiosque', 'rubrique-a-migrer' => [30, 34]),
    array('titre' => 'Nouvelles du réseau', 'slug' => 'nouvelles-du-reseau', 'rubrique-a-migrer' => [54, 55]),
    array('titre' => 'Points de vue', 'slug' => 'points-de-vue', 'rubrique-a-migrer' => [38, 39]),
    // Évènements
    // array('titre' => 'Congrès et conférences', 'slug' => 'congres-conferences'),
    // array('titre' => 'Expositions', 'slug' => 'expositions'),
    // array('titre' => 'Sorties de terrain', 'slug' => 'sorties-de-terrain'),
    // array('titre' => 'Stages et ateliers', 'slug' => 'stages-ateliers'),
    // Offres d'emploi
    array('titre' => 'CDD / CDI', 'slug' => 'cdd-cdi', 'rubrique-a-migrer' => [19]),
    array('titre' => 'Stages', 'slug' => 'stages', 'rubrique-a-migrer' => [51])
    // Autres
    // array('titre' => 'Revue de presse', 'slug' => 'revue-de-presse', 'rubrique-a-migrer' => 69) // Poubelle
  );

  private static $codes_langues = [
    '30842'=>'Anglais',
    '30843'=>'Allemand',
    '30844'=>'Italien',
    '30845'=>'Espagnol',
    '30846'=>'Arabe',
    '30847'=>'Chinois',
    '30848'=>'Russe'
  ];

  private static $departements = [
    '01' => '01 Ain',
    '02' => '02 Aisne',
    '03' => '03 Allier',
    '04' => '04 Alpes-de-Haute-Provence',
    '05' => '05 Hautes-Alpes',
    '06' => '06 Alpes-Maritimes',
    '07' => '07 Ardèche',
    '08' => '08 Ardennes',
    '09' => '09 Arièges',
    '10' => '10 Aube',
    '11' => '11 Aude',
    '12' => '12 Aveyron',
    '13' => '13 Bouches-du-Rhône',
    '14' => '14 Calvados',
    '15' => '15 Cantal',
    '16' => '16 Charente',
    '17' => '17 Charente-Maritime',
    '18' => '18 Cher',
    '19' => '19 Corrèze',
    '201' => '2A Corse-du-Sud',
    '202' => '2B Haute-Corse',
    '21' => '21 Côte d\'Or',
    '22' => '22 Côtes-d\'Armor',
    '23' => '23 Creuse',
    '24' => '24 Dordogne',
    '25' => '25 Doubs',
    '26' => '26 Drôme',
    '27' => '27 Eure',
    '28' => '28 Eure-et-Loir',
    '29' => '29 Finistère',
    '30' => '30 Gard',
    '31' => '31 Haute-Garonne',
    '32' => '32 Gers',
    '33' => '33 Gironde',
    '34' => '34 Hérault',
    '35' => '35 Ille-et-Vilaine',
    '36' => '36 Indre',
    '37' => '37 Indre-et-Loire',
    '38' => '38 Isère',
    '39' => '39 Jura',
    '40' => '40 Landes',
    '41' => '41 Loir-et-Cher',
    '42' => '42 Loire',
    '43' => '43 Haute-Loire',
    '44' => '44 Loire-Atlantique',
    '45' => '45 Loiret',
    '46' => '46 Lot',
    '47' => '47 Lot-et-Garonne',
    '48' => '48 Lozère',
    '49' => '49 Maine-et-Loire',
    '50' => '50 Manche',
    '51' => '51 Marne',
    '52' => '52 Haute-Marne',
    '53' => '53 Mayenne',
    '54' => '54 Meurthe-et-Moselle',
    '55' => '55 Meuse',
    '56' => '56 Morbihan',
    '57' => '57 Moselle',
    '58' => '58 Nièvre',
    '59' => '59 Nord',
    '60' => '60 Oise',
    '61' => '61 Orne',
    '62' => '62 Pas-de-Calais',
    '63' => '63 Puy-de-Dôme',
    '64' => '64 Pyrénées-Atlantiques',
    '65' => '65 Hautes-Pyrénées',
    '66' => '66 Pyrénées-Orientales',
    '67' => '67 Bas-Rhin',
    '68' => '68 Haut-Rhin',
    '69' => '69 Rhône',
    '70' => '70 Haute-Saône',
    '71' => '71 Saône-et-Loire',
    '72' => '72 Sarthe',
    '73' => '73 Savoie',
    '74' => '74 Haute-Savoie',
    '75' => '75 Paris',
    '76' => '76 Seine-Maritime',
    '77' => '77 Seine-et-Marne',
    '78' => '78 Yvelines',
    '79' => '79 Deux-Sèvres',
    '80' => '80 Somme',
    '81' => '81 Tarn',
    '82' => '82 Tarn-et-Garonne',
    '83' => '83 Var',
    '84' => '84 Vaucluse',
    '85' => '85 Vendée',
    '86' => '86 Vienne',
    '87' => '87 Haute-Vienne',
    '88' => '88 Vosges',
    '89' => '89 Yonne',
    '90' => '90 Territoire de Belfort',
    '91' => '91 Essonne',
    '92' => '92 Hauts-de-Seine',
    '93' => '93 Seine-Saint-Denis',
    '94' => '94 Val-de-Marne',
    '95' => '95 Val-d\'Oise',
    '971' => '971 Guadeloupe',
    '972' => '972 Martinique',
    '973' => '973 Guyane',
    '974' => '974 La Réunion',
    '975' => '975 Saint-Pierre-et-Miquelon',
    '976' => '976 Mayotte',
  ];



  public static function getBotanicLevel($annuaireTelaValue) {
    return self::$correspondances_niveau_bota[$annuaireTelaValue];
  }

  public static function getNaturalistAssociationMembership($annuaireTelaValue) {
    return self::$correspondances_membre_asso_naturaliste[$annuaireTelaValue];
  }

  public static function getGeoZone($annuaireTelaValue) {
    return self::$correspondances_zones_geo[$annuaireTelaValue];
  }

  public static function getCategory($annuaireTelaValue) {
    return self::$correspondance_categories[$annuaireTelaValue];
  }

  public static function getRubriqueCategory($annuaireTelaValue) {
    return self::$correspondanceCategorieRubriques[$annuaireTelaValue];
  }

  public static function getRubriqueCategoryArray() {
    return self::$correspondanceCategorieRubriques;
  }

  public static function getDepartment($annuaireTelaValue) {
    return isset(self::$departements[$annuaireTelaValue]) ?? '';
  }

  public static function getLanguage($annuaireTelaValue) {
    return self::$codes_langues[$annuaireTelaValue];
  }

  public static function getSpipRubricsToBeMigrated() {

    $rubriquesAMigrer = [];
    foreach (self::$correspondanceCategorieRubriques as $correspondance) {
      $rubriquesAMigrer[] = implode(',', $correspondance['rubrique-a-migrer']);
    }

    return implode(',', $rubriquesAMigrer);
  }

  public static function getWordpressCategoriesId() {
    $slugsCategories = [];
    foreach (self::getRubriqueCategoryArray() as $categorie) {
      $slugsCategories[] = $categorie['slug'];
    }

    $requeteIdCategories = 'SELECT term_id FROM ' . DatasourceManager::getInstance()->getTablePrefix(DbNamesEnum::Wp) . 'terms WHERE slug IN ("' . implode('", "', $slugsCategories) . '");';

    $idCategories = DatasourceManager::getInstance()
      ->getConnection(DbNamesEnum::Wp)
      ->query($requeteIdCategories)
      ->fetchAll(PDO::FETCH_COLUMN, 0)
    ;

    return implode("','", $idCategories);
  }

}
