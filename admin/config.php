<?php  

	if( isset( $_GET[ 'tab' ] ) ) {  
		$active_tab = $_GET[ 'tab' ];  
	} else {
		$active_tab = 'porte-documents';
	}
	?> 
	 
	<div class="wrap">
	
		<h2>Configuration des outils de l'Espace projets</h2>
		
		<div class="description">Cette page vous permet de configurer les outils sur l'ensemble des projets actifs sur le site.</div>
		
		<?php settings_errors(); ?> 

		<h2 class="nav-tab-wrapper">  
		    <a href="?page=configuration&tab=porte-documents" class="nav-tab <?php echo $active_tab == 'porte-documents' ? 'nav-tab-active' : ''; ?>">Porte-documents</a>  
		    <a href="?page=configuration&tab=forum" class="nav-tab <?php echo $active_tab == 'forum' ? 'nav-tab-active' : ''; ?>">Forum</a>  
		</h2>  

		<form method="post" action=""> 
		<?php
		
		    if( $active_tab == 'porte-documents' ) {  
		        settings_fields( 'porte-documents' );
		        do_settings_sections( 'configuration' );
		?>
		        <input type="text" name="activation-porte-documents" value="<?php echo get_option('admin_email'); ?>" />
		<?php
		    } 
		    elseif( $active_tab == 'forum' ) {
		        settings_fields( 'forum' );
		        do_settings_sections( 'configuration' );
		?>
				<input type="text" name="activation-porte-documents" value="<?php echo get_option('admin_email'); ?>" />
		<?php
		    }
		?>
		
			

		    <?php submit_button(); ?> 
		    
		</form> 

	</div>

