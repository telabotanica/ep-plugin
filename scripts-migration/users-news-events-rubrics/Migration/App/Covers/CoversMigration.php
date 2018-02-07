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


    $requete = sprintf('SELECT spip_articles.`id_article` AS ID, `date`, titre FROM `spip_articles` WHERE id_rubrique in ( %s ) ORDER BY id_article', AnnuaireTelaBpProfileDataMap::getSpipRubricsToBeMigrated());
    $articles = $this->spipDbConnection->query($requete)->fetchAll(PDO::FETCH_ASSOC);

    $i = 0;
    $compteurSucces = 0;
    $compteurEchecs = 0;
    $compteurAbusay = 0;
    $length = count($articles);
    foreach ($articles as $article) {


      $wpcli_meta = 'wp post meta list ' . $article['ID'] . ' --format=json';

      exec($wpcli_meta, $wpcli_meta_command_output, $exit_code);

      // die(var_dump($wpcli_meta_command_output));

      // on va commencer par vérifier si l'article n'a pas déjà une image de couv
      // [comme la sortie de "wp post meta get ID" est merdique (toujours à 1 et vide) on va ruser]
      // cas 1 : le post n'existe pas ; code de sortie  1, tableau vide
      // cas 2 : listes des metas à gérer
      if (0 === $exit_code) {
        $metas = json_decode($wpcli_meta_command_output[0], true);
        unset($wpcli_meta_command_output);

        // on va fouiller les metas, si on trouve une image de couverture (_thumbnail_id), on passe à la suite
        foreach ($metas as $meta) {
          if ($meta['meta_key'] === '_thumbnail_id') {
            // // Verbose
            // echo 'han!! le post ' . $article['ID'] . ' bah il a déjà une image, trop abusééééééé' . PHP_EOL;
            // echo 'id du post de l\'image ' . $meta['meta_value'] . PHP_EOL;
            $compteurAbusay++;

            continue 2; // y'a déjà une couverture, on passe au prochain article
          }
        }
      } else {
        // cas d'erreur, genre le post existe pas, on arrète tout (ou pas)
        echo $wpcli_meta . PHP_EOL;
        var_dump($wpcli_meta_command_output);
        var_dump($exit_code);
        $compteurEchecs++;

        echo('Erreur à l\'exécution de la commande (17) Faut resynchroniser les actus, merci' . PHP_EOL);
      }

      // on va pas aller télécharger les images sur le site, on les mets
      // dans un dossier exprès
      // genre :
      //    rsync -avz telabotap@sequoia:/home/telabotap/www/actu/IMG/arton* wp-content/uploads/2017/03/
      //
      // on recherche l'image de couverture dans le dossier
      $images = glob('IMG/arton' . $article['ID'] . '.*');
      if (!empty($images)) {
        $imageChemin = $images[0]; // normalement y'a qu'une image correspondant au filtre

        if (file_exists($imageChemin)) {
          $imageNom = strtolower(pathinfo($imageChemin, PATHINFO_FILENAME));

          $wpcli_commande = 'wp media import ' . $imageChemin . ' --featured_image'
          . ' --post_id=' . $article['ID']
          . ' --title="image de couverture de l\'article ' . $article['ID'] . '"'
          . ' --alt="image de couverture"'
          . ' --desc="image de couverture de l\'article ' . $article['ID'] . '"' // post_content field
          . ' --caption="image de couverture de l\'article ' . $article['ID'] . '"' // post_except field
          ;

          exec($wpcli_commande, $command_output, $exit_code);

          if (0 === $exit_code) {
            // // Verbose
            // foreach ($command_output as $message) {
            //   echo $message;
            // }
            // echo PHP_EOL;

            $compteurSucces++;

            unset($command_output);
          } else {
            echo 'commande en échec : "' . $wpcli_commande . '"' . PHP_EOL;
            echo PHP_EOL;
            var_dump($command_output);

            die('Erreur à l\'exécution de la commande (42)');
          }
        } else {
          echo 'Image manquante : ' . $article['ID'];
        }
      }
      // foreach ($imagesPaths as $path) {
      //   if (file_exists($path) && is_file($path)) {
      //     $imageChemin = $path;
      //     break;
      //   }
      // }


      // Si la méthode avec wp-cli fonctionne pas voir plus bas
      // En mode sql dessous :


      // vérifier si artonID.ext
      // comment gérer les ids supplémentaires ? Y'avait une marge de 10k pour les articles, pareil pour les évènement, c'est bien ça ?
      //
      //
      // Description de la table
      //
      // post_author sera admin "1"
      // post_content sera une description, genre : image de couverture / titre de l'article
      // post_title c'est le nom de l'image en minuscules sans l'extension
      // post_excerpt c'est la légende
      // post_status "inherit" avec post_parent à "0"
      // comment_status "open"
      // ping_status "closed"
      // post_password vide
      // post_name c'est comme post_title
      // to_ping / pinged vides
      // post_content_filtered vide
      // post_parent c koi ?
      // guid c koi ? dans mon exemple c'est l'url absolue de l'image
      // menu_order à 0
      // post_type attachment
      // post_mime_type image/[gif,png,jpeg]
      // comment_count à 0
      //
      //
      // une image dans la galerie ça consiste en :
      //  - un post de type attachment comme vu au dessus
      //  - des meta, à savoir (exemples) :
      //    - _wp_attached_file 2017/03/acab-11219026_10208737059251108_4677167688813950172_n.jpg
      //    - _wp_attachment_metadata a:4:{s:5:"width";i:800;s:6:"height";i:815;s:4:"file";s:65:"2017/03/acab-11219026_10208737059251108_4677167688813950172_n.jpg";s:10:"image_meta";a:12:{s:8:"aperture";s:1:"0";s:6:"credit";s:0:"";s:6:"camera";s:0:"";s:7:"caption";s:0:"";s:17:"created_timestamp";s:1:"0";s:9:"copyright";s:0:"";s:12:"focal_length";s:1:"0";s:3:"iso";s:1:"0";s:13:"shutter_speed";s:1:"0";s:5:"title";s:0:"";s:11:"orientation";s:1:"0";s:8:"keywords";a:0:{}}}
      //    - Dans cette dernière meta les seules choses importantes (sauf erreur) c'est les dimensions et l'emplacement du fichier, donc image_meta = osef
      //
      //    getimagesize() avec GD pour les tailles, ou mieux suivant ce qu'on a dans le serveur
      //
      // et une image de la galerie attachée à un article en tant qu'image à la une ça consite en :
      //  une ligne de meta sur le post avec _thumbnail_id qui pointe sur l'ID du post de l'image ex: _thumbnail_id 7870

      // $image = array(
      //   'post_author' => 1,
      //   'post_date' => $article['date'],
      //   'post_date_gmt' => $article['date'],
      //   'post_content' => 'Image à la une de l\'article ' . $article['titre'],
      //   'post_title' => $imagesNom,
      //   'post_excerpt' => 'Image à la une de l\'article ' . $article['titre'],
      //   'post_status' => 'inherit',
      //   'comment_status' => 'open',
      //   'ping_status' => 'closed',
      //   'post_password' => '',
      //   'post_name' => $imagesNom,
      //   'to_ping' => '',
      //   'pinged' => '',
      //   'post_modified' => $article['date'],
      //   'post_modified_gmt' => $article['date'],
      //   'post_content_filtered' => '',
      //   'post_parent' => 0,
      //   'guid' => 'https://www.tela-botanica.org/wp-content/uploads/2017/03/' . basename($imageChemin),
      //   'menu_order' => 0,
      //   'post_type' => 'attachment',
      //   'post_mime_type' => mime_content_type($imageChemin),
      //   'comment_count' => 0
      // );

      // $i++;


      // // gestion des dates normales et dates en GMT
      // $date = new DateTime($image['post_date'], new DateTimeZone('Europe/Paris'));
      // $image['post_date_gmt'] = $date->setTimezone(new DateTimeZone('GMT'))->format('Y-m-d H:i:s');
      // $date = new DateTime($image['post_modified'], new DateTimeZone('Europe/Paris'));
      // $image['post_modified_gmt'] = $date->setTimezone(new DateTimeZone('GMT'))->format('Y-m-d H:i:s');

      // $requeteInsertAttachment = 'INSERT INTO ' . $wpTablePrefix . 'posts (`post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES ' . implode(', ', array_map(array($wpDbConnection, 'quote'), $image)) . '
      //   ON DUPLICATE KEY UPDATE `ID`=VALUES(`ID`), `post_author`=VALUES(`post_author`), `post_date`=VALUES(`post_date`), `post_date_gmt`=VALUES(`post_date_gmt`), `post_content`=VALUES(`post_content`), `post_title`=VALUES(`post_title`), `post_excerpt`=VALUES(`post_excerpt`), `post_status`=VALUES(`post_status`), `comment_status`=VALUES(`comment_status`), `ping_status`=VALUES(`ping_status`), `post_password`=VALUES(`post_password`), `post_name`=VALUES(`post_name`), `to_ping`=VALUES(`to_ping`), `pinged`=VALUES(`pinged`), `post_modified`=VALUES(`post_modified`), `post_modified_gmt`=VALUES(`post_modified_gmt`), `post_content_filtered`=VALUES(`post_content_filtered`), `post_parent`=VALUES(`post_parent`), `guid`=VALUES(`guid`), `menu_order`=VALUES(`menu_order`), `post_type`=VALUES(`post_type`), `post_mime_type`=VALUES(`post_mime_type`), `comment_count`=VALUES(`comment_count`);'
      // ;

      // $attachmentMetas = array(
      //   'post_id' => article['ID'],
      //   'meta_key' => '_thumbnail_id',
      //   'meta_value' => $artucle['ID'] + 10000;
      // );

      // $requeteInsertPostmeta = 'INSERT INTO ' . $wpTablePrefix . 'postmeta (`post_id`, `meta_key`, `meta_value`) VALUES'
      //   . '(' . implode(', ', array_map(array($wpDbConnection, 'quote'), $attachmentMetas)) . ')'
      //   . 'ON DUPLICATE KEY UPDATE `post_id`=VALUES(`post_id`), `meta_key`=VALUES(`meta_key`), `meta_value`=VALUES(`meta_value`);'
      // ;

      // $postMetas = array(
      //   'post_id' => article['ID'],
      //   'meta_key' => '_thumbnail_id',
      //   'meta_value' => $artucle['ID'] + 10000;
      // );

      // $requeteInsertPostmeta = 'INSERT INTO ' . $wpTablePrefix . 'postmeta (`post_id`, `meta_key`, `meta_value`) VALUES'
      //   . '(' . implode(', ', array_map(array($wpDbConnection, 'quote'), $postMetas)) . ')'
      //   . 'ON DUPLICATE KEY UPDATE `post_id`=VALUES(`post_id`), `meta_key`=VALUES(`meta_key`), `meta_value`=VALUES(`meta_value`);'
      // ;

      // try {
      //   $wpDbConnection->exec($requeteInsertAttachment);

      //   $compteurSucces += count($insert);
      // } catch(Exception $e) {
      //   echo "-- ECHEC " . __FUNCTION__ . " REQUÊTE: [$requeteInsert]" . PHP_EOL;

      //   if (true !== $modeBourrin) {
      //     DbUtilz::restaureLaTable($wpDbConnection, $wpTablePrefix . 'posts');
      //     DbUtilz::restaureLaTable($wpDbConnection, $wpTablePrefix . 'postmeta');

      //     die(var_dump($e->errorInfo));
      //   }
      // }
    }

    echo '-- ' . $compteurSucces . ' images de couverture actualités migrées. ' . PHP_EOL;
    echo '-- ' . $compteurEchecs . ' erreurs rencontrées pendant la migration (mais rien de grave). ' . PHP_EOL;
    echo '-- ' . $compteurAbusay . ' images déjà migrées ' . PHP_EOL;
    echo '-- ' . $length . ' articles au total ' . PHP_EOL;
  }

}
