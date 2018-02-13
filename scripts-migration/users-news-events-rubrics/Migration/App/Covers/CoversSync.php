<?php

namespace Migration\App\Covers;

use Migration\Api\BaseMigration;
use Migration\Api\ConfManager;
use Migration\App\Config\ConfEntryValuesEnum;

use \Exception;
use \PDO;

/**
 * Migrates covers
 */
class CoversSync extends BaseMigration {

  /**
   * Migrates covers
   */
  public function migrate() {
    $confMgr = ConfManager::getInstance();
    $wordpress_dir = $confMgr->getConfEntryValue(ConfEntryValuesEnum::WpDir);
    $publicKey = $confMgr->getConfEntryValue(ConfEntryValuesEnum::PublicKeyPath);
    $rsyncSource = $confMgr->getConfEntryValue(ConfEntryValuesEnum::RsyncSource);
    $rsyncDest = $confMgr->getConfEntryValue(ConfEntryValuesEnum::RsyncDestination);

    $ssh = '"' . "ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -i $publicKey" . '"';
    $rsync = "rsync -e $ssh -avz $rsyncSource $rsyncDest --stats";

    // Faut être au bon endroit, le repertoire de wordpress
    // Et avoir rsync et ssh installés
    // Un peu plus loin on va rsync les images dans un dossier spécifique
    chdir($wordpress_dir);

    echo '-- exécution du "rsync"' . PHP_EOL;
    exec($rsync, $output, $exit_code);

    if (0 !== $exit_code) {
      var_dump($rsync);
      var_dump($output);
      throw new Exception('Faut férifier la commande, ça a foiré là.');
    } else {
      // // Verbose
      // var_dump($output);
    }
  }

}
