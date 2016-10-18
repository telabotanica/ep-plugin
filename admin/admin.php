<?php
/**
 * Ajout d'une page d'administration au tableau de bord Wordpress.
 * Permet à l'administrateur de définir la configuration globale des outils
 * (chemins des services, des applications, etc.)
 */

// Hook pour le menu Admin
add_action('admin_menu', 'tb_ajout_pages');

// Actions du hook
function tb_ajout_pages() {

    // Ajoute un menu 'Tela Botanica' au tableau de bord Wordpress
    add_menu_page(
		'Espace Projets', 
		'Espace Projets', 
		'manage_options', 
		'telabotanica'
	);

    // Ajoute un sous-menu 'Présentation' dans 'Tela Botanica'
    add_submenu_page(
    	'telabotanica', 
    	'Présentation',
    	'Présentation', 
    	'manage_options', 
    	'telabotanica', // On donne le même 'menu_slug' que celui du menu pour écraser le sous-menu automatique
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

/**
 * Page de présentation et documentation du plugin
 */
function tb_ssmenu_home() {
	include "admin_presentation.php";
}

/**
 * Affiche le sous-menu 'Configuration'; lit et enregistre les réglages dans
 * la table "options" de WP (standard)
 */
function tb_ssmenu_configuration() {
    
    /* Gestion des onglets */
	if( isset( $_GET[ 'onglet' ] ) ) {  
		$onglet_actif = $_GET[ 'onglet' ];  
	} else {
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
				// config par défaut de l'outil
				$cheminConfigDefautPd = __DIR__ . '/../outils/porte-documents_config-defaut.json';
				$configDefautPd = json_decode(file_get_contents($cheminConfigDefautPd), true);
				// injection des valeurs du formulaire
				$configDefautPd['filesServiceUrl'] = $opt_val_pd;
				// mise à jour de la BDD
				update_option( $opt_name_pd, json_encode($configDefautPd) );
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
							<input type="text" name="<?php echo $data_field_name_pd; ?>" value="<?php echo $tab_json_pd['filesServiceUrl']; ?>" />
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
				// config par défaut de l'outil
				$cheminConfigDefautForum = __DIR__ . '/../outils/forum_config-defaut.json';
				$configDefautForum = json_decode(file_get_contents($cheminConfigDefautForum), true);
				// injection des valeurs du formulaire
				$configDefautForum['ezmlm-php']['rootUri'] = $opt_val_forum;
				// mise à jour de la BDD
				update_option( $opt_name_forum, json_encode($configDefautForum) );
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
