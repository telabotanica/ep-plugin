<?php

class Forum extends TB_Outil {

	function forum() {

		global $wpdb, $bp;

		/* Initialisation des éléments nécessaires à la création d'un outil */
		$id_projet = bp_get_current_group_id();
		$this->slug = 'forum';
		$this->name = 'Forum';
		$this->prive = 0;
		$this->create_step_position = 100;
		$this->nav_item_position = 100;
		$this->enable_nav_item = 1;

		/* Lecture de la table "wp_tb_outils_reglages" */
		$requete = "
			SELECT * 
			FROM {$wpdb->prefix}tb_outils_reglages
			WHERE id_projet='".$id_projet."'
			AND id_outil='".$this->slug."'
		";
		$res = $wpdb->get_results($requete) ;

		/* Construction de l'objet */
		foreach ($res as $meta) {	
			$this->slug = $meta->id_outil;
			$this->name = $meta->name;
			$this->prive = $meta->prive;
			$this->create_step_position = $meta->create_step_position;
			$this->nav_item_position = $meta->nav_item_position;
			$this->enable_nav_item = $meta->enable_nav_item;
		}

		/* Ajout d'une ligne dans la base de données (stockage de l'objet) */
		/*$table = "{$wpdb->prefix}tb_outils_reglages";
		$data = array( 	
			'id_projet' => bp_get_current_group_id(),											
			'id_outil' => $this->slug,
			'name' => $this->name,
			'prive' => $this->prive,
			'create_step_position' => $this->create_step_position,
			'nav_item_position' => $this->nav_item_position,
			'enable_nav_item' => $this->enable_nav_item
		);
		$format = null;
		$wpdb->insert( $table, $data, $format );*/
	}

	/**
	 * Exécuté lors de l'installation du plugin TelaBotanica
	 */
	public function installation() {
		
	}

	/**
	 * Exécuté lors de la désinstallation du plugin TelaBotanica
	 */
	public function desinstallation() {
		
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
		include "forum/index.php";
	}
	
}

bp_register_group_extension( 'Forum' );

?>
