<?php

/**
* Migrates users/news/events/rubrics related data from TB
* "annuaire"/"bazar-fiches" and SPIP DB to the new WP site DB.
*
* *****************************************
* USE:
* *****************************************
*
* $ php tb_migrate_site.php <<commande>>
*
*
* @WARNING Bien vérifier le config.php
*
*
* *****************************************
* TODOS
* *****************************************
*
* Liste de @TODO (censée être exhaustive)
* - vérifier les différentes meta ayant pu changer pendant le dev (évènements et leurs champs ACF, meta utilisateurs (mapping des IDs différent entre test et preprod))
* - vérifier les correspondances entre la réalité et les tables de correspondances (surtout les catégories avec les IDs et slugs pouvant différer d'une instance à l'autre)
* - voir les différents @todo du script
*
*/
require_once "Migration/Autoloader.php";
use Migration\Autoloader;
Autoloader::register();

use \Migration\Api\ConfManager;
use \Migration\Api\DatasourceManager;
use \Migration\Api\MigrationFactory;
use \Migration\App\Config\ConfEntryValuesEnum;
use \Migration\App\Config\DbNamesEnum;

$context = $argv[1];

$ctxMigrationClassMap = array(
  'users'        => '\Migration\App\Users\UserMigrationGroup',
  'news-events'  => '\Migration\App\NewsEvents\NewsEventMigrationGroup',
  'rubrics'      => '\Migration\App\Rubrics\RubricMigrationGroup',
  'all'          => '\Migration\App\AllMigrationGroup'
);

$migrationFactory = new MigrationFactory($ctxMigrationClassMap);
$contexts = $migrationFactory->getContexts();
// Si l'utilisateur à mélangé ses doigts on affiche les consignes
if ($argc < 2 || !in_array($argv[1], $contexts)) {
  echo "Please, use: \n\tphp $argv[0] <context>\n\n";
  echo "With <context> being one of: \n\n\t"
    . implode(PHP_EOL . "\t", $contexts) . "\n";
  exit;
}

$confMgr     = ConfManager::getInstance();
$dsManager   = DatasourceManager::getInstance();
$bdWordpress = $dsManager->getConnection(DbNamesEnum::Wp);
$migration   = $migrationFactory->getMigration($context);

try {
  $bdWordpress->beginTransaction();
  $migration->migrate();
  $bdWordpress->commit();
} catch(MigrationException $ex) {
  echo '!!!!!!!!!!!!!!!!!!!!!FAIL!!!!!!!!!!!!!!!!!!!' . PHP_EOL;
  echo 'MESSAGE -> ' . $ex->getMessage() . PHP_EOL;
  echo 'CODE    -> ',  $ex->getCode()    . PHP_EOL;
  echo "QUERY   -> " . $ex->getQuery()   . PHP_EOL;
  echo "FUNC.   -> " . $ex->getFunc()    . PHP_EOL;
  echo '!!!!!!!!!!!!!!!!!!!!!FAIL!!!!!!!!!!!!!!!!!!!' . PHP_EOL;
} catch(Exception $ex) {
  echo '!!!!!!!!!!!!!!!!!!!!!FAIL!!!!!!!!!!!!!!!!!!! -> ',  $ex->getMessage(), "\n";
} finally {
  // useless as the script is ending but kinda cleaner...
  $dsManager->closeAll();
}
