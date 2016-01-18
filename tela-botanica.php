<?php
/*
Plugin Name: Tela Botanica
Description: Plugin permettant d'ajouter les outils de Tela Botanica à l'espace projets
Version: en cours de développement
Author: Tela Botanica
*/


/* Chargement du code nécessitant BuddyPress */
function initialisation_bp() {

	/* Ajout du champ "Description courte" */
	//require( dirname( __FILE__ ) . '/description-courte.php' );

	/* Gestion des outils */

	global $wpdb;
	
	/* On parcourt les meta-données du projet consulté (52 babar) */
	$res = $wpdb->get_results("
		SELECT * 
		FROM {$wpdb->prefix}bp_groups_groupmeta 
		WHERE group_id=52
	") ;
	
	/* On affiche les outils s'ils sont activés */
	foreach ($res as $meta) {
		$outil = $meta->meta_key;
		if ($meta->meta_value == "true") {
			require( dirname( __FILE__ ) . '/outils/'.$outil.'.php' );
		}
	}
	
	
	
	
	
	
}

add_action( 'bp_include', 'initialisation_bp' );





