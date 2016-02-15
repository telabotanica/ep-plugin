<?php

class Forum extends TB_Outil {

	function forum()
	{
		// config par défaut, si aucune config n'est trouvée dans la base
		$this->slug = 'forum';
		$this->name = 'Forum';
		// recherche d'une config générale dans la base
		$this->chargerConfig();

		//echo "CONFIIIIIG !!!"; var_dump($this->config);
	}

	/**
	 * Exécuté lors de l'installation du plugin TelaBotanica; ATTENTION, à ce
	 * moment elle est appelée en contexte non-objet
	 */
	public function installation()
	{
		global $wpdb;

		// @TODO maintenir en cohésion avec le fichier config.defaut.json d'ezmlm-php
		$configDefaut = array(
			"domainRoot" => "http://localhost",
			"baseUri" => "/ezmlm-forum",
			"title" => "TB_forum",
			"hrefBuildMode" => "REST",
			"defaultPage" => "view-list",
			"ezmlm-php" => array(
				"rootUri" => "http://localhost/ezmlm-php",
				"_rootUri" => "http://vpopmail.tela-botanica.org/ezmlm-service-test",
				"list" => "example-list"
			)
		);

		// l'id outil "forum" n'est pas tiré de $this->slug car la méthode d'install
		// est appelée en contexte non-objet => mettre le slug dans un attribut statique ?
		$insert_config_defaut = "
			INSERT INTO `{$wpdb->prefix}tb_outils` VALUES (
				'forum', 1, '" . json_encode($configDefaut) . "'
			);
		";
		$wpdb->query($insert_config_defaut);
	}

	/* Vue onglet admin */
	function edit_screen() {
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
		
		<p class="editfield">
			<label for="confidentialite-outil">Outil privé <br/>(<?php echo $this->prive ?>)</label>
			<input type="range" min="0" max="1" id="confidentialite-outil" class="pointer on-off" name="confidentialite-outil" value="<?php echo $this->prive ?>"/>
		</p>
		
		<?php
		
		wp_nonce_field( 'groups_edit_save_' . $this->slug );
		do_action( 'bp_after_group_settings_admin' );
	}

	function edit_screen_save() {
		global $wpdb, $bp;
		$id_projet = bp_get_current_group_id();
		if ( !isset( $_POST ) )	return false;
		check_admin_referer( 'groups_edit_save_' . $this->slug );
		
		/* Mise à jour de la ligne dans la base de données */
		$table = "{$wpdb->prefix}tb_outils_reglages";
		$data = array( 												
			'enable_nav_item' => $_POST['activation-outil'],
			'name' => $_POST['nom-outil'],
			'nav_item_position' => $_POST['position-outil'],
			'prive' => $_POST['confidentialite-outil'],
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
	function display() {
		$id_projet = bp_get_current_group_id();
		if ($this->prive) {
			// on ne devrait passer là que si les contrôles de sécurités précédents
			// ont réussi, càd si on est dans un groupe auquel on a droit (soit
			// le groupe est public, soit l'utilisateur en est membre)
			echo "<h4>L'outil <?php echo $this->name ?> est réservé aux membres du groupe</h4>";
			return;
		}

		// 1) lire la config de l'outil pour tout WP

		// 2) lire la config de l'outil pour le projet en cours

		// 3) amorcer l'outil
		//include "forum/index.php";
	}
	
}

bp_register_group_extension( 'Forum' );

?>
