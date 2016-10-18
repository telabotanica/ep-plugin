<?php

class Porte_Documents extends TB_Outil {

	public function __construct() {
	
		global $wpdb, $bp;
	
		/* Initialisation des éléments nécessaires à la création d'un outil */
		$id_projet = bp_get_current_group_id();
		$this->slug = 'porte-documents';
		$this->name = 'Porte-documents';

		// init du parent
		$this->initialisation();
	}

	// @TODO maintenir en cohésion avec le fichier main-config.js de cumulus-front
	public function getConfigDefautOutil()
	{
		$configDefaut = array(
			"ver" => '0.1',
			"filesServiceUrl" => 'http://api.tela-botanica.org/service:cumulus:doc',
			"userInfoByIdUrl" => 'https://www.tela-botanica.org/service:annuaire:utilisateur/infosParIds/',
			"abstractionPath" => '/mon',
			"ressourcesPath" => '', // in including mode, represents the path of application root path
			"group" => null,
			"authUrl" => 'https://www.tela-botanica.org/service:annuaire:auth',
			"tokenUrl" => 'https://www.tela-botanica.org/service:annuaire:auth/identite'
		);
		return $configDefaut;
	}

	/**
	 * Exécuté lors de l'installation du plugin TelaBotanica
	 */
	public function installation()
	{
		global $wpdb;
		$configDefaut = Porte_Documents::getConfigDefautOutil();
		// l'id outil "porte-documents" n'est pas tiré de $this->slug car la méthode d'install
		// est appelée en contexte non-objet => mettre le slug dans un attribut statique ?
		add_option('tb_porte-documents_config',json_encode($configDefaut));
	}

	/**
	 * Exécuté lors de la désinstallation du plugin TelaBotanica
	 */
	public function desinstallation()
	{
	}

	/* Vue onglet admin */
	function edit_screen($group_id = null)
	{
		if ( !bp_is_group_admin_screen( $this->slug ) )
		return false;
		
		?>
		<h4>Paramètres de l'outil <?php echo $this->name ?></h4>
		
		<p class="editfield">
			<?php
				if ( $this->enable_nav_item ) { $activation = "actif"; }
				else { $activation = "inactif"; }
			?>
			<label for="activation-outil">Activation de l'outil <br/>(<?php echo $activation ?>)</label>
			<input type="range" min="0" max="1" id="activation-outil" class="pointer on-off" name="activation-outil" value="<?php echo $this->enable_nav_item ?>"/>
		</p>
		
		<p class="editfield">
			<label for="nom-outil">Nom de l'outil</label>
			<input type="text" id="nom-outil" name="nom-outil" value="<?php echo $this->name ?>" />
		</p>
		
		<p class="editfield">
			<label for="position-outil">Position de l'outil <br/>(<?php echo $this->nav_item_position ?>)</label>
			<input type="range" min="0" max="100" step="5" id="position-outil" class="pointer" name="position-outil" value="<?php echo $this->nav_item_position ?>"/>
		</p>
		
		<!-- Marche pas
		<p class="editfield">
			<label for="confidentialite-outil">Outil privé <br/>(<?php echo $this->prive ?>)</label>
			<input type="range" min="0" max="1" id="confidentialite-outil" class="pointer on-off" name="confidentialite-outil" value="<?php echo $this->prive ?>"/>
		</p>
		-->
		
		<?php
		
		wp_nonce_field( 'groups_edit_save_' . $this->slug );
		do_action( 'bp_after_group_settings_admin' );
	}

	function edit_screen_save($group_id = null) {
		global $wpdb, $bp;
		$id_projet = bp_get_current_group_id();
		if ( !isset( $_POST ) )	return false;
		check_admin_referer( 'groups_edit_save_' . $this->slug );
		
		/* Mise à jour de la ligne dans la base de données */
		$table = "{$wpdb->prefix}tb_outils_reglages";
		$data = array( 												
			'enable_nav_item' => $_POST['activation-outil'],
			'name' => $_POST['nom-outil'],
			'nav_item_position' => $_POST['position-outil']
			//'prive' => $_POST['confidentialite-outil']
		);
		$where = array( 												
			'id_projet' => $id_projet,
			'id_outil' => $this->slug
		);
		$format = null;
		$where_format = null;
		$wpdb->update($table, $data, $where, $format, $where_format);
		
		$success = 1;
		if ( !$success )
		bp_core_add_message( __( 'There was an error saving, please try again', 'buddypress' ), 'error' );
		else
		bp_core_add_message( __( 'Settings saved successfully', 'buddypress' ) );
		bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . '/admin/' . $this->slug );
	}
	

	/* Vue onglet principal */
	function display($group_id = null) {
		$id_projet = bp_get_current_group_id();
		if ( (!$this->prive) || ($this->prive && bp_get_total_group_count_for_user( bp_loggedin_user_id() ) ) ) {
		?>
		<ul>
			<li>ID du projet : <?php echo $id_projet ?></li>
			<li>ID de l'outil : <?php echo $this->slug ?></li>
			<li>Nom de l'outil : <?php echo $this->name ?></li>
			<li>Outil privé : <?php echo $this->prive ?></li>
			<li>Position de l'onglet lors de la création d'un projet : <?php echo $this->create_step_position ?></li>
			<li>Position de l'onglet lors de la consultation d'un projet : <?php echo $this->nav_item_position ?></li>
			<li>Activation de l'onglet : <?php echo $this->enable_nav_item ?></li>
		</ul>
		
		<?php
		}
		else {
		?>
		<h4>L'outil <?php echo $this->name ?> est réservé aux membres du groupe</h4>
		<?php
		}

	}
	
}

bp_register_group_extension('Porte_Documents');

?>
