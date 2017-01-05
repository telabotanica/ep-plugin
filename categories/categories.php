<?php

// catégories de projets
add_action( 'bp_groups_register_group_types', 'tb_ajout_categories_projets' );
// restriction du choix (lors de la création et par la suite dans les réglages)
add_action( 'groups_screen_group_admin_settings', 'tb_restriction_choix_categories' );
add_action( 'groups_creation_tabs', 'tb_restriction_choix_categories' );

/**
 * Ajoute à BP les catégories de projets Tela Botanica
 * 
 * @WARNING s'assurer qu'au moins une a "create_screen_checked => true", sans
 * quoi le script JS de restriction des catégories permettra de n'en cocher
 * aucune (ce qui est formellement interdit)
 * 
 * @TODO déplacer les descriptions dans config.json ?
 */
function tb_ajout_categories_projets()
{
	bp_groups_register_group_type('echanges', array(
		'labels' => array(
			'name' => __( 'Échanges', 'telabotanica' ),
			'singular_name' => __( 'Échanges', 'telabotanica' )
		),
		'has_directory' => 'Échanges',
		'show_in_create_screen' => true,
		'show_in_list' => true,
		'description' => __( 'Discussion', 'telabotanica' ),
		'create_screen_checked' => true
	));

	bp_groups_register_group_type('botanique-locale', array(
		'labels' => array(
			'name' => __( 'Botanique', 'telabotanica' ),
			'singular_name' => __( 'Botanique', 'telabotanica' )
		),
		'has_directory' => 'botanique-locale',
		'show_in_create_screen' => true,
		'show_in_list' => true,
		'description' => __( 'Botanique en territoire : rencontres, sorties de terrain...', 'telabotanica' ),
		'create_screen_checked' => false
	));

	bp_groups_register_group_type('outils-informatiques', array(
		'labels' => array(
			'name' => __( 'Outils informatiques', 'telabotanica' ),
			'singular_name' => __( 'Outils informatiques', 'telabotanica' )
		),
		'has_directory' => 'outils-informatiques',
		'show_in_create_screen' => true,
		'show_in_list' => true,
		'description' => __( 'Projets présentant les outils et recevant les remarques (gné?)', 'telabotanica' ),
		'create_screen_checked' => false
	));

	bp_groups_register_group_type('organisation', array(
		'labels' => array(
			'name' => __( 'Organisation', 'telabotanica' ),
			'singular_name' => __( 'Organisation', 'telabotanica' )
		),
		'has_directory' => 'organisation',
		'show_in_create_screen' => true,
		'show_in_list' => true,
		'description' => __( 'Co-organisation de programmes, comités de pilotage...', 'telabotanica' ),
		'create_screen_checked' => false
	));

	bp_groups_register_group_type('contribution', array(
		'labels' => array(
			'name' => __( 'Contribution', 'telabotanica' ),
			'singular_name' => __( 'Contribution', 'telabotanica' )
		),
		'has_directory' => 'contribution',
		'show_in_create_screen' => true,
		'show_in_list' => true,
		'description' => __( "Saisie d'observations botaniques", 'telabotanica' ),
		'create_screen_checked' => false
	));

	bp_groups_register_group_type('construction', array(
		'labels' => array(
			'name' => __( 'Construction', 'telabotanica' ),
			'singular_name' => __( 'Construction', 'telabotanica' )
		),
		'has_directory' => 'construction',
		'show_in_create_screen' => true,
		'show_in_list' => true,
		'description' => __( 'Création de banque de données', 'telabotanica' ),
		'create_screen_checked' => false
	));

	// cette catégorie n'est accessible qu'à l'admin de WP via le TdB; elle est
	// cumulable avec les autres
	bp_groups_register_group_type('tela-botanica', array(
		'labels' => array(
			'name' => 'Tela Botanica',
			'singular_name' => 'Tela Botanica'
		),
		'has_directory' => 'tela-botanica',
		'show_in_create_screen' => false,
		'show_in_list' => true,
		'description' => __( 'Projets officiels de Tela Botanica', 'telabotanica' )
	));
}

/**
 * Charge un Javascript qui empêche de cocher plus d'une case de catégorie de
 * projets
 */
function tb_restriction_choix_categories()
{
	wp_enqueue_script('restriction-categories-projets', WP_PLUGIN_URL . '/tela-botanica/categories/categories.js', array('jquery'));
}