<?php  

	if( isset( $_GET[ 'onglet' ] ) ) {  
		$onglet_actif = $_GET[ 'onglet' ];  
	} 
	else {
		$onglet_actif = 'porte-documents';
	}
	?> 
	 
	<div class="wrap">
	
		<?php screen_icon(); ?>
	
		<h2>Configuration des outils de l'Espace projets</h2>
		
		<div class="description">Cette page vous permet de configurer les outils sur l'ensemble des projets actifs sur le site.</div>
		
		<?php settings_errors(); ?> 

		<h2 class="nav-tab-wrapper">  
		    <a href="?page=configuration&onglet=porte-documents" class="nav-tab <?php echo $onglet_actif == 'porte-documents' ? 'nav-tab-active' : ''; ?>">Porte-documents</a>  
		    <a href="?page=configuration&onglet=forum" class="nav-tab <?php echo $onglet_actif == 'forum' ? 'nav-tab-active' : ''; ?>">Forum</a>  
		</h2>

		<form method="post" action="options.php"> 
		
			<!-- Onglet Porte-documents -->
			<?php
		    if( $onglet_actif == 'porte-documents' ) {  
		        settings_fields( 'porte-documents' );
		        do_settings_sections( 'porte-documents' );
			?>
		    <!--<input type="text" name="activation-porte-documents" value="<?php echo get_option('admin_email'); ?>" />-->
		        
			<!-- Onglet Forum -->
			<?php
		    } 
		    
		    
		    elseif( $onglet_actif == 'forum' ) {
		        settings_fields( 'forum' );
		        do_settings_sections( 'forum' );
		        
		        /* Lecture du JSON dans 'wp_options' */
		        $config_forum = get_option('tb_forum_config');
		        
		        /* Parsage du JSON */
				$tab_json = json_decode($config_forum, true);
		?>
		
			<!-- URL ezmlm-php -->
			<div class="wrap">
				<p>URL de la librairie ezmlm-php
					<input type="text" name="forum-rootURI" value="<?php echo $tab_json['ezmlm-php']['rootUri']; ?>" />
				</p>	
			</div>
			
			
			<?php } ?>
			
			<!-- Enregistrer les modifications -->
		    <?php submit_button(); ?> 
		    
		</form> 

	</div>
