<?php

namespace Migration\App\Covers;

use Migration\Api\BaseMigration;
use Migration\App\AnnuaireTelaBpProfileDataMap;
use Migration\Api\ConfManager;
use Migration\App\Config\ConfEntryValuesEnum;

use \Exception;
use \PDO;

/**
 * Migrates covers
 */
class CoversMigration extends BaseMigration {

  private $wordpress_dir;

  public function __construct() {
    parent::__construct();
    $confMgr = ConfManager::getInstance();
    $this->wordpress_dir = $confMgr->getConfEntryValue(ConfEntryValuesEnum::WpDir);
  }

  /**
   * Migrates covers
   */
  public function migrate() {
    // On vérifie qu'on peut bosser
    // Faut être au bon endroit, le repertoire de wordpress
    // Et avoir wp-cli installé
    // Un peu plus loin on va charger les images depuis un dossier spécifique
    $old_path = getcwd();
    chdir($this->wordpress_dir);

    echo '-- exécution de "wp --info"' . PHP_EOL;
    exec('wp --info', $output, $exit_code);
    if (0 !== $exit_code) {
      throw new Exception('Faut lancer la commande depuis le repertoire de wordpress (et avoir wp-cli installé)');
    } else {
      // // Verbose
      // var_dump($output);
    }

    echo '-- recherche du dossier d\'images' . PHP_EOL;
    if (!file_exists($this->wordpress_dir . '/IMG')) {
      throw new Exception('Faut rsync les images dans wordpress/IMG avant de commencer');
    }

    // chdir($old_path); // commenté car inutile de rechanger de répertoire avant la fin du script


    echo '-- import des images (attention c\'est long)' . PHP_EOL;
    $requete = sprintf('SELECT spip_articles.`id_article` AS ID, `date`, titre FROM `spip_articles` WHERE id_rubrique in ( %s ) ORDER BY id_article', AnnuaireTelaBpProfileDataMap::getSpipRubricsToBeMigrated());
    $articles = $this->spipDbConnection->query($requete)->fetchAll(PDO::FETCH_ASSOC);

    $i = 0;
    $compteurSucces = 0;
    $compteurEchecs = 0;
    $compteurAbusay = 0;
    $compteurBoucle = 0;
    $length = count($articles);
    echo '-- nombre d\'images à importer : '. $length . PHP_EOL;
    foreach ($articles as $article) {
      $compteurBoucle++;
      if (0 === $compteurBoucle % 1000) {
        echo 'progression : ' . $compteurBoucle . '/' . $length . PHP_EOL;
      }

      $wpcli_meta_thumbnail = 'wp post meta get ' . $article['ID'] . ' _thumbnail_id --skip-plugins --skip-themes --skip-packages';

      unset($wpcli_meta_command_output); // sinon c'est les output sont cacaténées (c'est concon)
      exec($wpcli_meta_thumbnail, $wpcli_meta_command_output, $exit_code);
      // quand on demande directement la valeur d'une meta la sortie bof :
      // la meta existe, error code 0 et en output on voit l'id de la thumbnail
      // le post demandé n'existe pas, error code 1
      // la meta n'existe pas, error code 1 aussi (les erreurs n'arrivent malheureusement pas dans la variable output)
      if (0 === $exit_code) {
        // // Verbose
        // echo 'han!! le post ' . $article['ID'] . ' bah il a déjà une image, trop abusééééééé' . PHP_EOL;
        // echo 'id du post de l\'image ' . $meta['meta_value'] . PHP_EOL;
        $compteurAbusay++;

        $id_image = $wpcli_meta_command_output[0];
        $this->ajouter_categorie($id_image);

        continue; // y'a déjà une couverture, on passe au prochain article

      } else {
        // deux possibilités donc, soit le post n'existe pas (erreur), soit on peut continuer
        $wpcli_meta_post = 'wp post meta list ' . $article['ID'] . ' --skip-plugins --skip-themes --skip-packages --format=count';
        exec($wpcli_meta_post, $wpcli_meta_command_output, $exit_code);

        if (0 !== $exit_code) {
          echo 'le post ' . $article['ID'] . ' n\'existe pas' . PHP_EOL;
          echo 'Erreur à l\'exécution de la commande (17) Faut resynchroniser les actus, merci' . PHP_EOL;

          $compteurEchecs++;

          continue;
        }
      }

      // on va pas aller télécharger les images sur le site, on les mets dans un
      // dossier exprès grâce à la classe CoversSync
      // du coup on peut aller chercher l'image de couverture dans le dossier
      $images = glob('IMG/arton' . $article['ID'] . '.*');
      if (empty($images)) {
        //echo 'Image manquante : ' . $article['ID'] . PHP_EOL;
        $compteurEchecs++;

        continue;
      }

      $imageChemin = $images[0]; // normalement y'a qu'une image correspondant au filtre
      if (!file_exists($imageChemin)) {
        //echo 'Image manquante : ' . $article['ID'] . PHP_EOL;
        $compteurEchecs++;

        continue;
      }

      // $imageNom = strtolower(pathinfo($imageChemin, PATHINFO_FILENAME));

      $wpcli_commande = 'wp media import ' . $imageChemin . ' --featured_image'
        . ' --post_id=' . $article['ID']
        . ' --title="image de couverture de l\'article ' . $article['ID'] . '"'
        . ' --alt="image de couverture"'
        . ' --desc="image de couverture de l\'article ' . $article['ID'] . '"' // post_content field
        . ' --caption="image de couverture de l\'article ' . $article['ID'] . '"' // post_except field
        . ' --skip-plugins --skip-packages' // post_except field
      ;

      unset($command_output);
      exec($wpcli_commande, $command_output, $exit_code);

      if (0 === $exit_code) {
        // // Verbose
        // foreach ($command_output as $message) {
        //   echo $message;
        // }
        // echo PHP_EOL;

        $compteurSucces++;

        // on ajoute une catégorie à l'image importée
        preg_match('@(\d+).$@', $command_output[0], $matches);
        $id_image = $matches[1];

        $this->ajouter_categorie($id_image);

      } else {
        echo 'commande en échec : "' . $wpcli_commande . '"' . PHP_EOL;
        echo PHP_EOL;
        var_dump($command_output);

        die('Erreur à l\'exécution de la commande (42)');
      }

    }

    echo '-- ' . $compteurSucces . ' images de couverture actualités migrées. ' . PHP_EOL;
    echo '-- ' . $compteurEchecs . ' erreurs rencontrées pendant la migration (mais rien de grave). ' . PHP_EOL;
    echo '-- ' . $compteurAbusay . ' images déjà migrées ' . PHP_EOL;
    echo '-- ' . $length . ' articles au total ' . PHP_EOL;
  }

  public function ajouter_categorie($id_image) {
    $wpcli_image_categorie = 'wp post term add ' . $id_image
      . ' media_category actu-import --by=slug'
      . ' --skip-themes --skip-packages'
    ;

    exec($wpcli_image_categorie, $output_image_categorie_command, $exit_code);

    if (0 !== $exit_code) {
      echo 'erreur lors de l\'ajout de categorie à l\'image importée' . PHP_EOL;
    }
  }

}
