<?php

// Hook pour le menu Admin
add_action('admin_menu', 'tb_ajout_pages');

// Actions du hook
function tb_ajout_pages() {

    /* Ajoute un sous-menu 'Tela Botanica' dans 'Réglages'
    add_options_page(
    	'Tela Botanica', 
    	'Tela Botanica', 
    	'manage_options', 
    	'tb-reglages', 
    	'tb_ssmenu_reglages'
    );

    // Ajoute un sous-menu 'Tela Botanica' dans 'Outils'
    add_management_page(
    	'Tela Botanica', 
    	'Tela Botanica', 
    	'manage_options', 
    	'tb-outils', 
    	'tb_ssmenu_outils'
    );
    */

    // Ajoute un menu 'Tela Botanica'
    add_menu_page(
		'Espace Projets', 
		'Espace Projets', 
		'manage_options', 
		'telabotanica', 
		'tb_menu_telabotanica' 
	);

    // Ajoute un sous-menu 'Présentation' dans 'Tela Botanica'
    add_submenu_page(
    	'telabotanica', 
    	'Présentation',
    	'Présentation', 
    	'manage_options', 
    	'telabotanica', 	// On donne le même 'menu_slug' que celui du menu pour écraser le sous-menu automatique
    	'tb_ssmenu_home'
    );

    // Ajoute un sous-menu 'Configuration' dans 'Tela Botanica'
    add_submenu_page(
    	'telabotanica', 
    	'Configuration', 
    	'Configuration', 
    	'manage_options', 
    	'configuration', 
    	'tb_ssmenu_configuration'
    );
}

// tb_ssmenu_reglages() affiche le contenu de la page dans un sous-menu de Réglages
function tb_settings_page() {
    echo "<h2>Menu dans Réglages</h2>";
}

// tb_ssmenu_outils() affiche le contenu de la page du sous-menu de 'Outils'
function tb_tools_page() {
    echo "<h2>Menu dans Outils</h2>";
}

/* Inutile car remplacé par le sous-menu 'Home'
 * tb_tools_page() affiche le contenu de la page du menu 'Tela Botanica'
function tb_menu_telabotanica() {
    echo "<h2>Menu Tela Botanica</h2>";
}
*/

// tb_ssmenu_home() affiche le contenu de la page du sous-menu 'Home' de 'Tela Botanica'
function tb_ssmenu_home() {
	?>
	<div class="wrap">
	
		<h2>Plugin Espace projets</h2>
	
	</div>
	<?php
}

