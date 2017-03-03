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
	bp_groups_register_group_type('discussion', array(
		'labels' => array(
			'name' => __( 'Discussion', 'telabotanica' ),
			'singular_name' => __( 'Discussion', 'telabotanica' )
		),
		'has_directory' => 'discussion',
		'show_in_create_screen' => true,
		'show_in_list' => true,
		'description' => __( "Échanges d'informations et de points de vue sur un thème", 'telabotanica' ),
		'create_screen_checked' => true
	));

	bp_groups_register_group_type('botanique-locale', array(
		'labels' => array(
			'name' => __( 'Botanique locale', 'telabotanica' ),
			'singular_name' => __( 'Botanique locale', 'telabotanica' )
		),
		'has_directory' => 'botanique-locale',
		'show_in_create_screen' => true,
		'show_in_list' => true,
		'description' => __( 'Rencontres en territoires, sorties de terrain...', 'telabotanica' ),
		'create_screen_checked' => false
	));

	bp_groups_register_group_type('outils-numeriques', array(
		'labels' => array(
			'name' => __( 'Outils numériques', 'telabotanica' ),
			'singular_name' => __( 'Outils numériques', 'telabotanica' )
		),
		'has_directory' => 'outils-numeriques',
		'show_in_create_screen' => true,
		'show_in_list' => true,
		'description' => __( "Présentation d'outils informatiques et suivi de leur développement", 'telabotanica' ),
		'create_screen_checked' => false
	));

	bp_groups_register_group_type('coordination', array(
		'labels' => array(
			'name' => __( 'Coordination', 'telabotanica' ),
			'singular_name' => __( 'Coordination', 'telabotanica' )
		),
		'has_directory' => 'coordination',
		'show_in_create_screen' => true,
		'show_in_list' => true,
		'description' => __( 'Co-organisation de programmes, comités de pilotage...', 'telabotanica' ),
		'create_screen_checked' => false
	));

	bp_groups_register_group_type('collaboration-contribution', array(
		'labels' => array(
			'name' => __( 'Collaboration - Contribution', 'telabotanica' ),
			'singular_name' => __( 'Collaboration - Contribution', 'telabotanica' )
		),
		'has_directory' => 'collaboration-contribution',
		'show_in_create_screen' => true,
		'show_in_list' => true,
		'description' => __( "Saisie et partage d'observations botaniques", 'telabotanica' ),
		'create_screen_checked' => false
	));

	bp_groups_register_group_type('cooperation', array(
		'labels' => array(
			'name' => __( 'Coopération', 'telabotanica' ),
			'singular_name' => __( 'Coopération', 'telabotanica' )
		),
		'has_directory' => 'cooperation',
		'show_in_create_screen' => true,
		'show_in_list' => true,
		'description' => __( 'Création et amélioration de banque de données de connaissances botaniques', 'telabotanica' ),
		'create_screen_checked' => false
	));

	// ces catégories ne sont accessibles qu'à l'admin de WP via le TdB; elles
	// sont cumulables avec les autres
	bp_groups_register_group_type('tela-botanica', array(
		'labels' => array(
			'name' => 'Tela Botanica',
			'singular_name' => 'Tela Botanica'
		),
		'has_directory' => 'tela-botanica',
		'show_in_create_screen' => false,
		'show_in_list' => true,
		'description' => __( "Projets portés par l'équipe de Tela Botanica", 'telabotanica' )
	));

	bp_groups_register_group_type('archive', array(
		'labels' => array(
			'name' => __('Archivé', 'telabotanica'),
			'singular_name' => __('Archivé', 'telabotanica'),
		),
		'has_directory' => 'archives',
		'show_in_create_screen' => false,
		'show_in_list' => true,
		'description' => __( 'Projets terminés', 'telabotanica' )
	));

	bp_groups_register_group_type('sciences-participatives', array(
		'labels' => array(
			'name' => __('Sciences participatives', 'telabotanica'),
			'singular_name' => __('Sciences participatives', 'telabotanica'),
		),
		'has_directory' => 'sciences-participatives',
		'show_in_create_screen' => false,
		'show_in_list' => true,
		'description' => __( 'Observatoires citoyens', 'telabotanica' )
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