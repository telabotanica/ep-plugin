<?php

namespace Migration\App\DocumentsLinks;

use Migration\Api\BaseMigration;
use Migration\Api\MigrationException;
use Migration\App\AnnuaireTelaBpProfileDataMap;
use \Exception;
use \PDO;

/**
 * Migrates documents links for imported news from SPIP DB to WP DB.
 */
class DocumentsLinksMigration extends BaseMigration {

  public function migrate() {

    // Exclude not imported news ids and already imported documents news ids
    $requete_posts_ids = "SELECT `ID` FROM `posts` WHERE `post_type` = 'post' AND `post_status` = 'publish' AND `post_name` = CONCAT('article',CAST(ID AS CHAR)) AND `ID` NOT IN (7745,7851,8066,8287,8501,8655,8763,33767,35987);";
    $posts_ids = $this->wpDbConnection->query($requete_posts_ids)->fetchAll(PDO::FETCH_ASSOC);

    $posts_ids_string = implode( ',', array_column( $posts_ids , 'ID' ) );

    // Get informations for post documents to add
    $requete = "
      SELECT spip_documents_articles.`id_document`, spip_documents_articles.`id_article`, spip_documents.`titre` AS title, spip_documents.`descriptif` AS descriptif, spip_documents.`fichier` AS fichier
      FROM `spip_documents_articles` LEFT JOIN spip_documents ON  spip_documents.`id_document` = spip_documents_articles.`id_document`
      WHERE spip_documents_articles.`id_article` IN ($posts_ids_string) AND spip_documents.`mode` = 'document'
      ORDER BY spip_documents_articles.`id_article` ASC;
    ";
    $post_documents = $this->spipDbConnection->query($requete)->fetchAll(PDO::FETCH_ASSOC);

    foreach ( $post_documents as $post_document ) {
      $posts[$post_document['id_article']]['documents'][$post_document['id_document']] = $post_document;
    }

    // An array of all already existing metas for posts whitch have documents to add
    $selected_post_ids = implode(',', array_keys($posts));
    $post_metas_sql = "SELECT `meta_id`,`post_id`,`meta_key`,`meta_value` FROM {$this->wpTablePrefix}postmeta WHERE `post_id` IN ($selected_post_ids) ORDER BY `meta_id` ASC";
    $post_metas = $this->wpDbConnection->query($post_metas_sql)->fetchAll(PDO::FETCH_ASSOC);
    // Some metas have to be added later
    $last_meta_id = end($post_metas)['meta_id'];

    // Already has components
    $posts_has_components = array();
    // metas 'components' with no value
    $components_to_unset = array();
    foreach ( $post_metas as $meta_id => $post_meta ) {

      if ( 'components' === $post_meta['meta_key'] ) {
        // Found one post meta having 'component' with no meta value
        if ( '' === $post_meta['meta_value']) {
          $components_to_unset[] = $post_meta['post_id'];
        } else {
          $meta_value = unserialize( $post_meta['meta_value'] );

          // create the id of the new component
          end($meta_value);
          $component_id = key($meta_value) + 1;
          reset($meta_value);

          // add new component
          array_push( $meta_value, 'links' );
          $meta_value = serialize( $meta_value );

          $request_update_component_value = "UPDATE {$this->wpTablePrefix}postmeta
          SET `meta_value` = '$meta_value'
          WHERE `meta_id` = " . $post_meta['meta_id']. ";";

          try {
            $this->wpDbConnection->exec($request_update_component_value);

          } catch(Exception $e) {
            echo "-- ECHEC " . basename(__FILE__) . ':' . __FUNCTION__ . " REQUÊTE: [$request_update_component_value]" . PHP_EOL;

            throw new MigrationException($e, $request_update_component_value, basename(__FILE__) . ':' . __FUNCTION__);
          }

          $posts_has_components[$post_meta['post_id']] = $component_id;
          $post_meta['meta_value'] = $meta_value;
        }
      }

      $posts[$post_meta['post_id']]['meta'][$post_meta['meta_id']] = $post_meta;
    }

    // Usable $posts array and clean database
    $metas_to_delete = array();
    foreach (array_keys($posts) as $post_id) {

      // We need to set at least the meta_key
      if ( !in_array( 'meta',array_keys($posts[ $post_id ] ) ) ) {
        $posts[ $post_id ]['meta'][$last_meta_id]['meta_key'] = '';
        $last_meta_id++;

      }

      // Clean wrong set components in $posts
      if (in_array( $post_id , $components_to_unset )) {
        foreach ( $posts[ $post_id ]['meta'] as $unset_meta ) {

          if( strstr ( $unset_meta['meta_key'] , 'components' ) ){
            unset( $posts[ $post_id ]['meta'][$unset_meta['meta_id']] );

            // store wrong meta ids to delete from database
            array_push( $metas_to_delete, $unset_meta['meta_id'] );
          }

        }
      }

    }
    // clean database
    $metas_to_delete = implode( ',', $metas_to_delete);
    $delete_meta_request = "DELETE FROM {$this->wpTablePrefix}postmeta WHERE `meta_id` IN ($metas_to_delete) AND `meta_key` LIKE '%components%';";
    try {
      $this->wpDbConnection->exec($delete_meta_request);

    } catch(Exception $e) {
      echo "-- ECHEC " . basename(__FILE__) . ':' . __FUNCTION__ . " REQUÊTE: [$delete_meta_request]" . PHP_EOL;

      throw new MigrationException($e, $delete_meta_request, basename(__FILE__) . ':' . __FUNCTION__);
    }

    $compteurSucces = 0;
    // Insert metas for post_documents
    foreach ( $posts as $id_article => $documents ) {
      $component_id = '';

      // Already added meta "components" previously
      if ( array_key_exists( $id_article, $posts_has_components ) ) {
        $component_id = $posts_has_components[ $id_article ];
        $values = '';

      } else {
        $component_id = 0;
        $values = "
          ($id_article, 'components', 'a:1:{i:0;s:5:\"links\";}'),
          ($id_article, '_components', 'field_58177612a0f3b'),
        ";
      }

      // Sure there's no imported post having documents metas whith title "Documents", as tested the request :
      // SELECT post_id FROM postmeta where meta_value = 'Documents' and post_id <= 8800 order by post_id
      // ( All posts having documents to add : post_id <= 8800 )
      $values = "
        ($id_article, 'components_{$component_id}_title', 'Documents'),
        ($id_article, '_components_{$component_id}_title', 'field_5817910c37e01_field_5817810a01e2a'),
      ";
      $items = array();
      foreach ( $documents['documents'] as $document ) {

        $destination = serialize ( array(
          'url'    => 'https://www.tela-botanica.org/actu/'.$document['fichier'],
          'title'  => $document['descriptif'],
          'target' => '_blank',
          'postid' => ''
        ) );

        $item_id = count( $items );
        $values .= "
          ($id_article, 'components_{$component_id}_items_{$item_id}_text', {$this->wpDbConnection->quote($document['title'])}),
          ($id_article, '_components_{$component_id}_items_{$item_id}_text', 'field_5817814701e2c'),
          ($id_article, 'components_{$component_id}_items_{$item_id}_destination', {$this->wpDbConnection->quote($destination)}),
          ($id_article, '_components_{$component_id}_items_{$item_id}_destination', 'field_5817815e01e2d'),
        ";
        $items[] = 'link';
      }

      $values .= "
        ($id_article, 'components_{$component_id}_items',  '" . serialize( $items ) . "'),
        ($id_article, '_components_{$component_id}_items', 'field_5817910c37e01_field_5817812801e2b')
      ";

      $req = "INSERT INTO {$this->wpTablePrefix}postmeta (`post_id`, `meta_key`, `meta_value`) VALUES $values;";
      try {
        $this->wpDbConnection->exec($req);
        // // Verbose
        // echo $compteurSucces . PHP_EOL;
        $compteurSucces++;
      } catch(Exception $e) {
        echo "-- ECHEC " . basename(__FILE__) . ':' . __FUNCTION__ . " REQUÊTE: [$req]" . PHP_EOL;

        throw new MigrationException($e, $req, basename(__FILE__) . ':' . __FUNCTION__);
      }
    }
    echo '-- ' . $compteurSucces . ' metas de documents liés aux actualites migrées. ' . PHP_EOL;

  }
}
