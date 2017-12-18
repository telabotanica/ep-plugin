<?php

/**
* Permet de migrer tout un tas de trucs vers le nouveau site
*
* Genre les utilisateurs, les actus, les évènements, etc
*
* /!\ BEWARE OF THE CODE /!\ (Naughty code inside)
* Ce script a été écrit à la sueur de vieux pied
*
* *****************************************
* UTILISATION :
* *****************************************
*
* $ php migration.php <<commande>>
*
*  ou
*
* $ /usr/local/bin/php -d memory_limit=4000M cli.php migrationwp -a tous
*
* @WARNING Bien vérifier le config.php
*
*
* *****************************************
* TODOS
* *****************************************
*
* Liste de @TODO (censée être exhaustive)
* - méthode de nettoyage (voir repartirDeZero) pour rendre le script i(de)mpotent
* - gérer proprement les erreurs (là ça chie des exceptions dès que ça peut) en vue d'une automatisation
* - trouver un équivalent à un gros try-catch autour de chaque méthode permettant de restaurer les tables en cas de plantage
* - sauvegarder les tables proprement à chaque lancement ou ne pas lancer
* - méthode All permettant de tout lancer à la suite en vue d'une automatisation
* - vérifier les différentes meta ayant pu changer pendant le dev (évènements et leurs champs ACF, meta utilisateurs (mapping des IDs différent entre test et preprod))
* - vérifier les correspondances entre la réalité et les tables de correspondances (surtout les catégories avec les IDs et slugs pouvant différer d'une instance à l'autre)
* - voir les différents @todo du script
* - à compléter :) (Killian, avant ses vacances (et pas vous) le 21/07/2017)
*
*/



require_once "Migration/Autoloader.php";
use Migration\Autoloader;
Autoloader::register();


use \Migration\Users\UserMigrationGroup;
use \Migration\Rubrics\RubricMigration;
use \Migration\Rubrics\RubricDbResetter;
use \Migration\NewsEvents\NewsCommentMigration;
use \Migration\NewsEvents\NewsCoverMigration;
use \Migration\NewsEvents\NewsMigration;
use \Migration\NewsEvents\EventMigration;
use \Migration\NewsEvents\EventMetaMigration;
use \Migration\NewsEvents\NewsEventDbResetter;
use \Migration\Config;
use \Migration\DbUtilz;
use \Migration\MigrationGroup;

use \PDOException;


$actions = array(
  'users',
  'news-events',
  'rubrics'
);



// Si l'utilisateur à mélangé ses doigts on affiche les consignes
if ($argc < 2 || !in_array($argv[1], $actions)) {
  global $argv;
  global $actions;
  echo "Utilisation: \n\t$argv[0] action\n\n";
  echo "Actions: \n\t" . implode(PHP_EOL . "\t", $actions) . "\n";
  exit;
}

$action = $argv[1];

$connFactory = new Config($host, $port, $username, $pwd, $spipDb, $telaDb, $wpDb);

$bdSpip = $connFactory::connexionSpip();
$bdTelaProd =  $connFactory::connexionTelaProd();
$bdWordpress =  $connFactory::connexionWordpress();
$wordpressDir = $connFactory::getWpDir();
$wpTablePrefix = $connFactory::getWpTablePrefix();

$migration;

$userMigrationGroup = new UserMigrationGroup($wpTablePrefix, $bdSpip, $bdTelaProd, $bdWordpress);


$newsEventDbResetter = new NewsEventDbResetter($bdWordpress, $wpTablePrefix);
$newsEventMigrationGroup = new MigrationGroup([
  new NewsMigration(
   $wpTablePrefix, $bdSpip, $bdTelaProd, $bdWordpress),
  new NewsCommentMigration(
    $wpTablePrefix, $bdSpip, $bdTelaProd, $bdWordpress),
//  new NewsCoverMigration(
//    $wpTablePrefix, $bdSpip, $bdTelaProd, $bdWordpress, $wordpressDir),
  new EventMigration(
    $wpTablePrefix, $bdSpip, $bdTelaProd, $bdWordpress),
  new EventMetaMigration(
    $wpTablePrefix, $bdSpip, $bdTelaProd, $bdWordpress)
], $newsEventDbResetter);

$rubricDbResetter = new RubricDbResetter($bdWordpress, $wpTablePrefix);
$rubricEventMigrationGroup = new MigrationGroup([
  new RubricMigration(
    $wpTablePrefix, $bdSpip, $bdTelaProd, $bdWordpress)
], $rubricDbResetter);

switch($action) {
  case 'users':
  $migration = $userMigrationGroup;
  break;
  case 'news-events':
  $migration = $newsEventMigrationGroup;
  break;
  case 'rubrics':
  $migration = $rubricEventMigrationGroup;
  break;
  default:
  echo "commande inconnue, l'action ne correspond pas à une fonction";
  break;
}


$bdWordpress->beginTransaction();
$migration->migrate();

try {
  $bdWordpress->commit();
} catch(MigrationException $ex) {
  echo '!!!!!!!!!!!!!!!!!!!!!FAIL!!!!!!!!!!!!!!!!!!!' . PHP_EOL;
  echo 'MESSAGE -> ' . $ex->getMessage() . PHP_EOL;
  echo 'CODE    -> ',  $ex->getCode()    . PHP_EOL;
  echo "QUERY   -> " . $ex->getQuery()   . PHP_EOL;
  echo "FUNC.   -> " . $ex->getFunc()    . PHP_EOL;
  die(var_dump($ex->errorInfo));
  echo '!!!!!!!!!!!!!!!!!!!!!FAIL!!!!!!!!!!!!!!!!!!!' . PHP_EOL;
} catch(PDOException $ex) {
  echo '!!!!!!!!!!!!!!!!!!!!!FAIL!!!!!!!!!!!!!!!!!!! -> ',  $ex->getMessage(), "\n";
  die(var_dump($ex->errorInfo));
} catch(Exception $ex) {
  echo '!!!!!!!!!!!!!!!!!!!!!FAIL!!!!!!!!!!!!!!!!!!! -> ',  $ex->getMessage(), "\n";
  die(var_dump($ex->errorInfo));
}