// tb_ssmenu_configuration() affiche le contenu de la page du sous-menu 'Configuration' de 'Tela Botanica'
// of the custom Test Toplevel menu
function tb_ssmenu_configuration() {
    
    /* Gestion des onglets */
	if( isset( $_GET[ 'onglet' ] ) ) {  
		$onglet_actif = $_GET[ 'onglet' ];  
	} 
	else {
		$onglet_actif = 'porte-documents';
	}
	?> 
	
	<!-- Vue du sous-menu 'Configuration' -->
	<div class="wrap">
	
		<?php
		if (!current_user_can('manage_options'))
		{
			wp_die( __('Vous n\'avez pas les droits suffisants pour accéder à cette page.') );
		}
		?>
	
		<?php screen_icon(); ?>
	
		<!-- Titre -->
		<h2>Configuration des outils de l'Espace projets</h2>
		
		<!-- Description -->
		<!--<div class="description">Cette page vous permet de configurer les outils sur l'ensemble des projets actifs sur le site.</div>-->
		
		<?php settings_errors(); ?> 

		<!-- Menu des onglets -->
		<h2 class="nav-tab-wrapper">  
		    <a href="?page=configuration&onglet=porte-documents" class="nav-tab <?php echo $onglet_actif == 'porte-documents' ? 'nav-tab-active' : ''; ?>">Porte-documents</a>  
		    <a href="?page=configuration&onglet=forum" class="nav-tab <?php echo $onglet_actif == 'forum' ? 'nav-tab-active' : ''; ?>">Forum</a>  
		</h2>
		
		<!-- Onglet Porte-documents -->
		<?php
	    if( $onglet_actif == 'porte-documents' ) {  
	       
	       	$opt_name_pd = 'tb_porte-documents_config';
			$hidden_field_name_pd = 'tb_submit_hidden';
			$data_field_name_pd = 'tb_porte-documents_config';
			
			$opt_val_pd = get_option( $opt_name );
			
			if( isset($_POST[ $hidden_field_name_pd ]) && $_POST[ $hidden_field_name_pd ] == 'Y' ) {
				$opt_val_pd = $_POST[ $data_field_name_pd ];
				$configDefaut_pd = array(
					"title" => "", // laisser vide pour que WP/BP gèrent le titre
					"hrefBuildMode" => "REST",
					"defaultPage" => "view-list",
					"ezmlm-php" => array(
						"rootUri" => $opt_val_pd,
						"list" => "" // si vide, cherchera une liste ayant le nom du projet
					)
				);
				update_option( $opt_name_pd, json_encode($configDefaut_pd) );
			?>
			
			<!-- Confirmation de l'enregistrement -->
			<div class="updated">
				<p>
					<strong>Options mises à jour</strong>
				</p>
			</div>
			
			<?php
			}
			
			?>
			
			<div class="wrap">

				<form method="post" action="">
				
					<input type="hidden" name="<?php echo $hidden_field_name_pd; ?>" value="Y">
					
					<?php 
			
					/* Lecture du JSON dans 'wp_options' */
					$config__pd = get_option('tb_porte-documents_config');
					
					/* Parsage du JSON */
					$tab_json_pd = json_decode($config__pd, true);
					
					?>
		
					<!-- URL ezmlm-php -->
					<div class="wrap">
						<p>URL du porte-documents
							<input type="text" name="<?php echo $data_field_name_pd; ?>" value="<?php echo $tab_json_pd['ezmlm-php']['rootUri']; ?>" />
						</p>	
					</div>
					
					<hr/>

					<!-- Enregistrer les modifications -->
					<p class="submit">
						<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
					</p>

				</form>
				
			</div>
	       
	       <?php
	       
	    } 
	    
	    /* Onglet Forum */
	    elseif( $onglet_actif == 'forum' ) {

			$opt_name_forum = 'tb_forum_config';
			$hidden_field_name_forum = 'tb_submit_hidden';
			$data_field_name_forum = 'tb_forum_config';
			
			$opt_val_forum = get_option( $opt_name_forum );
			
			if( isset($_POST[ $hidden_field_name_forum ]) && $_POST[ $hidden_field_name_forum ] == 'Y' ) {
				$opt_val_forum = $_POST[ $data_field_name_forum ];
				$configDefaut_forum = array(
					"title" => "", // laisser vide pour que WP/BP gèrent le titre
					"hrefBuildMode" => "REST",
					"defaultPage" => "view-list",
					"ezmlm-php" => array(
						"rootUri" => $opt_val_forum,
						"list" => "" // si vide, cherchera une liste ayant le nom du projet
					)
				);
				update_option( $opt_name_forum, json_encode($configDefaut_forum) );
			?>
			
			<!-- Confirmation de l'enregistrement -->
			<div class="updated">
				<p>
					<strong>Options mises à jour</strong>
				</p>
			</div>
			
			<?php
			}
			
			?>
			
			<div class="wrap">

				<form method="post" action="">
				
					<input type="hidden" name="<?php echo $hidden_field_name_forum; ?>" value="Y">
					
			<?php 
					/* Lecture du JSON dans 'wp_options' */
					$config_forum = get_option('tb_forum_config');
					
					/* Parsage du JSON */
					$tab_json_forum = json_decode($config_forum, true);
					
					
			?>
		
					<!-- URL ezmlm-php -->
					<div class="wrap">
						<p>URL de la librairie ezmlm-php
							<input type="text" name="<?php echo $data_field_name_forum; ?>" value="<?php echo $tab_json_forum['ezmlm-php']['rootUri']; ?>" />
						</p>	
					</div>
					
					<hr/>

					<!-- Enregistrer les modifications -->
					<p class="submit">
						<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
					</p>

				</form>
			</div>
		
	<?php } ?>
			

	</div>
	<?php }?>
